<?php

declare(strict_types=1);

use App\Console\Commands\Backup;
use App\Models\AcademicTerm;
use App\Modules\AcademicTerms\Actions\ChangeTermStatus;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

/*
|--------------------------------------------------------------------------
| SEC-100 … SEC-102 — the backup runs by itself, and its absence is noticed (security plan G-19)
|--------------------------------------------------------------------------
|
| No real dump runs: Process is faked, and the default connection is pointed at a MySQL
| configuration that is never connected to — the command reads its settings, not its socket.
|
*/

/** This test's backup directory (created fresh per test by beforeEach). */
function backupDir(bool $fresh = false): string
{
    static $dir = null;

    if ($fresh || $dir === null) {
        $dir = sys_get_temp_dir().'/track-backup-test-'.uniqid();
    }

    return $dir;
}

/** This test's key file. */
function backupKeyFile(bool $fresh = false): string
{
    static $file = null;

    if ($fresh || $file === null) {
        $file = (string) tempnam(sys_get_temp_dir(), 'key');
        file_put_contents($file, 'k');
    }

    return $file;
}

beforeEach(function (): void {
    config([
        'backup.enabled' => true,
        'backup.path' => backupDir(fresh: true),
        'backup.key_file' => backupKeyFile(fresh: true),
        'backup.files_target' => 'backup-host:/track/files',
        'database.connections.mysql.password' => 'db-secret-pw',
        'database.connections.mysql.database' => 'track',
        'database.connections.mysql.username' => 'track_app',
    ]);
    @unlink(storage_path(Backup::STATUS_FILE));
});

afterEach(function (): void {
    config(['database.default' => 'sqlite']);
    File::deleteDirectory(backupDir());
    @unlink(backupKeyFile());
    @unlink(storage_path(Backup::STATUS_FILE));
});

test('a backup dumps, encrypts and copies, with the password kept off every command line', function (): void {
    Process::fake();
    config(['database.default' => 'mysql']);

    $this->artisan('track:backup')->assertSuccessful();

    Process::assertRan(function (PendingProcess $process): bool {
        $command = implode(' ', (array) $process->command);

        return str_contains($command, 'mysqldump --single-transaction')
            && str_contains($command, 'openssl enc -aes-256-cbc -pbkdf2')
            && str_contains($command, 'pipefail')
            && ! str_contains($command, 'db-secret-pw')
            && ($process->environment['MYSQL_PWD'] ?? null) === 'db-secret-pw';
    });
    Process::assertRan(fn (PendingProcess $process): bool => ($process->command[0] ?? null) === 'rsync');

    expect(Backup::lastSuccess())->not->toBeNull();
});

test('a failed dump exits 1 and leaves the last-success marker alone', function (): void {
    Process::fake(['*' => Process::result(errorOutput: 'mysqldump: Access denied', exitCode: 2)]);
    config(['database.default' => 'mysql']);

    $this->artisan('track:backup')->assertFailed();

    expect(Backup::lastSuccess())->toBeNull();
});

test('a missing key file refuses before anything runs', function (): void {
    Process::fake();
    config(['database.default' => 'mysql', 'backup.key_file' => '/no/such/key']);

    $this->artisan('track:backup')->assertFailed();

    Process::assertNothingRan();
});

test('old daily dumps are pruned and term-closure snapshots kept', function (): void {
    Process::fake();
    config(['database.default' => 'mysql']);
    File::ensureDirectoryExists(backupDir());
    $old = backupDir().'/track-2026-08-01-230000.sql.gz.enc';
    $snapshot = backupDir().'/track-2026-08-01-230000-term-closure-3.sql.gz.enc';
    file_put_contents($old, 'x');
    file_put_contents($snapshot, 'x');
    touch($old, now()->subDays(40)->getTimestamp());
    touch($snapshot, now()->subDays(40)->getTimestamp());

    $this->artisan('track:backup')->assertSuccessful();

    expect(file_exists($old))->toBeFalse()->and(file_exists($snapshot))->toBeTrue();
});

test('disabled, it writes nothing and runs nothing', function (): void {
    Process::fake();
    config(['backup.enabled' => false]);

    $this->artisan('track:backup')->assertSuccessful();

    Process::assertNothingRan();
});

test('it is scheduled daily at 02:00 Riyadh time', function (): void {
    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->first(fn ($e) => str_contains((string) $e->command, 'track:backup'));

    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 23 * * *');
});

test('closing a term queues a labelled snapshot', function (): void {
    Queue::fake();
    $term = AcademicTerm::factory()->create(['status' => 'active']);

    app(ChangeTermStatus::class)->execute($term, 'closed');

    Queue::assertPushed(QueuedCommand::class);
});

test('the monitor alerts when backups have stopped, and not when they are fresh', function (): void {
    $alerts = storage_path('logs/test-backup-alerts-'.uniqid().'.log');
    config(['logging.channels.alerts.driver' => 'single', 'logging.channels.alerts.path' => $alerts, 'cache.default' => 'array', 'monitoring.disk_usage' => 1.0]);
    Illuminate\Support\Facades\Log::forgetChannel('alerts');
    (new Database\Seeders\SettingSeeder)->run();

    File::put(storage_path(Backup::STATUS_FILE), (string) json_encode(['finished_at' => now()->subHours(30)->toIso8601String()]));
    $this->artisan('track:monitor')->assertSuccessful();
    expect((string) @file_get_contents($alerts))->toContain('backup_stale');

    @unlink($alerts);
    Illuminate\Support\Facades\Cache::flush();
    File::put(storage_path(Backup::STATUS_FILE), (string) json_encode(['finished_at' => now()->subHours(2)->toIso8601String()]));
    $this->artisan('track:monitor')->assertSuccessful();
    expect((string) @file_get_contents($alerts))->not->toContain('backup_stale');
    @unlink($alerts);
});
