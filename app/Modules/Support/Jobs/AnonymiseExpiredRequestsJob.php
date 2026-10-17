<?php

declare(strict_types=1);

namespace App\Modules\Support\Jobs;

use App\Modules\Support\Actions\AnonymiseExpiredRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * T452 — the daily retention sweep, queued so the scheduler's minute is never spent on it.
 *
 * Unique, so a sweep still running when the next day's is dispatched is not joined by a second one.
 * The action is safe against that anyway (each request is re-read under a lock), but two sweeps would
 * only contend. Retried: a sweep that fails halfway leaves the rest for the retry, or for tomorrow —
 * nothing it has done needs undoing.
 */
final class AnonymiseExpiredRequestsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** Hold the uniqueness lock for a day at most, so a crashed worker cannot block the sweep for good. */
    public int $uniqueFor = 86_400;

    public function handle(AnonymiseExpiredRequests $sweep): void
    {
        $sweep->execute();
    }
}
