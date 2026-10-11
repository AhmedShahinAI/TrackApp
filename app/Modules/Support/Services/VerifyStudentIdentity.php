<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

use App\Models\SectionEnrollment;
use App\Modules\Shared\Rules\UniversityId;

/**
 * T368 — is this a student enrolled now? One query, one answer (research.md R-10, FR-118, FR-119).
 *
 * Three different facts make the answer "no": the identifier is not on the roster, it is on the
 * roster with no enrollment in the current term, or its only enrollment this term is withdrawn. They
 * must be indistinguishable to the person asking, because telling them apart enumerates the roster
 * one identifier at a time. So there is **one** query that can only find a row or not, and nothing
 * here — no return value, no exception, no log line — says which of the three it was.
 *
 * {@see resolve()} returns the match or null; {@see verify()} is the boolean. A caller may branch on
 * presence and on nothing else. The match carries the student and the term because the action that
 * records a request (T390) needs both, and a second query to find them would be a second timing
 * path the response-time floor (T372) then has to cover.
 *
 * The query starts from the enrollment and joins outward, so every condition is a join or a filter
 * on one statement:
 *
 *     section_enrollments (active)
 *       → students (this identifier, not deleted)
 *       → sections → academic_terms (current, not deleted)
 *
 * The global scopes are dropped explicitly. They are inert on a public request already — there is no
 * authenticated caller — but a verification that silently narrowed when a staff member happened to be
 * signed in on the same browser would answer a different question.
 */
final class VerifyStudentIdentity
{
    public function verify(string $universityId): bool
    {
        return $this->resolve($universityId) !== null;
    }

    public function resolve(string $universityId): ?VerifiedStudent
    {
        $row = SectionEnrollment::query()
            ->withoutGlobalScopes()
            ->join('students', 'students.id', '=', 'section_enrollments.student_id')
            ->join('sections', 'sections.id', '=', 'section_enrollments.section_id')
            ->join('academic_terms', 'academic_terms.id', '=', 'sections.term_id')
            ->where('students.university_id', UniversityId::normalise($universityId))
            ->whereNull('students.deleted_at')
            ->where('section_enrollments.status', 'active')
            ->where('academic_terms.is_current', true)
            ->whereNull('academic_terms.deleted_at')
            ->select(['students.id as verified_student_id', 'academic_terms.id as verified_term_id'])
            ->toBase()
            ->first();

        if ($row === null) {
            return null;
        }

        return new VerifiedStudent((int) $row->verified_student_id, (int) $row->verified_term_id);
    }
}
