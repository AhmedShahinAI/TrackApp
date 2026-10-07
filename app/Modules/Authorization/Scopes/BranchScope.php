<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Scopes;

use App\Models\User;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * T144 — the coordinator's branch constraint, applied to every query automatically (FR-022).
 *
 * A global scope rather than a `where` in each controller, because constitution I makes the leak
 * the default failure mode: every endpoint written next month inherits this, and an author who has
 * never read this file still cannot write a query that crosses a branch boundary.
 *
 * ## Two inert conditions
 *
 * **No authenticated user.** Console commands, queue workers, and seeders have no ambient actor,
 * and a scope that resolved to "no branches" there would silently empty every maintenance query.
 * A queued job that must stay scoped passes an explicit actor instead of relying on the request.
 *
 * **`scope.bypass_all`.** The Super Admin's unscoped view is a *permission*, not a role name
 * (FR-020). NewRoleNeedsNoCodeChangeTest invents a role carrying this permission and asserts it
 * sees the whole college, which is what keeps this from drifting back into `hasRole('SUPER_ADMIN')`.
 *
 * ## The third condition, and why it is not "inert when empty"
 *
 * A coordinator with no branches assigned sees **nothing**, not everything. `whereIn('x', [])` is a
 * false predicate, which is the behaviour wanted — and an implementation that skipped the
 * constraint to avoid a pointless query would hand that caller the entire college. The test for it
 * exists because the optimisation is tempting and the failure is invisible.
 *
 * What *is* inert: this scope steps aside for a caller whose claim is section-based rather than
 * branch-based — an instructor holds no branches, and constraining them to an empty branch list
 * would hide the sections they teach. See the note on dual claims in {@see SectionScope}.
 *
 * @implements Scope<Model>
 */
final class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! $model instanceof ConstrainableToBranches) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User || $user->can('scope.bypass_all')) {
            return;
        }

        $branchIds = $user->coordinatorBranchIds();

        // A section-based caller is governed by SectionScope; see that class for the division.
        if ($branchIds === [] && $user->accessibleSectionIds() !== []) {
            return;
        }

        $model->constrainToBranches($builder, $branchIds);
    }
}
