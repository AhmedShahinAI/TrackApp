<?php

declare(strict_types=1);

namespace App\Modules\Sections\Actions;

use App\Models\Section;
use App\Models\SectionInstructorHistory;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * T191 — reassigning a section (FR-048, SC-018).
 *
 * ## Access moves; authorship does not
 *
 * This is the whole requirement, and it is easy to get wrong in the obvious direction. Changing the
 * instructor is a change to who may *open* the section from now on. It is not a change to who
 * created the enrollments, who withdrew a student, or who wrote the reason — those entries name the
 * person who performed them, and they keep naming that person after the handover.
 *
 * Nothing in here touches another table's `created_by`, and ChangeInstructorTest asserts that by
 * reading every record attached to the section before and after.
 *
 * ## The history is a closed row and a new open one
 *
 * `section_instructor_history` is append-only in spirit: the outgoing row gains `unassigned_at` and
 * a new row opens. Overwriting the existing row instead would answer "who teaches it now" and lose
 * "who taught it in March", which is the question an investigation actually asks.
 *
 * @extends TransactionalAction<Section>
 */
final class ChangeSectionInstructor extends TransactionalAction
{
    protected function handle(mixed ...$arguments): Section
    {
        /** @var Section $section */
        $section = $arguments[0];

        /** @var User|null $instructor */
        $instructor = $arguments[1];

        /*
         * SEC-050 (G-08). The controller only knew the id existed. Access to a section's students is
         * what this hands over, so the recipient must be someone who may teach: an active INSTRUCTOR
         * of the section's own organization, the same rule the registrar import applies
         * (RegistrarImportController). Checked here, not only in the request, so every caller of the
         * action is held to it.
         */
        if ($instructor !== null && ! self::mayTeach($instructor, $section)) {
            throw ValidationException::withMessages(['instructor_id' => __('sections.errors.instructor_invalid')]);
        }

        $previousId = $section->instructor_id === null ? null : (int) $section->instructor_id;
        $newId = $instructor?->getKey();

        if ($previousId === $newId) {
            return $section;
        }

        /*
         * Close the open period first. There should be at most one — the schema does not enforce
         * that, so the update is written as a set operation rather than a single-row fetch: a
         * second open row left by an earlier bug would otherwise stay open forever and make the
         * history unreadable.
         */
        SectionInstructorHistory::query()
            ->where('section_id', $section->getKey())
            ->whereNull('unassigned_at')
            ->update(['unassigned_at' => now()]);

        if ($newId !== null) {
            SectionInstructorHistory::query()->create([
                'section_id' => $section->getKey(),
                'instructor_id' => $newId,
                'assigned_at' => now(),
                'unassigned_at' => null,

                /*
                 * Who made the change, which is a different person from who now teaches it. The
                 * column is NOT NULL for that reason: a handover with no named author is a handover
                 * nobody can ask about.
                 */
                'changed_by' => Auth::id(),
            ]);
        }

        // saveQuietly, then one explicit entry: the contract names `section.instructor_changed`, and
        // letting the trait also fire would write `section.updated` beside it for one occurrence.
        $section->forceFill(['instructor_id' => $newId])->saveQuietly();

        app(Auditor::class)->record('section.instructor_changed', $section,
            ['instructor_id' => $previousId],
            ['instructor_id' => $newId],
        );

        return $section->refresh();
    }

    /**
     * Active, of the organization the section's branch belongs to, and holding a role that may be
     * given a section's access — the same configured list SectionAccessGrantController uses
     * (authorization.grants.grantable_roles), so a new teaching role is data, not code (FR-020).
     */
    public static function mayTeach(User $user, Section $section): bool
    {
        $organizationId = $section->branch()->withoutGlobalScopes()->value('organization_id');

        return $user->is_active
            && (int) $user->organization_id === (int) $organizationId
            && $user->roles->pluck('name')->intersect(config('authorization.grants.grantable_roles', []))->isNotEmpty();
    }
}
