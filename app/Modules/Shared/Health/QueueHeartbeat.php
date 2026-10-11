<?php

declare(strict_types=1);

namespace App\Modules\Shared\Health;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * T276 — proof that a queue worker is alive, for /health (PRD 8.8).
 *
 * A worker fires `Looping` before every attempt to take a job, busy or idle, so recording a time
 * there says "a worker is running and polling" — which no queue-size check can: an empty queue looks
 * the same with ten workers as with none. Written at most every THROTTLE seconds per worker process,
 * so an idle worker polling every three seconds does not write the cache twenty times a minute.
 */
final class QueueHeartbeat
{
    public const KEY = 'health:queue-worker:beat';

    /** A worker is presumed gone after this long without a beat. */
    public const STALE_AFTER_SECONDS = 120;

    private const THROTTLE = 30;

    private static ?int $lastWritten = null;

    public static function beat(): void
    {
        $now = time();

        if (self::$lastWritten !== null && $now - self::$lastWritten < self::THROTTLE) {
            return;
        }

        self::$lastWritten = $now;
        Cache::put(self::KEY, $now, self::STALE_AFTER_SECONDS * 5);
    }

    public static function lastBeat(): ?Carbon
    {
        $at = Cache::get(self::KEY);

        return is_int($at) ? Carbon::createFromTimestamp($at) : null;
    }

    /** For tests: forget this process's throttle. */
    public static function reset(): void
    {
        self::$lastWritten = null;
    }
}
