<?php

declare(strict_types=1);

namespace App\Modules\AcademicStructure\Policies;

use App\Models\User;

/**
 * T195 — courses.
 *
 * One method per action, each mapping to its named permission (FR-019, constitution VIII).
 *
 * The policy answers only the *permission* question. Whether the caller may see the record at all
 * is the global scopes' job, and answering it here would produce 403 where the constitution
 * requires 404 — a 403 confirms the record exists, which is how a coordinator enumerates another
 * branch's ids (research.md R-01).
 */
final class CoursePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('course.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('course.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('course.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('course.edit');
    }
}
