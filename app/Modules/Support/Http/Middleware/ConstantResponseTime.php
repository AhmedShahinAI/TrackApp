<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Middleware;

use App\Modules\Settings\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * T372 — every public support response that takes an identifier takes at least
 * `support_min_response_ms` (research.md R-10). Today that is POST /support; Phase 22's status lookup
 * joins it. The page views are not padded — they take no identifier (routes/public.php).
 *
 * "Not on the roster" returns after one index lookup; "on the roster, not enrolled" returns after a
 * join. The bodies are identical (T378), but the difference in time is measurable over enough
 * samples, and that reconstitutes the leak the identical body closed. A **fixed floor above the
 * slowest path** leaks nothing: every response leaves at the same moment.
 *
 * Not random jitter. Jitter still leaks the mean over enough samples (R-10, alternatives).
 *
 * ## Where it sits, and what it does not pad
 *
 * Inside the rate limiter, outside everything else of the support routes (T394): a challenge
 * refusal, a validation failure and a verification failure are padded exactly like a success, since
 * a refusal that returned early would be its own timing signal. The clock starts when this
 * middleware is entered; what runs before it (session, CSRF, routing) does not depend on the
 * identifier.
 *
 * **A 429 is never padded** (review). Its status already says "refused", so the floor hides nothing
 * there — and padding it would make every refused request of a flood hold a PHP worker for the whole
 * floor. At 400 ms a worker serves at most 2.5 padded requests a second; on a 50-worker pool that is
 * 125 a second, which a few hundred addresses reach while each stays under its own limit. Refusals
 * returning at once keeps the cost of the floor on the requests that actually reach the roster. The
 * submission limiter sits outside this middleware anyway; the status check also covers the identifier
 * refusal raised later, inside it, so the two limits cannot be told apart by timing either.
 *
 * Every other padded response still holds a worker for the floor. The deployment runbook sizes the
 * PHP pool for that (docs/runbook-deploy.md, "Support channel").
 *
 * A response slower than the floor is left alone: the floor is a minimum, not a target.
 *
 * `Sleep`, not `usleep`, so a test can fake the clock and assert the requested pause (T379).
 */
final class ConstantResponseTime
{
    /**
     * When the setting cannot be read, or is not a positive number of milliseconds, the documented
     * default. A floor of zero is not a configuration of this protection but its removal, and
     * removing it silently because a row was mistyped is the fail-open FR-129's reasoning forbids.
     */
    private const FALLBACK_MS = 400;

    public function handle(Request $request, Closure $next): Response
    {
        $slot = $this->takeSlot();

        // Full: answered at once, before any work, identically for every request — so it says nothing
        // about anyone's identifier, and it costs no worker its floor (Phase 22 review).
        if ($slot === false) {
            return $this->busy($request);
        }

        try {
            $started = hrtime(true);

            $response = $next($request);

            if ($response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
                return $response;
            }

            $floorMs = $this->floorMs();
            $elapsedMs = (hrtime(true) - $started) / 1_000_000;
            $remainingMs = $floorMs - $elapsedMs;

            if ($remainingMs > 0) {
                $requestedUs = (int) ceil($remainingMs * 1000);
                $beforeSleep = hrtime(true);
                Sleep::usleep($requestedUs);

                /*
                 * One top-up, because the floor is a guarantee and the OS timer is not. On Windows
                 * usleep can wake a fraction of a millisecond early, and the response then left just
                 * under the floor (Phase 26 gate: 249.36 ms against 250, one run in five). Only when the
                 * sleep really slept, meaning at least half of what was asked passed. A faked Sleep
                 * advances no clock, so a test that asserts the requested pause still sees exactly one.
                 */
                $sleptUs = (hrtime(true) - $beforeSleep) / 1000;
                $shortUs = (int) ceil(($floorMs - (hrtime(true) - $started) / 1_000_000) * 1000);

                if ($sleptUs >= $requestedUs / 2 && $shortUs > 0) {
                    Sleep::usleep($shortUs);
                }
            } else {
                $this->reportFloorExceeded($request, $elapsedMs, $floorMs);
            }

            return $response;
        } finally {
            $this->releaseSlot($slot);
        }
    }

    /**
     * A floor the work outran is a floor that no longer hides anything (Phase 22 review): the failure
     * paths do different work, and once one passes the floor its timing is its own again. Logged — at most
     * once a minute, so a flood of slow requests does not become a flood of log lines — so the floor can be
     * raised above the real p99 before anyone measures the difference from outside.
     */
    private function reportFloorExceeded(Request $request, float $elapsedMs, int $floorMs): void
    {
        try {
            if (! Cache::add('support:floor-exceeded:'.now()->format('YmdHi'), true, 120)) {
                return;
            }
        } catch (Throwable) {
            // The warning still goes out below; the once-a-minute damper is a courtesy, not a control.
        }

        Log::warning('A padded support response took longer than its response-time floor.', [
            'route' => $request->route()?->getName(),
            'elapsed_ms' => (int) round($elapsedMs),
            'floor_ms' => $floorMs,
        ]);
    }

    /**
     * Count this request in, or false when the cap is reached. Counted in a per-minute bucket in the cache
     * (Redis INCR): a worker killed mid-request leaves its count in a bucket that expires, so a crash
     * cannot wedge the cap shut. An unreadable store does not refuse the request — the cap protects the
     * pool, and failing open here costs capacity, not secrecy; the floor still applies.
     *
     * @return string|false|null the bucket to release, false when full, null when uncapped
     */
    private function takeSlot(): string|false|null
    {
        $cap = (int) config('support.padding_max_concurrent', 0);

        if ($cap < 1) {
            return null;
        }

        $bucket = 'support:padding:'.now()->format('YmdHi');

        try {
            Cache::add($bucket, 0, 120);
            $active = (int) Cache::increment($bucket);
        } catch (Throwable) {
            return null;
        }

        if ($active > $cap) {
            $this->releaseSlot($bucket);

            return false;
        }

        return $bucket;
    }

    private function releaseSlot(string|false|null $bucket): void
    {
        if (! is_string($bucket)) {
            return;
        }

        try {
            Cache::decrement($bucket);
        } catch (Throwable) {
            // The bucket expires on its own.
        }
    }

    private function busy(Request $request): Response
    {
        $message = __('support.failures.challenge_unavailable');

        return $request->expectsJson()
            ? response()->json(['message' => $message], Response::HTTP_SERVICE_UNAVAILABLE, ['Retry-After' => '30'])
            : response($message, Response::HTTP_SERVICE_UNAVAILABLE, ['Retry-After' => '30', 'Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function floorMs(): int
    {
        try {
            $floor = Settings::get(Settings::SUPPORT_MIN_RESPONSE_MS);
        } catch (Throwable) {
            return self::FALLBACK_MS;
        }

        if (! is_int($floor) || $floor < 1) {
            Log::warning('Ignoring an unusable support response-time floor.', ['setting' => Settings::SUPPORT_MIN_RESPONSE_MS]);

            return self::FALLBACK_MS;
        }

        return $floor;
    }
}
