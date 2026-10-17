<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 20 — the public surface does not publish its limiters' counters.
 *
 * Laravel's ThrottleRequests adds `X-RateLimit-Limit` and `X-RateLimit-Remaining` to **every**
 * response it lets through, not only to refusals. On the public support routes that is a live readout
 * of the caller's counter: it says how close they are to a limit and when it moved, and FR-130's rule
 * — a refusal says a limit was reached, never which or how near — is undone by the successful
 * responses before it. T378 found it: the three verification failures differed by exactly that header.
 *
 * In the `public` group between `web` and `throttle:public`, so it wraps the throttle and removes what
 * the throttle added on the way out. `Retry-After` on a 429 stays: it is the refusal's stated "when".
 */
final class HideRateLimitState
{
    private const COUNTER_HEADERS = ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::COUNTER_HEADERS as $header) {
            $response->headers->remove($header);
        }

        return $response;
    }
}
