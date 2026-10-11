<?php

declare(strict_types=1);

namespace App\Modules\AcademicStructure\Policies;

use App\Models\User;

/**
 * T315 — academic programmes (FR-089 to FR-095).
 *
 * One method per action, each mapping to its named permission (FR-019, constitution VIII). There is
 * no `delete`: FR-092 makes deactivation the only removal path, so there is no permission for it.
 *
 * Program carries no scope trait. It is institution-wide reference data with no branch dimension, so
 * a coordinator may read every programme of their organization while still seeing only their own
 * branches' sections inside it — the section counts come through Section's own scope (plan.md,
 * Amendment 1 re-check). The organization boundary is the controller's, as it is for courses.
 */
final class ProgramPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('program.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('program.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('program.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('program.edit');
    }

    /**
     * Deactivation is an edit, not a deletion (FR-092).
     */
    public function deactivate(User $actor): bool
    {
        return $actor->can('program.edit');
    }
}
