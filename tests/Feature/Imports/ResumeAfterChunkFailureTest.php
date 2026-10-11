<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\SectionEnrollment;
use App\Modules\Imports\Pipeline\Resolver;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T213 — a commit that dies part-way keeps what it finished and resumes the rest (FR-065)
|--------------------------------------------------------------------------
|
| The failure is real, not simulated by editing the job row: the Resolver is replaced with one that
| throws on a chosen source row, so the second chunk's transaction genuinely aborts mid-way. Then:
|
|   - the job is `failed` with a reason;
|   - the checkpoint is the last row of the first chunk — not the row before the failure, which
|     is what a checkpoint written per row rather than per chunk would show;
|   - the first chunk's 500 enrollments exist and the failing chunk's do not, so the chunk was
|     all-or-nothing;
|   - a resume completes the remainder with nothing committed twice.
|
*/

/**
 * A Resolver that fails on one source row, once.
 */
final class FailsOnRow extends Resolver
{
    public static ?int $row = null;

    public function commit(ImportRow $row): array
    {
        if ($row->row_number === self::$row) {
            self::$row = null;

            throw new RuntimeException('Simulated worker failure mid-chunk.');
        }

        return parent::commit($row);
    }
}

/**
 * The world, with the Resolver swapped for one that fails on FailsOnRow::$row.
 *
 * @return array<string, mixed>
 */
function failingWorld(): array
{
    $world = importWorld();

    app()->bind(Resolver::class, fn ($app, array $parameters) => new FailsOnRow($parameters['job']));

    return $world;
}

test('a failed chunk leaves earlier chunks committed and the checkpoint at the last good one', function (): void {
    $world = failingWorld();
    // Source row 750 is the 749th data row: in the second chunk, part-way through it.
    FailsOnRow::$row = 750;

    $job = importRoster($world, rosterRows($world, 1200));

    $ids = ImportRow::query()->where('import_job_id', $job->id)->orderBy('id')->pluck('id')->all();

    expect($job->status)->toBe('failed')
        ->and($job->failure_reason)->toBe(__('imports.failure.chunk', ['row' => 502]))
        ->and($job->last_committed_row_id)->toBe($ids[499])
        ->and($job->processed)->toBe(500)
        ->and($job->imported)->toBe(500)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(500)

        // Not one row of the failing chunk survived, including the 248 before the failure.
        ->and(ImportRow::query()->where('import_job_id', $job->id)->where('status', 'committed')->count())->toBe(500);
});

test('a resume completes the remainder exactly once', function (): void {
    $world = failingWorld();
    FailsOnRow::$row = 750;

    $job = importRoster($world, rosterRows($world, 1200));
    expect($job->status)->toBe('failed');

    $this->actingAs($world['admin'])
        ->postJson(route('api.v1.imports.resume', $job))
        ->assertAccepted()
        ->assertJsonPath('data.resuming_from_row_id', $job->last_committed_row_id);

    $job->refresh();

    expect($job->status)->toBe('completed')
        ->and($job->imported)->toBe(1200)
        ->and($job->processed)->toBe(1200)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(1200)
        ->and(DB::table('audit_logs')->where('action', 'import.resumed')->count())->toBe(1)
        ->and(DB::table('audit_logs')->where('action', 'import.committed')->count())->toBe(1);
});

test('only a failed commit can be resumed', function (): void {
    $world = failingWorld();
    $completed = importRoster($world, rosterRows($world, 2));

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.resume', $completed))->assertConflict();

    // A failed validation is not resumable either — nothing was committed to resume from.
    $job = uploadRoster($world['admin'], rosterFile(rosterRows($world, 1), 'other.xlsx'), $world['term']->id);
    $job->forceFill(['status' => 'failed'])->save();

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.resume', $job))->assertConflict();
});

test('the failed state is shown with what survived and a resume control', function (): void {
    $world = failingWorld();
    FailsOnRow::$row = 750;

    $job = importRoster($world, rosterRows($world, 1200));

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.report.failed'))
        ->assertSee(__('imports.report.failed_hint'))
        ->assertSee(route('api.v1.imports.resume', $job));

    expect(ImportJob::query()->find($job->id)?->canBeResumed())->toBeTrue();
});
