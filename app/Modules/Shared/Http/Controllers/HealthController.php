<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers;

use App\Modules\Shared\Health\QueueHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * T276 — GET /health: the database, Redis, the queue workers and storage (PRD 8.8).
 *
 * For a load balancer and a monitor, so it is unauthenticated and outside the `web` group — no
 * session row is written per probe, every few seconds, forever. And so it says only `ok` or `fail`
 * per check with a timing: the reason a check failed (a hostname, a driver error) goes to the log
 * with the request id, not to whoever can reach the URL.
 *
 * 200 when every applicable check passes, 503 otherwise, which is what a load balancer acts on.
 * A check that does not apply — Redis on a deployment that does not use it — is `skipped`, never a
 * failure: a health endpoint that fails on what the platform does not run gets ignored.
 *
 * Queue workers are judged two ways, because each misses something the other sees: the worker
 * heartbeat (QueueHeartbeat) says a worker is polling; the oldest waiting job says the workers are
 * keeping up. A live worker stuck on one queue while jobs pile up on another passes the first and
 * fails the second.
 */
final class HealthController
{
    /** A job ready this long without being taken means the workers are not keeping up. */
    private const BACKLOG_AFTER_SECONDS = 300;

    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', fn (): bool => DB::select('select 1 as ok') !== []),
            'redis' => $this->check('redis', fn (): ?bool => $this->usesRedis() ? $this->pingRedis() : null),
            'queue' => $this->check('queue', fn (): ?bool => $this->queueHealthy()),
            'storage' => $this->check('storage', fn (): bool => $this->storageWritable()),
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['status'] !== 'fail');

        return response()->json([
            'status' => $healthy ? 'ok' : 'fail',
            'checks' => $checks,
        ], $healthy ? 200 : 503, ['Cache-Control' => 'no-store']);
    }

    /**
     * @param  callable(): ?bool  $probe  true = ok, false = fail, null = does not apply here
     * @return array{status: string, ms: float}
     */
    private function check(string $name, callable $probe): array
    {
        $started = hrtime(true);

        try {
            $result = $probe();
            $status = $result === null ? 'skipped' : ($result ? 'ok' : 'fail');
        } catch (Throwable $e) {
            Log::warning("health check failed: {$name}", ['exception' => $e]);
            $status = 'fail';
        }

        return ['status' => $status, 'ms' => round((hrtime(true) - $started) / 1e6, 1)];
    }

    private function usesRedis(): bool
    {
        return in_array('redis', [
            config('cache.default'),
            config('queue.default'),
            config('session.driver'),
        ], true);
    }

    private function pingRedis(): bool
    {
        $reply = Redis::connection()->command('ping');

        return $reply === true || (is_string($reply) && Str::contains(strtoupper($reply), 'PONG'));
    }

    private function queueHealthy(): ?bool
    {
        $driver = config('queue.connections.'.config('queue.default').'.driver');

        // A synchronous queue has no workers to be alive.
        if ($driver === 'sync' || $driver === null) {
            return null;
        }

        $beat = QueueHeartbeat::lastBeat();

        if ($beat === null || $beat->diffInSeconds(now()) > QueueHeartbeat::STALE_AFTER_SECONDS) {
            return false;
        }

        if ($driver === 'database') {
            $table = (string) config('queue.connections.'.config('queue.default').'.table', 'jobs');
            $oldest = DB::table($table)->whereNull('reserved_at')->where('available_at', '<=', now()->getTimestamp())->min('available_at');

            return $oldest === null || now()->getTimestamp() - (int) $oldest <= self::BACKLOG_AFTER_SECONDS;
        }

        return true;
    }

    private function storageWritable(): bool
    {
        $disk = Storage::disk('local');
        $probe = 'health/'.Str::uuid()->toString();

        $disk->put($probe, 'ok');
        $ok = $disk->get($probe) === 'ok';
        $disk->delete($probe);

        return $ok;
    }
}
