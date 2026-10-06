<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * T118 — GET /auth/me (FR-027).
 *
 * Computed on every request, with nothing cached. That is the requirement, not an oversight: FR-027
 * says a role, branch, or section-access change "MUST take effect on the person's very next
 * request". Caching the permission set — the obvious optimisation — is precisely what would let a
 * revoked coordinator keep seeing a branch until their cache entry expired.
 *
 * The cost is bounded: spatie caches the permission *table* (24h, config/permission.php), so this
 * resolves the user's own roles and the two scope id lists. coordinatorBranchIds() and
 * accessibleSectionIds() are memoized per User instance, so repeated reads in one request are free.
 */
final class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name_ar' => $user->name_ar,
                'name_en' => $user->name_en,
                'email' => $user->email,
                'locale' => $user->locale,
                'roles' => $user->getRoleNames()->all(),
                'permissions' => $user->getAllPermissions()->pluck('name')->all(),
                'scope' => [
                    'branch_ids' => $user->coordinatorBranchIds(),
                    'section_ids' => $user->accessibleSectionIds(),
                ],
            ],
        ]);
    }
}
