<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers\Public;

use App\Modules\Support\Services\Challenge\ChallengeService;
use App\Modules\Support\Services\Challenge\ChallengeUnavailable;
use Illuminate\Http\JsonResponse;

/**
 * T392 — GET /support/challenge: `{ challenge, difficulty, expires_in }`, or 503 (contracts).
 *
 * The 503 is generic and fails closed (FR-129): the form shows it and keeps its submit disabled,
 * rather than letting a request through unchallenged. Why the store is down is logged by the service,
 * never told to the visitor.
 *
 * `no-store`: a challenge is single-use, and a cached response would hand two visitors the same one.
 */
final class ChallengeController
{
    public function __invoke(ChallengeService $challenges): JsonResponse
    {
        try {
            $issued = $challenges->issue();
        } catch (ChallengeUnavailable) {
            return response()->json(['message' => __('support.failures.challenge_unavailable')], 503)
                ->header('Cache-Control', 'no-store');
        }

        return response()->json($issued->toArray())->header('Cache-Control', 'no-store');
    }
}
