<?php

declare(strict_types=1);

namespace App\Modules\Shared\Monitoring;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * PRD 8.8 — counts requests and server errors per minute, for the error-rate alert.
 *
 * Global and outermost but one, so it sees the final status of every request — including a 500
 * rendered from an exception. Two cache increments per request into a minute bucket that expires on
 * its own; `track:monitor` sums the last few buckets. Counting is never allowed to fail a request.
 */
final class RequestOutcomes
{
    private const TTL_MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // A load balancer's probe is not traffic; counting it would dilute the error rate.
        if ($request->is('health', 'up')) {
            return $response;
        }

        try {
            $bucket = self::bucket(now()->getTimestamp());
            $ttl = now()->addMinutes(self::TTL_MINUTES);

            Cache::add('metrics:requests:'.$bucket, 0, $ttl);
            Cache::increment('metrics:requests:'.$bucket);

            if ($response->getStatusCode() >= 500) {
                Cache::add('metrics:errors:'.$bucket, 0, $ttl);
                Cache::increment('metrics:errors:'.$bucket);
            }
        } catch (Throwable) {
            // The cache is down; /health will say so. The request still succeeds.
        }

        return $response;
    }

    public static function bucket(int $timestamp): int
    {
        return intdiv($timestamp, 60);
    }

    /**
     * Requests and server errors over the last $minutes whole minutes, counting the current one.
     *
     * @return array{requests: int, errors: int}
     */
    public static function window(int $minutes): array
    {
        $now = self::bucket(now()->getTimestamp());
        $requests = 0;
        $errors = 0;

        for ($b = $now - $minutes + 1; $b <= $now; $b++) {
            $requests += (int) Cache::get('metrics:requests:'.$b, 0);
            $errors += (int) Cache::get('metrics:errors:'.$b, 0);
        }

        return ['requests' => $requests, 'errors' => $errors];
    }
}
