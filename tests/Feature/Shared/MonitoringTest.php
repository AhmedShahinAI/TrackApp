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

    // Seeded, as a deployment is: unreadable settings are an alert of their own (support retention).
    (new Database\Seeders\SettingSeeder)->run();

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

test('a support request kept past its retention says the sweep has stopped', function (): void {
    $world = supportStaffWorld();
    $late = supportRequestFor($world['branch']);
    $late->forceFill(['created_at' => now()->subMonths(24)->subDays(3)])->saveQuietly();

    $this->artisan('track:monitor')->expectsOutputToContain('support retention: OVERDUE')->assertSuccessful();

    expect(alertsRaised())->toContain('support_retention_overdue');
});

test('requests inside their retention, or within the grace days, raise nothing', function (): void {
    $world = supportStaffWorld();
    $graced = supportRequestFor($world['branch']);
    $graced->forceFill(['created_at' => now()->subMonths(24)->subDay()])->saveQuietly();

    $this->artisan('track:monitor')->expectsOutputToContain('support retention: on time')->assertSuccessful();

    expect(alertsRaised())->not->toContain('support_retention_overdue');
});

// ---- SEC-082 (security plan G-20): the security conditions ----

function auditRows(string $action, int $count, ?int $userId = null): void
{
    for ($i = 0; $i < $count; $i++) {
        DB::table('audit_logs')->insert(['user_id' => $userId, 'action' => $action, 'ip_address' => '10.0.0.1', 'created_at' => now()]);
    }
}

test('any account lock alerts at once', function (): void {
    auditRows('auth.login_blocked', 1);

    $this->artisan('track:monitor')->assertSuccessful();

    expect(alertsRaised())->toContain('login_blocked');
});

test('failed sign-ins alert past the level, not below it', function (): void {
    config(['monitoring.login_failures' => 10]);

    auditRows('auth.login_failed', 10);
    $this->artisan('track:monitor')->assertSuccessful();
    expect(alertsRaised())->not->toContain('login_failures');

    auditRows('auth.login_failed', 1);
    $this->artisan('track:monitor')->assertSuccessful();
    expect(alertsRaised())->toContain('login_failures');
});

test('one account refused unusually often alerts, with its id and nothing else about it', function (): void {
    config(['monitoring.denied_per_user' => 5]);
    $walker = aUser('INSTRUCTOR');
    auditRows('access.denied', 6, $walker->id);
    auditRows('access.denied', 3, aUser('INSTRUCTOR')->id);

    $this->artisan('track:monitor')->assertSuccessful();

    expect(alertsRaised())->toContain('access_denied_spike');

    $line = collect(explode("\n", (string) file_get_contents(alertLogFile())))->first(fn ($l) => str_contains($l, 'access_denied_spike'));
    expect(json_decode((string) $line, true)['context']['user_ids'])->toBe([$walker->id])
        ->and((string) $line)->not->toContain($walker->email);
});

test('old security events outside the window raise nothing', function (): void {
    DB::table('audit_logs')->insert(['action' => 'auth.login_blocked', 'ip_address' => '10.0.0.1', 'created_at' => now()->subHour()]);

    $this->artisan('track:monitor')->assertSuccessful();

    expect(alertsRaised())->not->toContain('login_blocked');
});
