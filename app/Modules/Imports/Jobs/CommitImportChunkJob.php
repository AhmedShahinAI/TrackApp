<?php

declare(strict_types=1);

namespace App\Modules\Imports\Jobs;

use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Section;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Imports\Exports\ImportErrorReportExport;
use App\Modules\Imports\Modes\RegistrarMode;
use App\Modules\Imports\Modes\SectionMode;
use App\Modules\Imports\Notifications\ImportFinished;
use App\Modules\Imports\Pipeline\ImportReasons;
use App\Modules\Imports\Pipeline\Resolver;
use App\Modules\Imports\Policies\ImportJobPolicy;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * T232 — step 5: commit staged rows in 500-row chunks, one transaction each (FR-064, FR-065).
 *
 * ## Self-chaining, one chunk per run
 *
 * Each run commits the next chunk after `last_committed_row_id` and dispatches the next run. Order
 * matters — the checkpoint is "everything up to this id is done" — so the chunks are a chain, not a
 * batch of parallel jobs (research.md R-03). One chunk per job also keeps every job short, so a
 * worker restart loses at most the chunk in flight.
 *
 * ## The checkpoint shares the chunk's transaction
 *
 * The counters and `last_committed_row_id` are advanced by the chunk transaction's **final
 * statement**. Either the chunk's rows and its checkpoint commit together, or neither does. A chunk
 * that dies part-way therefore leaves the checkpoint at the last chunk that finished, its own rows
 * rolled back, and a resume starts exactly where the work stopped — no row committed twice, none
 * skipped. ResumeAfterChunkFailureTest kills a chunk to prove it.
 *
 * ## One bad row does not fail its chunk
 *
 * Each row runs in its own savepoint. A unique-index violation — another import enrolled the same
 * student a moment ago — rolls back that row alone and becomes an error reason on it (R-04). Only
 * something that is not about the row, a lost connection or a bug, fails the chunk.
 *
 * ## Acting as the person who confirmed
 *
 * The rows are created on the operator's behalf, so the audit entries the models write are
 * attributed to them rather than to nobody. The job adopts the job owner as the authenticated user
 * for its own duration and restores whoever was there before — under the sync driver, the request's
 * own user.
 *
 * ## A previously imported file writes nothing (FR-067)
 *
 * When this file was already imported for this term, the operator was warned at upload and chose to
 * continue. Every row is then counted as matched and nothing is created — "proceeding MUST create
 * zero new records" — which is what the approved reference does too. Whether this applies is decided
 * once, when the commit is confirmed, and stored as `link_only`; asked again per chunk, it could
 * switch on halfway through when another import of the same file completed.
 *
 * ## One chain, whatever arrives
 *
 * Each run takes a row lock on the job (`lockForUpdate`) inside its transaction and reads the
 * checkpoint under it. A second chain — a duplicated dispatch, a redelivered job, a resume racing a
 * worker that was only slow — therefore waits, then reads the checkpoint the first one advanced, and
 * either commits the *next* chunk or finds nothing to do. No row is processed twice by two workers.
 *
 * ## Authority is checked every chunk, not only at the door
 *
 * The policy's "run permission and ownership" is enforced at the HTTP edge, but a 50,000-row commit
 * outlives the request by minutes. Each chunk re-checks that the owner still exists, is active, and
 * holds the mode's run permission; if not, the import stops as failed rather than writing more
 * records in the name of someone who may no longer write them.
 *
 * ## A dead worker leaves a resumable job
 *
 * failed() marks the import failed when the queue gives up on a run — a timeout, an exception that
 * escapes. A worker that dies outright runs no handler at all; ImportJob::workerWentQuiet() covers
 * that case by letting a silent `committing` job be resumed.
 */
final class CommitImportChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * One 500-row chunk takes seconds. Kept under the queue's `retry_after`, so a run is never handed
     * to a second worker while the first is still inside its transaction.
     */
    public int $timeout = 80;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $importJobId) {}

    public function handle(): void
    {
        $job = ImportJob::query()->withoutGlobalScopes()->find($this->importJobId);

        if ($job === null || $job->status !== 'committing') {
            return;
        }

        $owner = User::query()->withTrashed()->find($job->user_id);

        if (! $this->stillAuthorised($owner, $job)) {
            $this->stopForLostAuthority($owner, $job);

            return;
        }

        /** @var User $owner */
        $more = $this->actingAs($owner, fn (): bool => $this->commitNextChunk($job));

        if ($more) {
            self::dispatch($job->id);
        }
    }

    /**
     * The queue gave up on this run. The import becomes resumable instead of staying `committing`
     * with nothing queued behind it.
     */
    public function failed(?Throwable $exception): void
    {
        $job = ImportJob::query()->withoutGlobalScopes()->find($this->importJobId);

        if ($job !== null && $job->status === 'committing') {
            $this->stop($job, __('imports.failure.chunk', ['row' => $this->nextRowNumber($job)]));
        }
    }

    private function stillAuthorised(?User $owner, ImportJob $job): bool
    {
        $permission = ImportJobPolicy::RUN_PERMISSIONS[$job->mode] ?? null;

        if ($owner === null || $owner->trashed() || ! $owner->is_active || $permission === null || ! $owner->can($permission)) {
            return false;
        }

        // A section list also needs the section itself: the owner must still teach it or hold a
        // grant (SectionPolicy::importRoster). Unscoped, because the question is about this section,
        // not about whatever the worker's ambient scope happens to be.
        if ($job->mode === SectionMode::NAME) {
            $section = Section::query()->withoutGlobalScopes()->find($job->section_id);

            return $section !== null && $owner->can('importRoster', $section);
        }

        return true;
    }

    /**
     * @return bool whether another chunk follows
     */
    private function commitNextChunk(ImportJob $job): bool
    {
        $firstRow = null;

        try {
            return DB::transaction(function () use ($job, &$firstRow): bool {
                // Under the row lock: see "One chain, whatever arrives".
                $locked = ImportJob::query()->withoutGlobalScopes()->lockForUpdate()->find($job->id);

                if ($locked === null || $locked->status !== 'committing') {
                    return false;
                }

                $locked->forceFill(['heartbeat_at' => now()])->save();

                /** @var Collection<int, ImportRow> $rows */
                $rows = ImportRow::query()
                    ->where('import_job_id', $locked->id)
                    ->where('id', '>', (int) $locked->last_committed_row_id)
                    ->orderBy('id')
                    ->limit(ImportJob::CHUNK_SIZE)
                    ->get();

                if ($rows->isEmpty()) {
                    $this->complete($locked);

                    return false;
                }

                $firstRow = $rows->first()->row_number;

                $resolver = app(Resolver::class, ['job' => $locked]);
                $resolver->prime($rows);

                $tally = ['imported' => 0, 'duplicates' => 0, 'errors' => 0];

                foreach ($rows as $row) {
                    $tally[$this->commitRow($row, $resolver, $locked->link_only)]++;
                }

                // The final statement, by design: see the class docblock.
                ImportJob::query()->withoutGlobalScopes()->whereKey($locked->id)->incrementEach(
                    ['processed' => $rows->count()] + $tally,
                    ['last_committed_row_id' => $rows->last()->id, 'heartbeat_at' => now()],
                );

                return true;
            });
        } catch (Throwable $e) {
            report($e);

            $this->stop($job, __('imports.failure.chunk', ['row' => $firstRow ?? $this->nextRowNumber($job)]));

            return false;
        }
    }

    /**
     * @return 'imported'|'duplicates'|'errors' the counter this row adds to
     */
    private function commitRow(ImportRow $row, Resolver $resolver, bool $linkOnly): string
    {
        // Settled by validation, or by an earlier attempt at this chunk that a resume is repeating.
        if (in_array($row->status, ['error', 'skipped'], true)) {
            return 'errors';
        }

        if ($row->status === 'committed') {
            return 'duplicates';
        }

        if ($linkOnly) {
            $row->forceFill(['status' => 'committed'])->save();

            return 'duplicates';
        }

        try {
            $result = DB::transaction(fn (): array => $resolver->commit($row));
        } catch (UniqueConstraintViolationException) {
            $resolver->discardPending();

            $result = [
                'outcome' => 'error',
                'resolved' => $row->resolved ?? [],
                // Against the row's own identity column: the student for a roster, the code for a
                // course, the email for an account — not a university id a course row does not have.
                'reasons' => [...($row->reasons ?? []), ImportReasons::make(
                    ImportReasons::COMMIT_CONFLICT, $resolver->identityColumn(), (string) ($row->raw[$resolver->identityColumn()] ?? ''),
                )],
            ];
        }

        $row->forceFill([
            'status' => match ($result['outcome']) {
                'imported', 'duplicate' => 'committed',
                'skipped' => 'skipped',
                'error' => 'error',
            },
            'resolved' => $result['resolved'],
            'reasons' => $result['reasons'],
        ])->save();

        return match ($result['outcome']) {
            'imported' => 'imported',
            'duplicate' => 'duplicates',
            'skipped', 'error' => 'errors',
        };
    }

    /**
     * FR-073 — the accountability entry carries the final counts, so it is written when they exist.
     *
     * Runs inside the chunk's transaction and under its lock, so the status change and the entry
     * commit together (constitution II) and a second chain cannot complete the import a second time.
     */
    private function complete(ImportJob $job): void
    {
        $job->forceFill([
            'status' => 'completed',
            'finished_at' => now(),
            'heartbeat_at' => now(),
        ])->save();

        app(Auditor::class)->record('import.committed', $job, [], [
            'file' => $job->original_filename,
            'mode' => $job->mode,
            'term_id' => $job->term_id,
            'processed' => $job->processed,
            'imported' => $job->imported,
            'duplicates' => $job->duplicates,
            'errors' => $job->errors,
        ] + ($job->mode === RegistrarMode::NAME ? [
            // Review M3 — the block this import applied, and any degree or activity it could not
            // apply as written, kept with the accountable entry rather than only on the preview.
            'metadata' => $job->file_metadata['registrar'] ?? null,
            'notices' => $job->summary['notices'] ?? [],
        ] : []));

        // PRD 11.4's flat error table, written once, in the language of the person who ran the import.
        $locale = User::query()->withTrashed()->whereKey($job->user_id)->value('locale');
        ImportErrorReportExport::materialise($job, is_string($locale) && $locale !== '' ? $locale : app()->getLocale());

        DB::afterCommit(fn () => $this->notify($job, 'completed'));
    }

    /**
     * Stop an import whose owner may no longer write, and say so in the audit log.
     *
     * An import cut off part-way — its first chunks committed, the rest refused — otherwise left no
     * entry at all: no `import.committed`, because it never completed, and no refusal, because the
     * check ran in a worker rather than at the HTTP edge where AuditDeniedAccess listens. The entry is
     * `access.denied` (contracts/audit.md has no import-specific refusal), attributed to the owner,
     * and shares a transaction with the status change so neither exists without the other.
     */
    private function stopForLostAuthority(?User $owner, ImportJob $job): void
    {
        $record = function () use ($job): bool {
            return DB::transaction(function () use ($job): bool {
                $updated = ImportJob::query()->withoutGlobalScopes()
                    ->whereKey($job->id)
                    ->where('status', 'committing')
                    ->update([
                        'status' => 'failed',
                        'failure_reason' => __('imports.failure.owner_revoked'),
                        'heartbeat_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    return false;
                }

                app(Auditor::class)->record('access.denied', $job, [], [
                    'reason' => 'import_authority_lost',
                    'mode' => $job->mode,
                    'section_id' => $job->section_id,
                    'file' => $job->original_filename,
                    'committed_rows' => $job->processed,
                ]);

                return true;
            });
        };

        $stopped = $owner === null ? $record() : $this->actingAs($owner, $record);

        if ($stopped) {
            $this->notify($job->refresh(), 'failed');
        }
    }

    /**
     * Stop the import as failed — only if it is still committing, so a run that lost a race cannot
     * mark a completed import failed.
     */
    private function stop(ImportJob $job, string $reason): void
    {
        $updated = ImportJob::query()->withoutGlobalScopes()
            ->whereKey($job->id)
            ->where('status', 'committing')
            ->update(['status' => 'failed', 'failure_reason' => $reason, 'heartbeat_at' => now(), 'updated_at' => now()]);

        if ($updated === 1) {
            $this->notify($job->refresh(), 'failed');
        }
    }

    private function nextRowNumber(ImportJob $job): ?int
    {
        $number = ImportRow::query()
            ->where('import_job_id', $job->id)
            ->where('id', '>', (int) $job->last_committed_row_id)
            ->orderBy('id')
            ->value('row_number');

        return $number === null ? null : (int) $number;
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $work
     * @return T
     */
    private function actingAs(User $owner, Closure $work): mixed
    {
        $guard = Auth::guard();
        $previous = $guard->user();

        $guard->setUser($owner);

        try {
            return $work();
        } finally {
            $previous instanceof Authenticatable ? $guard->setUser($previous) : $guard->forgetUser();
        }
    }

    private function notify(ImportJob $job, string $stage): void
    {
        User::query()->withTrashed()->find($job->user_id)?->notify(new ImportFinished($job, $stage));
    }
}
