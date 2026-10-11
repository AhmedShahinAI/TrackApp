<?php

declare(strict_types=1);

namespace App\Modules\AcademicStructure\Policies;

use App\Models\User;

/**
 * T195 — branches.
 *
 * One method per action, each mapping to its named permission (FR-019, constitution VIII).
 *
 * The policy answers only the *permission* question. Whether the caller may see the record at all
 * is the global scopes' job, and answering it here would produce 403 where the constitution
 * requires 404 — a 403 confirms the record exists, which is how a coordinator enumerates another
 * branch's ids (research.md R-01).
 */
final class BranchPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('branch.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('branch.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('branch.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('branch.edit');
    }

    /**
     * Deactivation is an edit, not a deletion — there is no delete permission because there is no delete (FR-049).
     */
    public function deactivate(User $actor): bool
    {
        return $actor->can('branch.edit');
    }
}
