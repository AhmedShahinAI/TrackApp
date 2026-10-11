<?php

declare(strict_types=1);

namespace App\Modules\Support\Http;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * T371 — a support limit was reached. One refusal for every limit on the public surface
 * (FR-130): 429, a `Retry-After` header, and one message saying a limit was reached and when to try
 * again — never which limit.
 *
 * Renders itself, so the submission throttle (T371) and the status-retrieval throttle (Phase 22)
 * cannot drift into two wordings, which would be the "which limit" signal by another route.
 */
final class SupportLimitReached extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('A support rate limit was reached.');
    }

    public function render(Request $request): Response
    {
        $minutes = max(1, (int) ceil($this->retryAfterSeconds / 60));
        $message = trans_choice('support.limit_reached', $minutes, ['minutes' => $minutes]);
        $headers = ['Retry-After' => (string) $this->retryAfterSeconds];

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_TOO_MANY_REQUESTS, $headers);
        }

        return response()->view('support.public.limit-reached', ['message' => $message], Response::HTTP_TOO_MANY_REQUESTS, $headers);
    }
}
