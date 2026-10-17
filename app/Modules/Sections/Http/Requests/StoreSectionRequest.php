<?php

declare(strict_types=1);

namespace App\Modules\Sections\Http\Requests;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Section;
use App\Models\User;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\InSetting;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T189 — creating a section (FR-037, FR-040, FR-041, FR-090, FR-108, FR-109).
 *
 * ## The index is the authority; this is the courtesy
 *
 * `(term_id, branch_id, course_id, approval_key)` is unique in the schema, where `approval_key` is a
 * stored generated column that is NULL for a rejected section — so rejected ones never collide and
 * everything else does. That index is what actually holds FR-041, because two registrars submitting
 * the same number in the same second both pass a `unique` rule and only one passes the index.
 *
 * This request exists so the ordinary case gets a sentence a person can act on rather than a 500
 * from the engine. SectionUniquenessTest asserts both layers separately, because a rule without the
 * index is a race and an index without the rule is an unreadable error.
 *
 * ## Degree and activity are settings, not enums in code
 *
 * `section_degrees` and `section_activities` are rows (constitution IV, FR-108/FR-109). A college
 * that adds "intermediate diploma" changes a setting; nothing here learns a new word.
 */
final class StoreSectionRequest extends FormRequest
{
    /**
     * Create and edit are different permissions, so this cannot be one check.
     *
     * The request object is shared between POST and PUT, and the first version asked for
     * `section.create` in both — which meant a registrar allowed to correct a section number was
     * refused, and anyone who could create one could rewrite every existing one. Which of the two
     * is being asked for is decided by whether the route bound a section, because that is the only
     * thing that distinguishes the two calls at this point.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        // T493 — a resubmission is the creator correcting their own returned section: `section.create`,
        // not `section.edit`, which an instructor does not hold. The creator check is the policy's.
        if ($this->routeIs('api.v1.sections.resubmit')) {
            return $user->can('section.create');
        }

        return $this->isUpdate() ? $user->can('section.edit') : $user->can('section.create');
    }

    /** Whether the route bound a section, which is the only thing separating a create from an edit. */
    private function isUpdate(): bool
    {
        return $this->route('section') instanceof Section;
    }

    /**
     * The branches this caller may create a section in.
     *
     * Deliberately not a global scope on Branch: Branch carries none, because resolving a
     * coordinator's claim reads the branches table and a scope there would call the method that
     * calls the scope — the recursion that cost 512 MB in US2. So the set is assembled explicitly,
     * from the same three sources the read side uses.
     *
     * @return list<int>
     */
    private function allowedBranchIds(): array
    {
        $user = $this->user();

        if ($user === null) {
            return [];
        }

        if ($user->can('scope.bypass_all')) {
            return Branch::query()
                ->where('organization_id', $user->organization_id)
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();
        }

        $branchIds = $user->coordinatorBranchIds();

        if ($branchIds !== []) {
            return $branchIds;
        }

        /*
         * An instructor holds `section.create` by design, and has no branch claim — their reach is
         * the branches of the sections they already teach. Without this they could create nothing,
         * which is a different bug from the one being fixed.
         */
        return Section::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $user->accessibleSectionIds())
            ->distinct()
            ->pluck('branch_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * FR-037 — a section cannot exist before a term does, and the refusal says so.
     *
     * 409 rather than 422: nothing in the payload is wrong. The institution has not been set up
     * yet, which is a different problem with a different fix, and a field-level error would send
     * the registrar looking at their own form.
     */
    protected function prepareForValidation(): void
    {
        if (! AcademicTerm::query()->exists()) {
            throw new ConflictHttpException(__('sections.errors.term_required_first'));
        }

        /*
         * contracts/academic.md: every field of PUT /sections/{id} is optional. An omitted field
         * keeps its stored value, filled in here rather than by loosening the rules to `sometimes`,
         * because the uniqueness rule is about the RESULTING combination: a PUT that changes only
         * term_id must still be checked against the section number it keeps, and a skipped rule
         * would not check it at all.
         *
         * Only an absent key is filled. An explicit `"program_id": null` is present, so it still
         * meets `required` and is refused (FR-090).
         */
        $section = $this->route('section');

        if ($section instanceof Section) {
            $kept = [];

            foreach (['term_id', 'branch_id', 'course_id', 'program_id', 'section_number'] as $field) {
                if ($this->missing($field)) {
                    $kept[$field] = $section->getAttribute($field);
                }
            }

            $this->merge($kept);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // T494 — FR-175: a roster may come with the section at creation, and only then. Its size,
            // type and content are the import pipeline's to check (FileValidator), as for any upload.
            'roster' => $this->isUpdate() ? ['prohibited'] : ['nullable', 'file'],

            'term_id' => ['required', 'integer', 'exists:academic_terms,id'],

            /*
             * `exists` alone proved too little. It asks whether the branch exists anywhere, and the
             * answer is yes for every campus in the university — so a coordinator, or an instructor
             * (who holds `section.create` by design), could post any `branch_id` and create a
             * section in a campus they cannot see. The read side is scoped by BranchScope; the
             * write side had nothing.
             *
             * `allowedBranchIds()` is the caller's own set, so the rule refuses before the row
             * exists rather than hiding it afterwards.
             */
            'branch_id' => ['required', 'integer', Rule::in($this->allowedBranchIds())],

            /*
             * Courses are university-wide by design, but they belong to an organization. Scoped to
             * the caller's own so a section cannot be attached to another tenant's course.
             */
            'course_id' => [
                'required', 'integer',
                Rule::exists('courses', 'id')->where('organization_id', $this->user()?->organization_id),
            ],

            /*
             * Required since Amendment 1 (FR-090). A section with no programme cannot be reported on.
             *
             * Scoped like course_id: a bare `exists` accepted another tenant's programme. And only an
             * active, undeleted one (FR-092: deactivation hides it from new choices) — except the
             * programme an edited section already has, so a section whose programme was deactivated
             * since can still be edited without being forced to move.
             */
            'program_id' => [
                'required', 'integer',
                Rule::exists('programs', 'id')
                    ->where('organization_id', $this->user()?->organization_id)
                    ->where(fn ($query) => $query
                        ->where(fn ($open) => $open->where('is_active', true)->whereNull('deleted_at'))
                        ->when($this->isUpdate(), fn ($kept) => $kept->orWhere('id', $this->route('section')->program_id))),
            ],

            'section_number' => [
                'required', 'string', 'max:20',
                $this->uniqueCombination(),
            ],

            /*
             * Accepted on create, refused on edit.
             *
             * `$section->update($request->validated())` mass-assigned this, so PUT moved a section
             * to a different instructor while bypassing ChangeSectionInstructor entirely: no
             * history row, no `section.instructor_changed`, access transferred with nothing
             * recorded. It also let `section.edit` do the work of `section.change_instructor`,
             * which is a separate permission precisely because it is a different act.
             *
             * `prohibited` rather than silently stripped: dropping a submitted field leaves the
             * caller believing the reassignment happened.
             */
            'instructor_id' => $this->isUpdate()
                ? ['prohibited']
                /*
                 * An instructor of this organization, not any user row. Unconstrained, a section
                 * could be assigned to an administrator, to a deactivated account, or to somebody
                 * in another tenant — and `exists:users,id` says yes to all three.
                 */
                : ['nullable', 'integer', Rule::exists('users', 'id')
                    ->where('organization_id', $this->user()?->organization_id)
                    ->where('is_active', true),
                    /*
                     * SEC-051 (G-14). Active and in the organization was not enough: the named user
                     * gains access to the pending section, so it must hold a teaching role. An
                     * instructor naming a COLLEAGUE stays allowed: Amendment 4 designs exactly that
                     * (a section created for someone else, approved by a coordinator before it
                     * counts; ApprovalReviewRegressionsTest 1), and the approval is the control.
                     */
                    function (string $attribute, mixed $value, Closure $fail): void {
                        // The configured teaching roles (authorization.grants.grantable_roles), as the grant endpoint uses.
                        if (! User::query()->role(config('authorization.grants.grantable_roles', []))->whereKey((int) $value)->exists()) {
                            $fail(__('sections.errors.instructor_invalid'));
                        }
                    }],
            // Settings::values() already exists for exactly this: the permitted machine values of
            // a settings list, which is what a validation rule compares against.
            // T304 — InSetting, the one rule the section form and the imports share for these lists.
            'degree' => ['nullable', new InSetting(Settings::SECTION_DEGREES)],
            'activity' => ['nullable', new InSetting(Settings::SECTION_ACTIVITIES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'section_number.unique' => __('sections.errors.duplicate_key'),
            'instructor_id.prohibited' => __('sections.errors.instructor_via_own_endpoint'),
            'program_id.required' => __('sections.errors.program_required'),
            'program_id.exists' => __('sections.errors.program_required'),
        ];
    }

    /**
     * The same four columns the index covers.
     *
     * Rejected sections are excluded here as they are there: `approval_key` is NULL for them, and
     * NULLs do not collide in a unique index, so a number freed by a rejection is reusable.
     */
    private function uniqueCombination(): Unique
    {
        return Rule::unique('sections', 'section_number')
            ->where('term_id', $this->integer('term_id'))
            ->where('branch_id', $this->integer('branch_id'))
            ->where('course_id', $this->integer('course_id'))
            ->where(fn ($query) => $query->where('status', '!=', 'rejected'))
            ->ignore($this->route('section') instanceof Section ? $this->route('section')->getKey() : null);
    }
}
