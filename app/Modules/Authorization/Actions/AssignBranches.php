<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Actions;

use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;

/**
 * T151 — replace a coordinator's branches (FR-027).
 *
 * No cache to flush here: branch membership is a pivot table read per request through
 * `coordinatorBranchIds()`, whose only cache is the per-instance memo that dies with the request.
 * The scope change therefore lands on the target's next request with nothing else to do — which is
 * the property ScopeChangeTakesEffectImmediatelyTest asserts in both directions, added and removed.
 *
 * The audit payload carries both id sets rather than a diff. A diff is smaller and answers a
 * narrower question: "what changed" is reconstructible from before-and-after, but "what did this
 * person have access to in March" is not reconstructible from a chain of diffs once any one entry
 * is pruned by retention.
 *
 * @extends TransactionalAction<User>
 */
final class AssignBranches extends TransactionalAction
{
    protected function handle(mixed ...$arguments): User
    {
        /** @var User $user */
        $user = $arguments[0];

        /** @var list<int> $branchIds */
        $branchIds = $arguments[1];

        $previous = $user->coordinatorBranchIds();

        $user->coordinatorBranches()->sync($branchIds);
        $user->forgetScopeCache();

        app(Auditor::class)->record('user.branches_changed', $user,
            ['branch_ids' => $previous],
            ['branch_ids' => collect($branchIds)->map(intval(...))->sort()->values()->all()],
        );

        return $user->refresh();
    }
}
