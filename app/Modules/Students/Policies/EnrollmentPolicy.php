<?php

declare(strict_types=1);

namespace App\Modules\Students\Policies;

use App\Models\User;

/**
 * T195 — enrollments.
 *
 * One method per action, each mapping to its named permission (FR-019, constitution VIII).
 *
 * The policy answers only the *permission* question. Whether the caller may see the record at all
 * is the global scopes' job, and answering it here would produce 403 where the constitution
 * requires 404 — a 403 confirms the record exists, which is how a coordinator enumerates another
 * branch's ids (research.md R-01).
 */
final class EnrollmentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('student.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('student.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('enrollment.create');
    }

    /**
     * Withdrawal is its own permission: adding a student to a roster and removing one from it are different acts, and only the second needs a written reason.
     */
    public function withdraw(User $actor): bool
    {
        return $actor->can('enrollment.withdraw');
    }
}
