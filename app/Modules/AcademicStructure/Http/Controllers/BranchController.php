<?php

declare(strict_types=1);

namespace App\Modules\AcademicStructure\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * T187 — branches, per contracts/academic.md.
 *
 * Adding a campus requires no code change (FR-038): three fields and a row. That is the whole test
 * of whether the structure is data or hard-coding.
 *
 * ## Deactivation is the only removal
 *
 * There is no DELETE here and there must not be. A branch holds sections, which hold enrollments,
 * which hold the history the platform exists to keep (FR-049, constitution V). `is_active = false`
 * takes it out of use and leaves every record that names it readable — NoHardDeleteTest fails the
 * build if a delete route for branches ever appears.
 *
 * ## Why the scope is written here rather than as a global scope
 *
 * The contract says a coordinator sees only their own branches, and Branch deliberately does *not*
 * carry BelongsToBranchScope. A global scope on Branch would be consulted while resolving
 * `coordinatorBranchIds()` — which reads the branches table — and the scope would call the method
 * that calls the scope. That recursion cost a 512 MB memory exhaustion in US2 and is the reason the
 * rule exists: a query that DEFINES a scope must never be subject to it.
 */
final class BranchController extends Controller
{
    use RespondsToBoth;

    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse|View
    {
        $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $branches = $this->visibleTo($request->user())
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->string('gender')->toString()))
            ->withCount('sections')

            /*
             * Students, counted DISTINCT. A student enrolled in three sections of the same branch
             * is one student, and a plain join would report three — the same mistake FR-043 names
             * for the students list, in a column nobody would think to check.
             *
             * Not a relation on Branch: there is no branch_id on students, and the path runs
             * branch → sections → enrollments → students. A HasManyThrough would still need the
             * DISTINCT, so the subquery states it instead of hiding it.
             */
            ->withCount(['sections as students_count' => fn ($query) => $query
                ->join('section_enrollments', 'section_enrollments.section_id', '=', 'sections.id')
                ->where('section_enrollments.status', 'active')
                ->select(DB::raw('count(distinct section_enrollments.student_id)')),
            ])
            ->orderBy('campus_name_ar')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $branches->getCollection()->map($this->present(...))->all(),
                'meta' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'total' => $branches->total(),
                ],
            ]);
        }

        return view('branches.index', ['branches' => $branches]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'campus_name_ar' => ['required', 'string', 'max:255'],
            'campus_name_en' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
        ]);

        $branch = Branch::query()->create($validated + [
            'organization_id' => $this->organizationId(),
            'is_active' => true,
        ]);

        return $this->respond($request, ['data' => $this->present($branch->loadCount('sections'))], 201);
    }

    public function update(Request $request, Branch $branch): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'campus_name_ar' => ['sometimes', 'string', 'max:255'],
            'campus_name_en' => ['sometimes', 'string', 'max:255'],
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        /*
         * $request->boolean(), not $validated['is_active'] === false.
         *
         * The `boolean` rule ACCEPTS "0", 0, "false" and false, but validate() hands back the value
         * as it arrived — so a form-encoded "0" compared identical to false is not false, and
         * deactivating a branch from the screen wrote `branch.updated` instead of
         * `branch.deactivated`. The audit then has no answer to the one question it is kept for:
         * when did this campus go out of service.
         */
        $wasActive = (bool) $branch->is_active;
        $deactivating = array_key_exists('is_active', $validated)
            && $wasActive
            && $request->boolean('is_active') === false;

        // Normalised so the write matches the decision just made above.
        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        if (! $deactivating) {
            $branch->update($validated);

            return $this->respond($request, ['data' => $this->present($branch->loadCount('sections'))]);
        }

        /*
         * Deactivation gets its own key, per the contract: "branch.updated, or branch.deactivated
         * when is_active flips to false". Letting the Auditable trait fire would record
         * `branch.updated` — true but useless, because the one question asked later is when the
         * campus went out of service, and a search for `branch.deactivated` would find nothing.
         *
         * saveQuietly suppresses the trait so exactly one entry is written (FR-028), which
         * ActionCoverageTest enforces as a count.
         */
        DB::transaction(function () use ($branch, $validated): void {
            $before = $branch->only(['campus_name_ar', 'campus_name_en', 'gender', 'is_active']);

            $branch->forceFill($validated)->saveQuietly();

            app(Auditor::class)->record('branch.deactivated', $branch, $before, $branch->only(array_keys($before)));
        });

        return $this->respond($request, ['data' => $this->present($branch->fresh()->loadCount('sections'))]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Branch>
     */
    private function visibleTo(?User $user)
    {
        $query = Branch::query();

        if ($user === null || $user->can('scope.bypass_all')) {
            return $query;
        }

        $branchIds = $user->coordinatorBranchIds();

        /*
         * An instructor holds no branches and is not branch-scoped: they see the campuses their own
         * sections sit in, which is what makes a section list readable. Returning nothing would hide
         * the branch column on their own roster.
         */
        if ($branchIds === []) {
            return $query->whereIn('id',
                \App\Models\Section::query()->withoutGlobalScopes()
                    ->whereIn('id', $user->accessibleSectionIds())
                    ->select('branch_id'),
            );
        }

        return $query->whereIn('id', $branchIds);
    }

    /**
     * The acting user's organization, not "the first college in the table".
     *
     * This read `Organization::where('type', 'college')->value('id')`, which is correct only while
     * exactly one organization exists. The moment a second is onboarded, every branch and course
     * created by anyone lands in whichever row the database returned first — silently, and in the
     * wrong tenant.
     *
     * The user carries `organization_id` already, so there is no reason to guess.
     */
    private function organizationId(): int
    {
        return (int) request()->user()?->organization_id;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Branch $branch): array
    {
        return [
            'id' => $branch->getKey(),
            'campus_name_ar' => $branch->campus_name_ar,
            'campus_name_en' => $branch->campus_name_en,
            'gender' => $branch->gender,
            'is_active' => (bool) $branch->is_active,
            'sections_count' => (int) ($branch->sections_count ?? 0),
            'students_count' => (int) ($branch->students_count ?? 0),
        ];
    }
}
