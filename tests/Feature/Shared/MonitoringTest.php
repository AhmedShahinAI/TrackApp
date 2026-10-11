<?php

declare(strict_types=1);

use App\Modules\Shared\Monitoring\RequestOutcomes;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PRD 8.8 — alerts on error rate, failed jobs, queue backlog and disk usage (security review R-02)
|--------------------------------------------------------------------------
*/

/** This test's alert log file. */
function alertLogFile(bool $fresh = false): string
{
    static $file = null;

    if ($fresh || $file === null) {
        $file = storage_path('logs/test-alerts-'.uniqid().'.log');
    }

    return $file;
}

/** @return list<string> the alert keys written so far */
function alertsRaised(): array
{
    $lines = array_filter(explode("\n", (string) @file_get_contents(alertLogFile())));

    return array_values(array_map(static fn (string $line): string => (string) (json_decode($line, true)['context']['alert'] ?? ''), $lines));
}

beforeEach(function (): void {
    config([
        'cache.default' => 'array',
        'queue.default' => 'database',
        'logging.channels.alerts.driver' => 'single',
        'logging.channels.alerts.path' => alertLogFile(fresh: true),
        'monitoring.min_requests' => 10,
        'monitoring.disk_usage' => 1.0, // off unless a test lowers it
        'monitoring.email' => null,
    ]);
    Log::forgetChannel('alerts');
    Cache::flush();

    Route::get('/__ok', fn () => 'ok');
    Route::get('/__boom', fn () => abort(500));
});

afterEach(function (): void {
    @unlink(alertLogFile());
});

test('every request is counted, and a server error counted as one', function (): void {
    $this->get('/__ok')->assertOk();
    $this->get('/__ok')->assertOk();
    $this->get('/__boom')->assertStatus(500);
    $this->getJson('/health'); // a probe is not traffic

    expect(RequestOutcomes::window(5))->toBe(['requests' => 3, 'errors' => 1]);
});

test('an error rate over 1% across enough requests raises one alert, not one per check', function (): void {
    for ($i = 0; $i < 20; $i++) {
        $this->get('/__ok');
    }

    $this->get('/__boom');
    $this->get('/__boom');

    $this->artisan('track:monitor')->expectsOutputToContain('errors: 2 of 22')->assertSuccessful();
    $this->artisan('track:monitor')->assertSuccessful();

    expect(alertsRaised())->toBe(['error_rate']);
});

test('a handful of requests is not a rate', function (): void {
    $this->get('/__boom');
    $this->get('/__ok');

    $this->artisan('track:monitor')->assertSuccessful();

    expect(alertsRaised())->toBe([]);
});

test('a backlog over the threshold alerts', function (): void {
    config(['monitoring.queue_backlog' => 2]);

    foreach (range(1, 3) as $i) {
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => now()->getTimestamp(), 'created_at' => now()->getTimestamp()]);
    }

    $this->artisan('track:monitor')->expectsOutputToContain('queue backlog: 3')->assertSuccessful();

    expect(alertsRaised())->toBe(['queue_backlog']);
});

test('disk usage over the threshold alerts', function (): void {
    config(['monitoring.disk_usage' => 0.0]);

    $this->artisan('track:monitor')->assertSuccessful();

    expect(alertsRaised())->toBe(['disk_usage']);
});

test('an unreachable queue store is itself the alert', function (): void {
    config(['queue.default' => 'broken', 'queue.connections.broken' => ['driver' => 'database', 'connection' => 'nowhere', 'table' => 'jobs', 'queue' => 'default']]);
    config(['database.connections.nowhere' => ['driver' => 'sqlite', 'database' => base_path('no-such-dir/x.sqlite')]]);

    $this->artisan('track:monitor')->expectsOutputToContain('unreachable')->assertSuccessful();

    expect(alertsRaised())->toBe(['queue_unreachable']);
});

test('a failed job alerts the moment it fails', function (): void {
    // A real job object: its name comes from the payload's displayName, as a queued job's does.
    $job = new Illuminate\Queue\Jobs\SyncJob(app(), (string) json_encode([
        'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
        'displayName' => 'App\\Modules\\Imports\\Jobs\\ValidateImportJob',
    ]), 'database', 'default');

    event(new JobFailed('database', $job, new RuntimeException('disk full')));

    expect(alertsRaised())->toBe(['job_failed:App\\Modules\\Imports\\Jobs\\ValidateImportJob']);
});

test('the checks are scheduled every five minutes', function (): void {
    $events = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'track:monitor'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('*/5 * * * *');
});
