<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Middleware;

use App\Modules\Settings\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Sleep;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * T372 — every public support response takes at least `support_min_response_ms` (research.md R-10).
 *
 * "Not on the roster" returns after one index lookup; "on the roster, not enrolled" returns after a
 * join. The bodies are identical (T378), but the difference in time is measurable over enough
 * samples, and that reconstitutes the leak the identical body closed. A **fixed floor above the
 * slowest path** leaks nothing: every response leaves at the same moment.
 *
 * Not random jitter. Jitter still leaks the mean over enough samples (R-10, alternatives).
 *
 * ## Where it sits
 *
 * Outermost in the support routes' middleware (T394), so a throttled refusal, a challenge refusal
 * and a validation failure are padded exactly like a verification failure — a refusal that returned
 * early would be its own timing signal. The clock starts when this middleware is entered; what runs
 * before it (session start, CSRF, routing) does not depend on the identifier.
 *
 * A response slower than the floor is left alone: the floor is a minimum, not a target.
 *
 * `Sleep`, not `usleep`, so a test can fake the clock and assert the requested pause (T379).
 */
final class ConstantResponseTime
{
    /**
     * When the setting cannot be read, the documented default. Skipping the floor because settings
     * failed would remove a protection silently (FR-129's fail-closed reasoning).
     */
    private const FALLBACK_MS = 400;

    public function handle(Request $request, Closure $next): Response
    {
        $started = hrtime(true);

        $response = $next($request);

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;
        $remainingMs = $this->floorMs() - $elapsedMs;

        if ($remainingMs > 0) {
            Sleep::usleep((int) ceil($remainingMs * 1000));
        }

        return $response;
    }

    private function floorMs(): int
    {
        try {
            return max(0, Settings::int(Settings::SUPPORT_MIN_RESPONSE_MS));
        } catch (Throwable) {
            return self::FALLBACK_MS;
        }
    }
}
