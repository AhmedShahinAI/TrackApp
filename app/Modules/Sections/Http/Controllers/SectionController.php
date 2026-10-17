<?php

declare(strict_types=1);

namespace App\Modules\Sections\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Sections\Actions\ChangeSectionInstructor;
use App\Modules\Sections\Actions\CreateSection;
use App\Modules\Sections\Enums\SectionStatus;
use App\Modules\Sections\Http\Requests\StoreSectionRequest;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use App\Modules\Shared\Support\CsvSafe;
use App\Modules\Shared\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The read surface for sections, as far as US2 needs it.
 *
 * ## Why this exists in the scope phase
 *
 * US2's Independent Test is "every access path shows only their own branch, and the other branch's
 * records return 404 rather than 403". A scope with no endpoint to read through is a scope nobody
 * has tested. T135 names the four paths explicitly — API route, Blade route, list, export — so all
 * four live here, built to contracts/academic.md so US4 extends them rather than replacing them.
 *
 * What is **not** here: create, update, the instructor reassignment, the approval workflow. Those
 * are US4 and Amendment 4, and writing them now would be scope creep dressed as momentum.
 *
 * ## There is not one `where` in this file
 *
 * That is the point of the global scope, and the reason this class can be read in a minute. The
 * branch and section constraints are applied by BranchScope and SectionScope on every query below,
 * including the count behind `meta.total` and the rows behind the export stream.
 */
final class SectionController extends Controller
{
    use RespondsToBoth;

    private const MAX_PER_PAGE = 100;

    /** One screenful of roster. The rest is a page away, not a megabyte of hidden markup. */
    private const ROSTER_PER_PAGE = 25;

    public function index(Request $request): JsonResponse|View
    {
        $sections = $this->filtered($request)
            ->with(['term', 'branch', 'course', 'program', 'instructor'])
            ->withCount(['enrollments as active_enrollments' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('section_number')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $sections->getCollection()->map($this->present(...))->all(),
                'meta' => [
                    'current_page' => $sections->currentPage(),
                    'last_page' => $sections->lastPage(),
                    'total' => $sections->total(),
                ],
            ]);
        }

        return view('sections.index', [
            'sections' => $sections,
            'filters' => $this->filterOptions($request),
        ] + $this->createOptions($request));
    }

    public function show(Request $request, Section $section): JsonResponse|View
    {
        $section->load(['term', 'branch', 'course', 'program', 'instructor']);

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->present($section)]);
        }

        /*
         * The roster and the handover history, for T200's tabs.
         *
         * Withdrawn enrollments are loaded, not filtered out. A roster that showed only the active
         * students would answer "who is here now" and silently lose "who withdrew, and why" — which
         * is the question an investigation asks, and the reason WithdrawEnrollment demands a reason
         * at all. The ordering puts the active rows first so the common read is unaffected.
         *
         * The history is loaded most-recent-first and keeps its open row (unassigned_at = null)
         * last-written rather than special-cased, so a section that has never been reassigned shows
         * one row rather than an empty tab.
         */
        $section->load([
            'instructorHistory' => fn ($query) => $query
                ->with(['instructor', 'changedBy'])
                ->orderByDesc('assigned_at')
                ->orderByDesc('id'),
        ]);

        /*
         * The roster is paginated, not eager-loaded whole.
         *
         * A 400-student section loaded every enrollment and every student into memory to render one
         * screen — and the withdraw dialogs below were rendered one per active row, so the response
         * also carried 400 modals, each with its own form and textarea. That is the page's weight
         * growing linearly with the roster, for a table nobody reads past the first screenful of.
         *
         * `withoutGlobalScopes` is deliberately NOT used: the section was resolved through the
         * scoped binding, and the enrollments of a section the caller may see are theirs to see.
         */
        /*
         * FR-111 — a roster that came from a registrar file is shown in the registrar's order.
         *
         * Decided once per SECTION, not per page: deciding from the 25 rows on screen showed the
         * sequence column on page 1 and dropped it on page 2. When the section has a sequence, it
         * is the order: a withdrawn student keeps their place (their badge says withdrawn) rather
         * than being moved after number 40, which would no longer match the file in anyone's hand.
         * Unsequenced rows (added by hand) follow, in insertion order; id breaks every tie.
         *
         * Without a sequence, the earlier rule stands: active rows first, so the common read
         * ("who is here now") is the top of the list.
         */
        $sequenced = $section->enrollments()->whereNotNull('roster_sequence')->exists();

        $roster = $section->enrollments()
            ->with('student')
            ->when(
                $sequenced,
                fn ($q) => $q->orderByRaw('case when roster_sequence is null then 1 else 0 end')->orderBy('roster_sequence'),
                fn ($q) => $q->orderByRaw("case when status = 'withdrawn' then 1 else 0 end"),
            )
            ->orderBy('id')
            ->paginate(self::ROSTER_PER_PAGE, ['*'], 'roster')
            ->withQueryString();

        return view('sections.show', [
            'section' => $section,
            'roster' => $roster,
            'sequenced' => $sequenced,

            /*
             * T498 — FR-179: the creator of a returned section corrects it in a dialog. The options are
             * built only for them, so every other viewer of the page pays nothing for them.
             */
            'resubmitOptions' => $section->status === SectionStatus::Returned->value && ($request->user()?->can('resubmit', $section) ?? false)
                ? array_intersect_key($this->createOptions($request), array_flip(['courseOptions', 'programOptions']))
                : null,

            /*
             * Counted separately, and deliberately not $roster->total(): the roster deliberately
             * includes withdrawn rows, while "enrolled" on the metadata panel means active. Reusing
             * the paginator's total would quietly inflate the headline number by every student who
             * ever left.
             */
            'activeEnrollments' => $section->enrollments()->where('status', 'active')->count(),

            /*
             * The assignable instructors, resolved here rather than in the dialog partial: it is a
             * permission query, and a permission query in a template is one query per render of
             * every page that includes it.
             *
             * Keyed by id, valued by display name, so the <select> needs no further mapping and the
             * posted value is the foreign key the action expects.
             */
            'instructors' => $request->user()?->can('changeInstructor', Section::class)
                ? User::query()
                    /*
                     * By role, not by permission. `section.view` is held by the coordinator and the
                     * admin too, so a permission query would offer the whole staff list as
                     * candidate instructors — and assigning a section to an admin is not a thing
                     * anyone means to do.
                     */
                    ->role('INSTRUCTOR')
                    ->orderBy('name_ar')
                    ->get()
                    ->mapWithKeys(fn (User $user) => [
                        $user->getKey() => $user->{'name_'.app()->getLocale()} ?? $user->name_ar,
                    ])
                    ->all()
                : [],
        ]);
    }

    /**
     * T190 — create. The request object carries the uniqueness rule and the FR-037 refusal.
     */
    public function store(StoreSectionRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $creator */
        $creator = $request->user();

        // T488 + T494: the creator's role decides the status, and an attached roster goes through the
        // existing section import mode. Both inside CreateSection, in one transaction.
        $result = app(CreateSection::class)->execute(
            $creator,
            array_intersect_key($request->validated(), array_flip((new Section)->getFillable())),
            $request->file('roster'),
        );

        return $this->respond($request, [
            'data' => $this->present($result['section']->load(['term', 'branch', 'course', 'program', 'instructor'])) + [
                'import_job_id' => $result['import_job_id'],
            ],
        ], 201);
    }

    /**
     * T190 — edit. The same uniqueness rule applies to the resulting combination.
     */
    public function update(StoreSectionRequest $request, Section $section): JsonResponse|RedirectResponse
    {
        $section->update($request->validated());

        return $this->respond($request, [
            'data' => $this->present($section->fresh()->load(['term', 'branch', 'course', 'program', 'instructor'])),
        ]);
    }

    /**
     * PATCH /sections/{id}/instructor — access moves, authorship does not (FR-048).
     */
    public function changeInstructor(Request $request, Section $section): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'instructor_id' => ['present', 'nullable', 'integer', 'exists:users,id'],
        ]);

        $instructor = $validated['instructor_id'] === null
            ? null
            : User::query()->findOrFail($validated['instructor_id']);

        $updated = app(ChangeSectionInstructor::class)->execute($section, $instructor);

        return $this->respond($request, [
            'data' => $this->present($updated->load(['term', 'branch', 'course', 'program', 'instructor'])),
        ]);
    }

    /**
     * FR-023 — the export is the same query as the list.
     *
     * Streamed rather than assembled in memory: a Super Admin's export is every section in the
     * college, and `chunk()` over a stream keeps the memory cost flat whether that is forty rows or
     * forty thousand.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with(['branch', 'course', 'instructor']);

        /*
         * SEC-061 (G-07): the audit and error-report exports are audited; this one carried the
         * same data out unrecorded. The filters are kept (names and values only, no rows), so the
         * entry says what was taken, not just that something was.
         */
        app(Auditor::class)->record('section.exported', null, [], [
            'filters' => $request->only(['term_id', 'branch_id', 'course_id', 'status', 'search', 'instructor_id']),
            'rows' => (clone $query)->count(),
        ]);

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            // BOM first: Excel reads a UTF-8 CSV as Windows-1252 without it, and every Arabic name
            // in the file arrives as mojibake.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['section_number', 'course', 'branch', 'instructor', 'status']);

            // SEC-060: every cell defused — a section number or name beginning with = runs as a formula.

            $query->orderBy('id')->chunk(500, function ($chunk) use ($handle): void {
                foreach ($chunk as $section) {
                    fputcsv($handle, CsvSafe::row([
                        $section->section_number,
                        $section->course?->code,
                        $section->branch?->campus_name_ar,
                        $section->instructor?->name_ar,
                        $section->status,
                    ]));
                }
            });

            fclose($handle);
        }, 'sections.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Section>
     */
    private function filtered(Request $request)
    {
        return Section::query()
            ->when($request->filled('term_id'), fn ($q) => $q->where('term_id', $request->integer('term_id')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('instructor_id'), fn ($q) => $q->where('instructor_id', $request->integer('instructor_id')))
            ->when($request->filled('q'), fn ($q) => $q->where('section_number', 'like', '%'.$request->string('q')->toString().'%'))
            /*
             * T494 — FR-181, FR-183. A rejected section is out of every default list. A pending or
             * returned one is in, and badged, so a creator sees their own without a separate screen.
             * `status=rejected` asks for the rejected ones, and nothing else does. An unknown status
             * matches nothing rather than everything.
             */
            ->when(
                $request->filled('status'),
                // An array (`status[]=`) or an unknown value matches nothing, never everything, and never a 500.
                fn ($q) => $q->where('status', is_string($request->input('status')) && SectionStatus::tryFrom($request->input('status')) instanceof SectionStatus ? $request->input('status') : '__none__'),
                fn ($q) => $q->where('status', '!=', SectionStatus::Rejected->value),
            )
            ->when($request->filled('created_by'), fn ($q) => $q->where('created_by', $request->integer('created_by')));
    }

    /**
     * Clamped. An unbounded `per_page` is a full-table load one query string away, and a negative
     * one reaches the database as `LIMIT -1`.
     */
    /**
     * T199 — the options behind the filter row.
     *
     * Every list is derived from the caller's own scoped sections rather than from the four tables
     * directly. That is deliberate and it is the whole safety argument: a filter whose options come
     * from the same scoped query can never offer a term, branch, course or instructor the caller
     * cannot see, and it needs no second copy of the scope rule to stay that way. Reading `branches`
     * directly here would also have to re-derive the coordinator's claim — the recursion that cost
     * 512 MB in US2 (see BranchController's class note).
     *
     * `has_multiple_branches` decides whether the branch filter is rendered at all. For an
     * instructor the answer is no: a select with one option is a control that cannot do anything.
     *
     * @return array{terms: array<int, string>, branches: array<int, string>, courses: array<int, string>, programs: array<int, string>, instructors: array<int, string>, has_multiple_branches: bool, scoped_by_assignment: bool}
     */
    private function filterOptions(Request $request): array
    {
        $locale = app()->getLocale();

        /*
         * DISTINCT ids per column, then one lookup each.
         *
         * The first version hydrated every visible section with four relations eager-loaded and
         * reduced them in PHP. That is 26 queries for a ten-row page and, worse, memory and CPU
         * proportional to the caller's entire section count — a Super Admin at ten thousand
         * sections pays for all of them to render a filter row with four selects in it.
         *
         * `distinct()->pluck()` on the scoped builder keeps the scope (it is still Section::query())
         * while returning only as many ids as there are distinct terms, branches, courses and
         * instructors. That is bounded by the institution's structure rather than by its data, and
         * it is the number of <option> elements the page was always going to render anyway.
         */
        $distinct = static fn (string $column): array => Section::query()
            ->whereNotNull($column)
            ->distinct()
            ->pluck($column)
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $named = static function (string $model, array $ids, string $attribute, string $fallback): array {
            if ($ids === []) {
                return [];
            }

            // Three columns through the base builder, not hydrated models: an option is an id and a
            // label, and this runs on every sections page load for every caller.
            return $model::query()
                ->whereIn('id', $ids)
                ->toBase()
                ->get(array_unique(['id', $attribute, $fallback]))
                ->mapWithKeys(static fn (object $row): array => [$row->id => $row->{$attribute} ?? $row->{$fallback}])
                ->sort()
                ->all();
        };

        $branches = $named(Branch::class, $distinct('branch_id'), 'campus_name_'.$locale, 'campus_name_ar');

        return [
            // "2026/2027 - الأول" (design-system.md §12): a term as the context of a section.
            'terms' => AcademicTerm::query()->whereIn('id', $distinct('term_id'))->orderByDesc('start_date')->get()
                ->mapWithKeys(static fn (AcademicTerm $term): array => [$term->id => Format::term($term)])
                ->all(),
            'branches' => $branches,
            'courses' => $named(Course::class, $distinct('course_id'), 'code', 'code'),
            // FR-094. From the caller's own sections like the rest, so a coordinator is offered the
            // programmes their branches teach, not every programme in the college.
            'programs' => $named(Program::class, $distinct('program_id'), 'name_'.$locale, 'name_ar'),
            'instructors' => $named(User::class, $distinct('instructor_id'), 'name_'.$locale, 'name_ar'),

            /*
             * Whether the branch select is worth rendering. Separate from the empty-state wording,
             * which this used to decide as well — so a Super Admin with no sections at all was told
             * "no sections have been assigned to you yet", which is not their situation and names a
             * remedy they do not need.
             */
            'has_multiple_branches' => count($branches) > 1,

            /*
             * Whose reach is purely by assignment. A property of the scope system, not a role name:
             * no bypass and no branch claim means every section this person sees, they see because
             * it was given to them — and "ask for an assignment" is then the right thing to say.
             */
            'scoped_by_assignment' => $request->user() !== null
                && ! $request->user()->can('scope.bypass_all')
                && $request->user()->coordinatorBranchIds() === [],
        ];
    }

    /**
     * The selects behind the create dialog, which are a different question from the filter row.
     *
     * A filter may only offer what the caller already sees; a create form must offer everything the
     * caller may legitimately create, including a term with no sections yet — which is every term
     * on the day it is made, so deriving these from existing sections would make the first section
     * of a term impossible to add.
     *
     * @return array{termOptions: array<int, string>, branchOptions: array<int, string>, courseOptions: array<int, string>, programOptions: array<int, string>, instructorOptions: array<int, string>, currentTermId: int|null}
     */
    private function createOptions(Request $request): array
    {
        if (! ($request->user()?->can('create', Section::class) ?? false)) {
            return [
                'termOptions' => [], 'branchOptions' => [], 'courseOptions' => [],
                'programOptions' => [], 'instructorOptions' => [], 'currentTermId' => null,
            ];
        }

        $locale = app()->getLocale();
        $organizationId = $request->user()->organization_id;

        /*
         * Every list is the caller's organization's, none of these models carries an organization
         * scope of its own. Unfiltered, a Super Admin of one college was offered every other
         * college's course codes, campuses and instructors' names, a cross-tenant leak of staff
         * names, and StoreSectionRequest then refused whichever foreign one they picked.
         *
         * pluck(), not get(): two columns per row rather than a hydrated model, so the cost of
         * rendering the dialog is the size of the option list and nothing else.
         */
        $named = static fn (string $model, string $attribute, ?callable $filter = null): array => $model::query()
            ->where('organization_id', $organizationId)
            ->when($filter !== null, $filter)
            ->orderBy($attribute)
            ->pluck($attribute, 'id')
            ->all();

        return [
            // Archived terms excluded: a section cannot be created into a term that has been closed
            // out, and offering it would produce a 409 the registrar could not have predicted.
            'termOptions' => AcademicTerm::query()->whereIn('status', ['draft', 'active'])->orderByDesc('start_date')->get()
                ->mapWithKeys(static fn (AcademicTerm $term): array => [$term->id => Format::term($term)])
                ->all(),

            'branchOptions' => $named(Branch::class, 'campus_name_'.$locale,
                fn ($q) => $q->where('is_active', true)),

            'courseOptions' => $named(Course::class, 'code'),
            // The caller's organization's active programmes only — the same set StoreSectionRequest
            // accepts (FR-092: a deactivated programme is hidden from new choices).
            // "HR · الموارد البشرية", as the reference labels them: the code is what a registrar's
            // file names, the name is what a person recognises.
            'programOptions' => Program::query()
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderBy('code')
                ->toBase()
                ->get(['id', 'code', 'name_ar', 'name_'.$locale.' as name'])
                ->mapWithKeys(static fn (object $p): array => [$p->id => $p->code.' · '.($p->name ?? $p->name_ar)])
                ->all(),

            // Active ones only: StoreSectionRequest refuses an inactive or foreign instructor.
            'instructorOptions' => User::query()->role('INSTRUCTOR')
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderBy('name_ar')
                ->toBase()
                ->get(['users.id', 'name_ar', 'name_'.$locale.' as name'])
                ->mapWithKeys(static fn (object $u): array => [$u->id => $u->name ?? $u->name_ar])
                ->all(),

            // Preselected, because the current term is the one a registrar means nine times out of
            // ten and the single-current invariant guarantees there is at most one.
            'currentTermId' => AcademicTerm::query()->where('is_current', true)->value('id'),
        ];
    }

    private function perPage(Request $request): int
    {
        return max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Section $section): array
    {
        return [
            'id' => $section->getKey(),
            'section_number' => $section->section_number,
            'term' => $section->term?->only(['id', 'code']),
            'branch' => $section->branch === null ? null : [
                'id' => $section->branch->id,
                'campus_name_ar' => $section->branch->campus_name_ar,
                'campus_name_en' => $section->branch->campus_name_en,

                // FR-050: a section's gender is its branch's, never its own column.
                'gender' => $section->branch->gender,
            ],
            'course' => $section->course?->only(['id', 'code', 'name_ar']),
            'program' => $section->program?->only(['id', 'code', 'name_ar', 'name_en']),
            'degree' => $section->degree,
            'activity' => $section->activity,
            'instructor' => $section->instructor === null ? null : [
                'id' => $section->instructor->id,
                'name_ar' => $section->instructor->name_ar,
                'name_en' => $section->instructor->name_en,
            ],
            'active_enrollments' => (int) ($section->active_enrollments ?? 0),
            'status' => $section->status,

            // Amendment 4 — the approval state and who it waits on (contracts/academic.md).
            'requires_approval' => (bool) $section->requires_approval,
            'created_by' => $section->created_by,
            'review_reason' => $section->review_reason,

            // FR-026 — per caller, not per row. See Section::sharedAccess().
            'shared_access' => $section->shared_access,
        ];
    }
}
