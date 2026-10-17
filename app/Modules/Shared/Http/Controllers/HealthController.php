<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers;

use App\Modules\Shared\Health\QueueHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function __invoke(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', fn (): bool => DB::select('select 1 as ok') !== []),
            'redis' => $this->check('redis', fn (): ?bool => $this->usesRedis() ? $this->pingRedis() : null),
            'queue' => $this->check('queue', fn (): ?bool => $this->queueHealthy()),
            'storage' => $this->check('storage', fn (): bool => $this->storageWritable()),
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['status'] !== 'fail');

        $body = ['status' => $healthy ? 'ok' : 'fail'];

        if ($this->mayReadDetail($request)) {
            $body['checks'] = $checks;
        }

        return response()->json($body, $healthy ? 200 : 503, ['Cache-Control' => 'no-store']);
    }

    /**
     * SEC-091 (security plan G-18). The probe is public, and its component list with timings is a
     * map of the stack for anyone who asks: which stores exist, which is slow, when the workers last
     * polled. The status and its 200/503 are what a load balancer needs, so that is all anyone gets;
     * the detail needs `X-Health-Token` matching HEALTH_TOKEN (compared in constant time).
     *
     * With no token configured, a production or staging host shows the status only — forgetting
     * the token must not open the detail — while a developer machine still shows everything.
     */
    private function mayReadDetail(Request $request): bool
    {
        $token = config('monitoring.health_token');

        if (! is_string($token) || $token === '') {
            return ! app()->environment(['production', 'staging']);
        }

        return hash_equals($token, (string) $request->header('X-Health-Token', ''));
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
