<?php

declare(strict_types=1);

namespace App\Modules\Imports\Jobs;

use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\User;
use App\Modules\Imports\Notifications\ImportFinished;
use App\Modules\Imports\Pipeline\ColumnMapper;
use App\Modules\Imports\Pipeline\HeaderReader;
use App\Modules\Imports\Pipeline\RowValidator;
use App\Modules\Imports\Pipeline\SpreadsheetReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * T228 — step 3: stream the file and stage one `import_rows` row per source row (FR-057, FR-063).
 *
 * ## It writes to no core table
 *
 * Only `import_rows` and the job's own counters. That is FR-060, and it holds structurally: the
 * validator it calls performs lookups only, and the staging insert is the one write here.
 * NoWritesBeforeConfirmTest asserts the core tables are unchanged across it.
 *
 * ## Re-runnable
 *
 * A job's staged rows are cleared before it starts, so a mapping corrected after the preview is
 * validated afresh rather than appended to the first attempt's rows.
 *
 * ## Progress and liveness
 *
 * Each 500-row batch updates `processed` and `heartbeat_at`. The first is the progress bar the
 * wizard polls; the second is how the screen tells "a worker is on it" from "waiting for a worker"
 * (ImportJob::isWaitingForWorker, C-13).
 *
 * ## Failure is a state, not an exception
 *
 * Anything thrown marks the job `failed` with a reason the operator can read, and is reported for
 * the operator's colleagues in IT. Rethrowing would let the queue retry a corrupt file indefinitely,
 * and under the sync driver it would turn the operator's click into a 500.
 */
final class ValidateImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const BATCH = 500;

    public int $tries = 1;

    /**
     * Fifty thousand rows take minutes. Kept below the queue's `retry_after` (config/queue.php), or a
     * run still working would be handed to a second worker; failOnTimeout so a run that overstays is
     * recorded as a failed validation rather than left `validating` forever.
     */
    public int $timeout = 840;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $importJobId) {}

    public function handle(ColumnMapper $mapper, HeaderReader $headerReader): void
    {
        $job = ImportJob::query()->withoutGlobalScopes()->find($this->importJobId);

        if ($job === null || $job->status !== 'validating') {
            return;
        }

        $job->forceFill([
            'heartbeat_at' => now(),
            'started_at' => $job->started_at ?? now(),
            'processed' => 0,
        ])->save();

        try {
            ImportRow::query()->where('import_job_id', $job->id)->delete();

            $validator = new RowValidator($job);
            $header = $headerReader->read($job);
            $indexes = $mapper->indexes($job->mapping ?? [], $header['headers']);
            $processed = 0;
            $batch = [];

            foreach (SpreadsheetReader::rows(HeaderReader::path($job), HeaderReader::extension($job)) as $number => $cells) {
                if ($number <= $header['row']) {
                    continue;
                }

                $raw = [];

                foreach ($job->modeClass()::targets() as $target) {
                    $raw[$target] = isset($indexes[$target]) ? ($cells[$indexes[$target]] ?? '') : '';
                }

                $batch[] = ['row_number' => $number, 'raw' => $raw];

                if (count($batch) === self::BATCH) {
                    $processed += $this->stage($job, $validator, $batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $processed += $this->stage($job, $validator, $batch);
            }

            $summary = $validator->summary();

            // Compare-and-set: if the operator re-mapped while this ran (allowed once the worker went
            // quiet), these results describe a mapping that no longer exists and must not land.
            $finished = ImportJob::query()->withoutGlobalScopes()
                ->whereKey($job->id)
                ->where('status', 'validating')
                ->update([
                    'status' => 'validated',
                    'summary' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'total_rows' => $summary['total_rows'],
                    'processed' => $processed,
                    'errors' => $summary['errors'],
                    'heartbeat_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($finished === 1) {
                $this->notify($job->refresh(), 'validated');
            }
        } catch (Throwable $e) {
            report($e);

            $this->stop($job);
        }
    }

    /**
     * The queue gave up on this run — a timeout, or an exception the handler did not catch. The job
     * becomes a failed validation, which the operator can re-map and check again, instead of staying
     * `validating` with nothing behind it.
     */
    public function failed(?Throwable $exception): void
    {
        $job = ImportJob::query()->withoutGlobalScopes()->find($this->importJobId);

        if ($job !== null) {
            $this->stop($job);
        }
    }

    private function stop(ImportJob $job): void
    {
        $updated = ImportJob::query()->withoutGlobalScopes()
            ->whereKey($job->id)
            ->where('status', 'validating')
            ->update([
                'status' => 'failed',
                'failure_reason' => __('imports.failure.validation'),
                'heartbeat_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 1) {
            $this->notify($job->refresh(), 'failed');
        }
    }

    /**
     * @param  list<array{row_number: int, raw: array<string, string>}>  $batch
     */
    private function stage(ImportJob $job, RowValidator $validator, array $batch): int
    {
        $rows = array_map(static fn (array $row): array => [
            'import_job_id' => $job->id,
            'row_number' => $row['row_number'],
            'raw' => json_encode($row['raw'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'resolved' => json_encode($row['resolved'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'status' => $row['status'],
            'reasons' => json_encode($row['reasons'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], $validator->validate($batch));

        ImportRow::query()->insert($rows);

        ImportJob::query()->withoutGlobalScopes()->whereKey($job->id)->incrementEach(
            ['processed' => count($rows)],
            ['heartbeat_at' => now()],
        );

        return count($rows);
    }

    private function notify(ImportJob $job, string $stage): void
    {
        User::query()->withTrashed()->find($job->user_id)?->notify(new ImportFinished($job, $stage));
    }
}
