<?php

declare(strict_types=1);

use App\Modules\Shared\Health\QueueHeartbeat;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T276 — GET /health: database, Redis, queue workers, storage (PRD 8.8)
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    config([
        'queue.default' => 'database',
        'cache.default' => 'array',
        'session.driver' => 'array',
    ]);
    QueueHeartbeat::reset();
    Cache::forget(QueueHeartbeat::KEY);
});

test('all applicable checks pass: 200, and Redis is skipped where nothing uses it', function (): void {
    event(new Looping('database', 'default'));

    $this->getJson('/health')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database.status', 'ok')
        ->assertJsonPath('checks.redis.status', 'skipped')
        ->assertJsonPath('checks.queue.status', 'ok')
        ->assertJsonPath('checks.storage.status', 'ok');
});

test('no worker has polled: the queue check fails and the endpoint answers 503', function (): void {
    $this->getJson('/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'fail')
        ->assertJsonPath('checks.queue.status', 'fail');
});

test('a worker that stopped polling two minutes ago counts as gone', function (): void {
    Cache::put(QueueHeartbeat::KEY, now()->subSeconds(QueueHeartbeat::STALE_AFTER_SECONDS + 5)->getTimestamp());

    $this->getJson('/health')->assertStatus(503)->assertJsonPath('checks.queue.status', 'fail');
});

test('a live worker that is not keeping up still fails: a job has waited over five minutes', function (): void {
    event(new Looping('database', 'default'));

    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->subMinutes(10)->getTimestamp(),
        'created_at' => now()->subMinutes(10)->getTimestamp(),
    ]);

    $this->getJson('/health')->assertStatus(503)->assertJsonPath('checks.queue.status', 'fail');
});

test('a synchronous queue has no workers to check', function (): void {
    config(['queue.default' => 'sync']);

    $this->getJson('/health')->assertOk()->assertJsonPath('checks.queue.status', 'skipped');
});

test('a failing check says fail and nothing more: the reason goes to the log, not the caller', function (): void {
    event(new Looping('database', 'default'));

    // A database file in a directory that does not exist: fails at once, and its path is the kind of
    // detail that must not reach the caller.
    $original = config('database.default');
    config(['database.connections.broken' => ['driver' => 'sqlite', 'database' => base_path('no-such-dir/secret_db_name.sqlite'), 'prefix' => '']]);
    config(['database.default' => 'broken']);

    try {
        $response = $this->getJson('/health');
    } finally {
        config(['database.default' => $original]);
        DB::purge('broken');
    }

    $response->assertStatus(503)->assertJsonPath('checks.database.status', 'fail');

    expect($response->getContent())->not->toContain('secret_db_name')
        ->and($response->getContent())->not->toContain('no-such-dir');
});

test('the probe is anonymous and writes no session or cookie', function (): void {
    event(new Looping('database', 'default'));

    $response = $this->getJson('/health')->assertOk();

    expect($response->headers->getCookies())->toBe([]);
});

test('the heartbeat writes at most once per throttle window per worker', function (): void {
    event(new Looping('database', 'default'));
    $first = Cache::get(QueueHeartbeat::KEY);

    Cache::put(QueueHeartbeat::KEY, 1);
    event(new Looping('database', 'default'));

    expect($first)->toBeInt()->and(Cache::get(QueueHeartbeat::KEY))->toBe(1);
});
