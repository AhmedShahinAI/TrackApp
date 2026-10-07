<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Http\Controllers;

use App\Models\User;
use App\Modules\Authorization\Actions\AssignRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * T151 — PUT /api/v1/users/{user}/roles. Permission `user.assign_role`.
 *
 * The roles must already exist as rows. `Rule::exists` against the roles table rather than a
 * hard-coded `Rule::in([...])` of the three seeded names, because a fourth role is a seeded row and
 * nothing else (FR-020) — an `in` list here would be the code change that requirement forbids.
 */
final class UserRoleController extends Controller
{
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        /** @var list<string> $roles */
        $roles = $validated['roles'];

        $updated = app(AssignRoles::class)->execute($user, $roles);

        return response()->json([
            'data' => [
                'id' => $updated->getKey(),
                'roles' => $updated->roles->pluck('name')->sort()->values()->all(),
            ],
        ]);
    }
}
