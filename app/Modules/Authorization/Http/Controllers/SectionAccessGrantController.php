<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Http\Controllers;

use App\Models\Section;
use App\Models\SectionAccessGrant;
use App\Models\User;
use App\Modules\Authorization\Actions\GrantSectionAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

/**
 * T149 — POST /api/v1/sections/{section}/access-grants (FR-026).
 *
 * The `{section}` parameter resolves through the scoped binding registered in RouteServiceProvider,
 * so a section the caller cannot see is a 404 here before any of this runs — which is why there is
 * no visibility check in the body. The permission is on the route.
 */
final class SectionAccessGrantController extends Controller
{
    public function store(Request $request, Section $section): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        /*
         * first(), not findOrFail().
         *
         * `exists:users,id` ignores the soft-delete scope, so a deleted account passed validation
         * and then findOrFail() threw — the caller got a 404 that reads as "no such section", plus a
         * spurious access.denied entry against a section they are perfectly entitled to. The body
         * was wrong, so the answer is 422.
         */
        $recipient = User::query()->find($validated['user_id']);

        $this->refuseUngrantableRecipient($recipient, $section);
        $this->refuseDuplicate($section, $recipient);

        $grant = app(GrantSectionAccess::class)->execute($section, $recipient);

        return response()->json(['data' => $this->present($grant)], 201);
    }

    /**
     * 422, not 403: the caller holds the permission, the *target* is wrong. A 403 here would tell
     * an administrator they lack an authority they actually have.
     */
    private function refuseUngrantableRecipient(?User $recipient, Section $section): void
    {
        /** @var list<string> $grantable */
        $grantable = config('authorization.grants.grantable_roles', []);

        /*
         * Deleted, deactivated, or the wrong kind of account — one message for all three.
         *
         * Distinguishing them would answer "does this id belong to a real person" for anyone holding
         * the grant permission, and a deactivated account is exactly the sort of thing an
         * administrator should have to look up rather than probe for.
         */
        // SEC-052 (G-15): and of the section's own organization — one message for that too.
        $sectionOrganization = $section->branch()->withoutGlobalScopes()->value('organization_id');

        $grantableTarget = $recipient !== null
            && $recipient->is_active
            && (int) $recipient->organization_id === (int) $sectionOrganization
            && $recipient->roles->pluck('name')->intersect($grantable)->isNotEmpty();

        if (! $grantableTarget) {
            throw ValidationException::withMessages([
                'user_id' => __('authorization.errors.not_grantable'),
            ]);
        }
    }

    private function refuseDuplicate(Section $section, User $recipient): void
    {
        $exists = SectionAccessGrant::query()
            ->where('section_id', $section->getKey())
            ->where('user_id', $recipient->getKey())
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'user_id' => __('authorization.errors.already_granted'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SectionAccessGrant $grant): array
    {
        return [
            'id' => $grant->getKey(),
            'section_id' => (int) $grant->section_id,
            'user_id' => (int) $grant->user_id,
            'granted_by' => (int) $grant->granted_by,
            'granted_at' => $grant->granted_at?->toIso8601String(),
        ];
    }
}
