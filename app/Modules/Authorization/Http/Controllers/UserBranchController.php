<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Http\Controllers;

use App\Models\User;
use App\Modules\Authorization\Actions\AssignBranches;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

/**
 * T151 — PUT /api/v1/users/{user}/branches. Permission `user.assign_branch`.
 *
 * Per contracts/auth-users.md, **422** when the target does not hold a branch-scoped role or when a
 * branch id does not exist. Both are 422 rather than 404 or 403 deliberately: the caller is
 * permitted and the user they named is visible, so the only thing wrong is the request body.
 */
final class UserBranchController extends Controller
{
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            /*
             * `present`, not `required`. `required` rejects [], which made revoking a coordinator's
             * last branch impossible through this endpoint: every attempt returned 422 and the
             * branch stayed. `present` still demands the field be sent — an absent key is a
             * malformed request — while allowing the empty list to mean what it says.
             */
            'branch_ids' => ['present', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $this->refuseNonCoordinator($user);

        /** @var list<int> $branchIds */
        $branchIds = $validated['branch_ids'];

        $updated = app(AssignBranches::class)->execute($user, $branchIds);

        return response()->json([
            'data' => [
                'id' => $updated->getKey(),
                'branch_ids' => $updated->coordinatorBranchIds(),
            ],
        ]);
    }

    private function refuseNonCoordinator(User $user): void
    {
        /** @var list<string> $branchScoped */
        $branchScoped = config('authorization.grants.branch_scoped_roles', []);

        if ($user->roles->pluck('name')->intersect($branchScoped)->isEmpty()) {
            throw ValidationException::withMessages([
                'branch_ids' => __('authorization.errors.not_branch_scoped'),
            ]);
        }
    }
}
