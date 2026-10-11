<?php

declare(strict_types=1);

namespace App\Modules\Students\Actions;

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use App\Modules\Shared\Rules\UniversityId;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T193 — enrolling a student (FR-043, FR-045, SC-012).
 *
 * ## One person, one row, however many sections
 *
 * The university id is the person. Enrolling an id that already exists links the existing row; it
 * does not create a second student who happens to share a number. That is FR-043, and it is the
 * difference between "this student withdrew from one of their four sections" and four unrelated
 * people with the same id.
 *
 * The id is normalised before the lookup, so a registrar typing Arabic-Indic digits reaches the
 * same person as the registrar who typed Western ones — see UniversityId::normalise(), where
 * accepting both without normalising was found to create exactly the duplicate this prevents.
 *
 * ## A name mismatch is a warning, not an overwrite
 *
 * When the stored name differs from the one typed, the stored name wins and the caller is told.
 * The alternative — letting the newest spelling overwrite — means a typo in one enrollment renames
 * the person across every record they appear in, silently and retroactively. The registrar who
 * meant to correct the name can do it on the student screen, deliberately and auditably.
 *
 * @extends TransactionalAction<array{enrollment: SectionEnrollment, student: Student, warnings: list<string>}>
 */
final class EnrollStudent extends TransactionalAction
{
    /**
     * @return array{enrollment: SectionEnrollment, student: Student, warnings: list<string>}
     */
    protected function handle(mixed ...$arguments): array
    {
        /** @var Section $section */
        $section = $arguments[0];

        /** @var array{university_id: string, name_ar: string, name_en?: string|null} $attributes */
        $attributes = $arguments[1];

        $universityId = UniversityId::normalise((string) $attributes['university_id']);

        /*
         * withoutGlobalScopes, and this is the whole correctness of FR-043.
         *
         * Student carries the branch and section scopes, so a plain query answers "students I can
         * already see". But a student becomes visible *through* an enrollment — which is the thing
         * this action is about to create. Asking the scoped query first therefore returns null for
         * every student not yet in the caller's branch, the action creates a second row, and the
         * unique index on `university_id` rejects it with a 500.
         *
         * That is the same rule the scope layer was built on: a query that decides whether a record
         * may be *reached* must not be subject to the scope that record will acquire. The lookup is
         * by exact university id and returns nothing to the caller except through the enrollment
         * they are authorised to create, so nothing is disclosed that the next line does not grant.
         */
        $student = Student::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->where('university_id', $universityId)
            ->first();

        $warnings = [];

        if ($student === null) {
            $student = Student::query()->create([
                'university_id' => $universityId,
                'name_ar' => $attributes['name_ar'],
                'name_en' => $attributes['name_en'] ?? null,
            ]);
        } else {
            /*
             * The stored name stands. Reported rather than applied, so the person who typed a
             * different spelling sees that the record disagrees and can decide what to do about it.
             */
            if (trim($student->name_ar) !== trim($attributes['name_ar'])) {
                $warnings[] = __('students.warnings.name_mismatch', [
                    'stored' => $student->name_ar,
                    'given' => $attributes['name_ar'],
                ]);
            }

            if ($student->trashed()) {
                $student->restore();
                $warnings[] = __('students.warnings.restored');
            }
        }

        /*
         * Unscoped for the same reason as the student lookup above, and it is not redundant: a
         * withdrawn enrollment the caller cannot currently see is still a row the unique index
         * `(student_id, section_id)` will refuse. Scoped, this returns null and the insert below
         * becomes a second 500 on the same request.
         *
         * The section itself was already resolved through the scoped route binding, so the caller's
         * right to act on it is settled before this line runs.
         */
        $existing = SectionEnrollment::query()
            ->withoutGlobalScopes()
            ->where('section_id', $section->getKey())
            ->where('student_id', $student->getKey())
            ->first();

        if ($existing !== null && $existing->status === 'active') {
            throw new ConflictHttpException(__('students.errors.already_enrolled'));
        }

        if ($existing !== null) {
            /*
             * Re-enrolling after a withdrawal reopens the same row rather than adding a second one.
             * Two rows for one person in one section would double every roster count and make
             * "withdrew then returned" indistinguishable from "enrolled twice".
             */
            $before = $existing->only(['status', 'withdrawn_at', 'withdraw_reason']);

            /*
             * saveQuietly then an explicit `enrollment.created`.
             *
             * save() would let the trait write `enrollment.updated`, which is not in the contract's
             * closed list for this act and is also the wrong word: from the roster's point of view
             * the student is enrolled again, and someone searching the audit for how they came back
             * would find nothing under the action that put them there.
             */
            $existing->forceFill([
                'status' => 'active',
                'withdrawn_at' => null,
                'withdraw_reason' => null,
                'enrolled_at' => now(),
            ])->saveQuietly();

            app(Auditor::class)->record('enrollment.created', $existing, $before, [
                'status' => 'active',
                'withdrawn_at' => null,
                'withdraw_reason' => null,
            ]);

            return ['enrollment' => $existing->refresh(), 'student' => $student, 'warnings' => $warnings];
        }

        $enrollment = SectionEnrollment::query()->create([
            'section_id' => $section->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        /*
         * `student.created` comes from the Auditable trait when the row is new; `enrollment.created`
         * likewise. Both are in contracts/audit.md's closed list, and ActionCoverageTest counts
         * them — so nothing is recorded explicitly here, or there would be two of each.
         */
        return ['enrollment' => $enrollment, 'student' => $student, 'warnings' => $warnings];
    }
}
