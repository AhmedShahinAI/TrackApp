<?php

declare(strict_types=1);

namespace App\Modules\UserManagement\Policies;

use App\Models\User;

/**
 * T125 — one method per action, each mapping to its named permission (constitution VIII, FR-019).
 *
 * The policy answers only the *permission* question. It never answers "can this caller see this
 * record", because that is the global scopes' job and answering it here would produce 403 where the
 * constitution requires 404 — a 403 confirms the record exists, which is how a coordinator
 * enumerates another branch's ids (research.md R-01).
 *
 * No `before()` granting the Super Admin everything. The Super Admin holds all 47 permissions from
 * the seeder, so a bypass hook would only hide a seeding mistake.
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('user.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('user.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('user.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('user.edit');
    }

    public function deactivate(User $actor, User $target): bool
    {
        // The self-deactivation refusal is a 409 from the controller, not a 403 here: the caller is
        // permitted to deactivate accounts, and this particular one is refused for what it would do.
        return $actor->can('user.deactivate');
    }

    public function resetPassword(User $actor): bool
    {
        return $actor->can('user.reset_password');
    }

    public function assignRole(User $actor): bool
    {
        return $actor->can('user.assign_role');
    }

    public function assignBranch(User $actor): bool
    {
        return $actor->can('user.assign_branch');
    }

    /**
     * Deletion is not a thing this platform does (constitution V, FR-049). Returning false rather
     * than omitting the method, so `$user->can('delete', $target)` is a definite no instead of
     * falling through to a gate that might be defined elsewhere later.
     */
    public function delete(User $actor, User $target): bool
    {
        return false;
    }

    public function forceDelete(User $actor, User $target): bool
    {
        return false;
    }
}
