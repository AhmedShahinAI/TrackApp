<?php

declare(strict_types=1);

namespace App\Modules\Students\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use App\Modules\Shared\Rules\UniversityId;
use App\Modules\Students\Actions\EnrollStudent;
use App\Modules\Students\Actions\WithdrawEnrollment;
use App\Modules\Support\Enums\SupportRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Students, read-only, as far as US2 needs them.
 *
 * A student is the hardest of the five scoped models to get right, because they have no branch and
 * no section of their own — they are visible only through an enrollment. Student::constrainToBranches()
 * carries that logic; this controller is deliberately ignorant of it.
 *
 * Per contracts/academic.md, a student enrolled in several in-scope sections appears **once**
 * (FR-043). That is a property of the `whereExists` in the model rather than a `distinct()` here,
 * which matters because `distinct()` would have to be remembered by every future caller.
 */
final class StudentController extends Controller
{
    use RespondsToBoth;

    private const MAX_PER_PAGE = 100;

    // The section filter's options; past this a branch must be chosen first (Phase 26 review).
    private const SECTION_OPTIONS = 500;

    public function index(Request $request): JsonResponse|View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            // Digits only (either script), so the prefix match below can never carry a LIKE wildcard.
            'university_id' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\x{0660}-\x{0669}]+$/u'],
            'branch_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'integer'],
        ]);

        $currentSections = $this->currentSectionIds();
        $actor = $request->user();
        $forScreen = ! $request->expectsJson();

        // FR-142: an instructor holds no support.view, so the column is never even counted for one. And
        // only the screen shows it: the API's rows do not carry it (Phase 26 review).
        $seesRequests = $forScreen && $actor !== null && $actor->can('support.view');

        /*
         * A section from another branch than the one chosen matches nothing, and the select cannot show
         * it as chosen, so the list would be empty with nothing on screen to say why. It is dropped,
         * and the branch decides (Phase 26 review).
         */
        if (isset($filters['branch_id'], $filters['section_id'])
            && ! Section::query()->whereKey($filters['section_id'])->where('branch_id', $filters['branch_id'])->exists()) {
            unset($filters['section_id']);
        }

        $students = Student::query()
            ->when($request->filled('university_id'), fn ($q) => $q->where(
                'university_id', 'like', self::westernDigits($request->string('university_id')->trim()->toString()).'%',
            ))
            /*
             * T468: the search box takes a name or a University ID, as v2's StudentsAdminScreen does.
             *
             * All digits, in either script, is a University ID, and only its indexed prefix is searched.
             * OR-ing it with the names' leading-wildcard LIKEs would scan the table for every number
             * typed (Phase 26 review).
             *
             * A name never contains `%`, `_` or a backslash, so they are removed rather than escaped:
             * escaping would need an ESCAPE clause, which is raw SQL (constitution security floor), and
             * SQLite has no default escape character. A search of nothing but those characters matches
             * nobody, never everybody.
             */
            ->when($request->filled('q'), function ($q) use ($request): void {
                $typed = $request->string('q')->trim()->toString();
                $digits = self::westernDigits($typed);

                if (preg_match('/^\d+$/', $digits) === 1) {
                    $q->where('university_id', 'like', $digits.'%');

                    return;
                }

                $name = trim(str_replace(['%', '_', '\\'], '', $typed));

                if ($name === '') {
                    $q->whereKey([]);

                    return;
                }

                $q->where(fn ($w) => $w->where('name_ar', 'like', '%'.$name.'%')->orWhere('name_en', 'like', '%'.$name.'%'));
            })
            /*
             * T468 — the v2 filters: branch, section, programme. Each narrows to students holding a
             * current enrollment that matches, inside the caller's scope. SectionEnrollment carries the
             * scope, and the sections are the caller's current ones, so naming another branch's id
             * narrows to nothing rather than reaching it.
             */
            ->when(isset($filters['branch_id']) || isset($filters['section_id']) || isset($filters['program_id']),
                fn ($q) => $q->whereHas('enrollments', fn ($e) => $e->currentlyHeld()
                    ->whereIn('section_id', Section::query()
                        ->whereIn('id', $currentSections)
                        ->when(isset($filters['branch_id']), fn ($s) => $s->where('branch_id', $filters['branch_id']))
                        ->when(isset($filters['section_id']), fn ($s) => $s->whereKey($filters['section_id']))
                        ->when(isset($filters['program_id']), fn ($s) => $s->where('program_id', $filters['program_id']))
                        ->select('id'))))
            /*
             * T468 — what v2's columns are made of: the programme, degree and activity of each section
             * the student currently holds in the caller's scope this term. Loaded once for the page, so
             * the list costs the same number of queries for 25 rows as for 1. Only for the screen: the
             * API's rows are the student record alone.
             */
            ->when($forScreen, fn ($q) => $q->with(['enrollments' => fn ($e) => $e->currentlyHeld()->whereIn('section_id', $currentSections)
                ->select(['id', 'student_id', 'section_id'])
                ->with(['section' => fn ($s) => $s->select(['id', 'program_id', 'degree', 'activity'])
                    ->with('program:id,code,name_ar,name_en')])]))
            // "Open" is the domain's own rule (SupportRequestStatus::isOpen): Resolved still counts,
            // because it is not final until someone holding support.close closes it.
            ->when($seesRequests, fn ($q) => $q->withCount(['supportRequests as open_requests_count' => fn ($r) => $r->whereIn(
                'status',
                collect(SupportRequestStatus::cases())->filter(fn (SupportRequestStatus $status) => $status->isOpen())->map(fn (SupportRequestStatus $status) => $status->value)->values()->all(),
            )]))
            ->orderBy('name_ar')
            ->paginate(max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE)))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $students->getCollection()->map($this->present(...))->all(),
                'meta' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'total' => $students->total(),
                ],
            ]);
        }

        return view('students.index', [
            'students' => $students,
            'seesRequests' => $seesRequests,
            'filters' => $filters,
            'filterOptions' => $this->filterOptions($request, $currentSections, $filters),
        ]);
    }

    /** Arabic-Indic digits to Western, the form a University ID is stored in. */
    private static function westernDigits(string $value): string
    {
        return strtr($value, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }

    /**
     * The ids of the current term's sections, as a subquery. Scoped like everything else, so it is
     * already only the caller's.
     *
     * @return Builder<Section>
     */
    private function currentSectionIds(): Builder
    {
        // Approved sections only (Phase 27 review): the filters must offer what currentlyHeld() can
        // match, and a pending, returned or rejected section holds nobody's current enrollment.
        return Section::query()
            ->whereIn('term_id', AcademicTerm::query()->where('is_current', true)->select('id'))
            ->where('sections.status', 'active')
            ->select('sections.id');
    }

    /**
     * T468 — what the v2 filter bar offers. The branch filter appears only when the caller can see more
     * than one branch. The section list is the caller's current sections. For a college-wide caller it
     * waits for a branch to be chosen, because a select listing every section in the university is
     * not a filter anyone can use.
     *
     * @param  Builder<Section>  $currentSections
     * @param  array<string, mixed>  $filters
     * @return array{branches: array<array-key, string>, sections: array<array-key, string>, sections_truncated: bool, sections_cap: int, programs: array<array-key, string>}
     */
    private function filterOptions(Request $request, Builder $currentSections, array $filters): array
    {
        $locale = app()->getLocale();
        $collegeWide = (bool) $request->user()?->can('scope.bypass_all');

        $branches = Branch::query()
            ->whereIn('id', Section::query()->whereIn('id', $currentSections)->select('branch_id'))
            ->orderBy('campus_name_ar')
            ->get()
            // The campus and its gender, as the registrar's export writes it: a campus can hold both a male and
            // a female branch, so the name alone is ambiguous.
            ->mapWithKeys(fn (Branch $branch) => [(string) $branch->id => ($branch->{'campus_name_'.$locale} ?: $branch->campus_name_ar).' ('.__('branches.gender.'.$branch->gender).')'])
            ->all();

        $sections = ! $collegeWide || isset($filters['branch_id'])
            ? Section::query()
                ->whereIn('id', $currentSections)
                ->when(isset($filters['branch_id']), fn ($s) => $s->where('branch_id', $filters['branch_id']))
                ->with('course:id,code')
                ->orderBy('section_number')
                ->limit(self::SECTION_OPTIONS + 1)
                ->get(['id', 'course_id', 'section_number'])
                ->mapWithKeys(fn (Section $section) => [(string) $section->id => data_get($section, 'course.code', '—').' · '.$section->section_number])
                ->all()
            : [];

        // The programmes of the caller's current sections, not the whole catalogue: a coordinator offered a
        // programme taught only in another branch would pick it and see nothing (Phase 26 review).
        $programs = Program::query()
            ->whereIn('id', Section::query()->whereIn('id', $currentSections)->select('program_id'))
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->mapWithKeys(fn (Program $program) => [(string) $program->id => ($program->{'name_'.$locale} ?: $program->name_ar).' ('.$program->code.')'])
            ->all();

        // One more than the cap is fetched, so the screen can say the list was cut rather than hide it.
        $truncated = count($sections) > self::SECTION_OPTIONS;

        return [
            'branches' => count($branches) > 1 ? $branches : [],
            'sections' => array_slice($sections, 0, self::SECTION_OPTIONS, true),
            'sections_truncated' => $truncated,
            'sections_cap' => self::SECTION_OPTIONS,
            'programs' => $programs,
        ];
    }

    /**
     * 404 when the student exists but has no enrollment in the caller's scope — the binding does it,
     * so this method never has to know (FR-024).
     */
    public function show(Request $request, Student $student): JsonResponse|View
    {
        $student->load(['enrollments.section.course', 'enrollments.section.branch', 'enrollments.section.program']);

        if (! $request->expectsJson()) {
            return view('students.show', ['student' => $student]);
        }

        return response()->json([
            'data' => $this->present($student) + [
                'enrollments' => $student->enrollments->map(fn ($enrollment) => [
                    'id' => $enrollment->getKey(),
                    'section_id' => $enrollment->section_id,
                    'section_number' => $enrollment->section?->section_number,
                    'course' => $enrollment->section?->course?->code,
                    'branch' => $enrollment->section?->branch?->campus_name_ar,
                    'status' => $enrollment->status,
                    'enrolled_at' => $enrollment->enrolled_at?->toIso8601String(),
                ])->all(),

                // The initiative history the contract promises, empty in this feature by design.
                'initiatives' => [],
            ],
        ]);
    }

    /**
     * T193 — POST /sections/{section}/enrollments.
     */
    public function enroll(Request $request, Section $section): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'university_id' => ['required', new UniversityId],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $result = app(EnrollStudent::class)->execute($section, $validated);

        return $this->respond($request, [
            'data' => $this->present($result['student']) + [
                'enrollment_id' => $result['enrollment']->getKey(),
                'status' => $result['enrollment']->status,
            ],

            // Reported, not applied: a stored name that disagrees with the typed one is the
            // registrar's to resolve, not this endpoint's to overwrite.
            'warnings' => $result['warnings'],
        ], 201, [
            // The browser half of the same report. Without it the enroll dialog's warning alert is
            // markup that nothing ever populates, and a name mismatch is silently swallowed.
            'enroll_warnings' => $result['warnings'],
        ]);
    }

    /**
     * T194 — PATCH /enrollments/{enrollment}/withdraw. The reason is mandatory (FR-047).
     */
    public function withdraw(Request $request, SectionEnrollment $enrollment): JsonResponse|RedirectResponse
    {
        /*
         * Trimmed before `min:3`, so "   " is a 422 naming the field rather than a 409 from the
         * action. Both refuse it, but only one tells the registrar what to do about it, and a
         * reason of three spaces is a missing reason, not a conflicting state.
         */
        $request->merge(['reason' => trim((string) $request->input('reason'))]);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => __('students.errors.reason_required'),
        ]);

        $updated = app(WithdrawEnrollment::class)->execute($enrollment, $validated['reason']);

        return $this->respond($request, [
            'data' => [
                'id' => $updated->getKey(),
                'status' => $updated->status,
                'withdrawn_at' => $updated->withdrawn_at?->toIso8601String(),
                'withdraw_reason' => $updated->withdraw_reason,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Student $student): array
    {
        return [
            'id' => $student->getKey(),
            'university_id' => $student->university_id,
            'name_ar' => $student->name_ar,
            'name_en' => $student->name_en,
        ];
    }
}
