<?php

declare(strict_types=1);

namespace App\Modules\Students\Policies;

use App\Models\User;

/**
 * T195 — students.
 *
 * One method per action, each mapping to its named permission (FR-019, constitution VIII).
 *
 * The policy answers only the *permission* question. Whether the caller may see the record at all
 * is the global scopes' job, and answering it here would produce 403 where the constitution
 * requires 404 — a 403 confirms the record exists, which is how a coordinator enumerates another
 * branch's ids (research.md R-01).
 */
final class StudentPolicy
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
        return $actor->can('student.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('student.edit');
    }
}
