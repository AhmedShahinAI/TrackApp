<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Models\SupportRequest;
use App\Models\User;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use App\Modules\Support\Actions\RespondToSupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * T415 — POST /api/v1/support-requests/{reference}/responses (contracts/support-staff.md).
 *
 * The body is held to the same configured maximum as the student's description
 * (`support_description_max`): one setting for "how long a message in this channel may be", rather
 * than a second one nobody would ever set differently. Stored raw, escaped on every output (FR-153).
 */
final class SupportResponseController
{
    use RespondsToBoth;

    public function store(Request $request, SupportRequest $supportRequest, RespondToSupportRequest $respond): JsonResponse|RedirectResponse
    {
        // Line breaks arrive as CRLF from a textarea; counted as the composer counts them.
        $request->merge(['body' => str_replace(["\r\n", "\r"], "\n", trim((string) $request->input('body')))]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:'.Settings::int(Settings::SUPPORT_DESCRIPTION_MAX)],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $response = $respond->execute($supportRequest, $validated['body'], $actor);

        return $this->respond($request, [
            'data' => [
                'id' => $response->getKey(),
                'body' => $response->body,
                'created_at' => $response->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
