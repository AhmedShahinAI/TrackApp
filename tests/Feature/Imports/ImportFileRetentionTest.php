<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\ImportJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| SEC-062, SEC-063 — an uploaded roster is deleted once nothing needs it (security plan G-05)
|--------------------------------------------------------------------------
|
| Always on a fake disk: the sweep deletes orphaned files, and a developer's real
| storage/app/private/imports holds files nobody has agreed to lose.
|
*/

beforeEach(function (): void {
    Storage::fake('private');
});

/** An import job row with a file on the (fake) private disk. */
function jobWithFile(string $status, int $ageDays, string $name): ImportJob
{
    $term = AcademicTerm::query()->first() ?? AcademicTerm::factory()->create();
    $path = 'imports/2026/10/'.$name.'.xlsx';
    Storage::disk('private')->put($path, 'roster');

    $job = ImportJob::query()->create([
        'user_id' => aUser('SUPER_ADMIN')->id,
        'mode' => 'central',
        'term_id' => $term->id,
        'original_filename' => $name.'.xlsx',
        'file_path' => $path,
        'file_hash' => hash('sha256', $name),
        'status' => $status,
    ]);

    ImportJob::query()->withoutGlobalScopes()->whereKey($job->id)->update(['updated_at' => now()->subDays($ageDays)]);

    return $job;
}

test('completing an import deletes its uploaded file, and audits it', function (): void {
    $world = importWorld();
    $job = importRoster($world, [rosterRow($world, ['section_number' => '701'])], ['create_missing_instructors' => true]);

    expect($job->fresh()->status)->toBe('completed')
        ->and(Storage::disk('private')->exists($job->file_path))->toBeFalse()
        ->and(DB::table('audit_logs')->where('action', 'import.file_discarded')->count())->toBe(1);
});

test('the sweep deletes finished files past retention and orphans, and keeps everything else', function (): void {
    $oldCompleted = jobWithFile('completed', 31, 'old-completed');
    $oldFailed = jobWithFile('failed', 45, 'old-failed');
    $recentFailed = jobWithFile('failed', 3, 'recent-failed');
    $oldInFlight = jobWithFile('validated', 90, 'old-in-flight');
    Storage::disk('private')->put('imports/2026/09/orphan.xlsx', 'left behind');

    expect(Artisan::call('track:prune-import-files'))->toBe(0);

    $disk = Storage::disk('private');

    expect($disk->exists($oldCompleted->file_path))->toBeFalse()
        ->and($disk->exists($oldFailed->file_path))->toBeFalse()
        ->and($disk->exists('imports/2026/09/orphan.xlsx'))->toBeFalse()
        ->and($disk->exists($recentFailed->file_path))->toBeTrue()
        // In flight: validation may still read it, however old.
        ->and($disk->exists($oldInFlight->file_path))->toBeTrue()
        ->and(DB::table('audit_logs')->where('action', 'import.file_discarded')->count())->toBe(2);
});

test('a dry run deletes nothing', function (): void {
    $old = jobWithFile('completed', 60, 'dry');
    Storage::disk('private')->put('imports/2026/08/orphan.xlsx', 'left behind');

    Artisan::call('track:prune-import-files', ['--dry-run' => true]);

    expect(Storage::disk('private')->exists($old->file_path))->toBeTrue()
        ->and(Storage::disk('private')->exists('imports/2026/08/orphan.xlsx'))->toBeTrue()
        ->and(Artisan::output())->toContain('Would delete: 1 expired import file(s), 1 orphaned file(s).');
});

test('the sweep is scheduled daily', function (): void {
    $events = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'track:prune-import-files'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 0 * * *');
});
