<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Actions;

use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * T151 — replace a user's roles (FR-027).
 *
 * ## The cache flush is the load-bearing line
 *
 * Spatie caches the permission-to-role map process-wide, for an hour by default. Without the flush
 * the database is correct and the application is wrong: the target keeps their old permissions
 * until the cache expires, with nothing in the logs and nothing to see in the data. It is also
 * invisible in local development, where almost every request is a fresh process — which is exactly
 * why FR-027 is written as a requirement and why
 * ScopeChangeTakesEffectImmediatelyTest drives it through two real requests.
 *
 * The flush is inside the transaction with the write. On a rollback the cache is merely cold, which
 * costs one query; the reverse order would leave a flushed cache repopulated from a state that was
 * about to disappear.
 *
 * @extends TransactionalAction<User>
 */
final class AssignRoles extends TransactionalAction
{
    protected function handle(mixed ...$arguments): User
    {
        /** @var User $user */
        $user = $arguments[0];

        /** @var list<string> $roles */
        $roles = $arguments[1];

        $previous = $user->roles->pluck('name')->sort()->values()->all();

        $this->refuseRemovingTheLastUnscopedRole($user, $roles);

        $user->syncRoles($roles);

        /*
         * Branch assignments follow the role out.
         *
         * coordinator_branches is a plain pivot, and BranchScope reads it without asking what role
         * the user holds — so a coordinator demoted to instructor kept whole-branch visibility over
         * every section, student, enrollment and audit entry in their old branches. Measured: three
         * sections before the demotion, three after. The role said instructor; the scope said
         * coordinator; the scope wins, because it is what the queries consult.
         */
        $branchScoped = (array) config('authorization.grants.branch_scoped_roles', []);

        if (array_intersect($roles, $branchScoped) === []) {
            $user->coordinatorBranches()->sync([]);
        }

        $user->forgetScopeCache();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(Auditor::class)->record('user.roles_changed', $user,
            ['roles' => $previous],
            ['roles' => collect($roles)->sort()->values()->all()],
        );

        return $user->refresh();
    }

    /**
     * Refuse the change that would leave nobody able to undo it.
     *
     * `scope.bypass_all` is the permission that sees the whole college; the accounts screen needs
     * `user.assign_role` to hand it back. Taking the last holder's role away locks the organisation
     * out of its own administration with no path back through the application — measured as zero
     * remaining holders, and the only recovery is a database edit.
     *
     * Checked by permission rather than by role name, so a fourth seeded role carrying
     * `scope.bypass_all` counts as a holder (FR-020).
     *
     * @param  list<string>  $roles
     */
    private function refuseRemovingTheLastUnscopedRole(User $user, array $roles): void
    {
        if (! $user->can('scope.bypass_all')) {
            return;
        }

        $retainsIt = Role::query()
            ->whereIn('name', $roles)
            ->with('permissions:id,name')
            ->get()
            ->contains(fn (Role $role): bool => $role->permissions->contains('name', 'scope.bypass_all'));

        if ($retainsIt) {
            return;
        }

        $otherHolders = User::query()
            ->whereKeyNot($user->getKey())
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $other): bool => $other->can('scope.bypass_all'))
            ->count();

        if ($otherHolders === 0) {
            throw ValidationException::withMessages([
                'roles' => __('authorization.errors.last_unscoped_role'),
            ]);
        }
    }
}
