<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ImportJob;
use App\Modules\Imports\Support\ImportFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * `track:prune-import-files` — the daily retention sweep for uploaded rosters (SEC-063, G-05).
 *
 * Two kinds of file go:
 *
 *   - the file of an import that completed or failed more than `imports.file_retention_days` ago
 *     (a completed one is normally deleted on completion already; this catches anything that
 *     escaped, e.g. a failure between the commit and the delete);
 *   - a file under imports/ that no import refers to at all — an upload whose job row was never
 *     written, or a copy a test run left behind.
 *
 * In-flight imports (uploaded, mapped, validating, validated, committing) are never touched, however
 * old: their file may still be read. `--dry-run` reports the counts and deletes nothing.
 */
final class PruneImportFiles extends Command
{
    private const FINISHED = ['completed', 'failed'];

    protected $signature = 'track:prune-import-files {--dry-run : Report what would be deleted, delete nothing}';

    protected $description = 'Delete uploaded import files past their retention, and orphaned ones';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $cutoff = now()->subDays((int) config('imports.file_retention_days', 30));
        $expired = 0;

        ImportJob::query()->withoutGlobalScopes()
            ->whereIn('status', self::FINISHED)
            ->where('updated_at', '<', $cutoff)
            ->orderBy('id')
            ->each(function (ImportJob $job) use ($dry, &$expired): void {
                if ($dry) {
                    $expired += Storage::disk(ImportFiles::DISK)->exists($job->file_path) ? 1 : 0;

                    return;
                }

                $expired += ImportFiles::discard($job, 'retention') ? 1 : 0;
            });

        $referenced = array_flip(ImportJob::query()->withoutGlobalScopes()->pluck('file_path')->all());
        $orphans = array_values(array_filter(
            Storage::disk(ImportFiles::DISK)->allFiles('imports'),
            static fn (string $path): bool => ! isset($referenced[$path]),
        ));

        if (! $dry) {
            Storage::disk(ImportFiles::DISK)->delete($orphans);
        }

        $this->info(sprintf('%s: %d expired import file(s), %d orphaned file(s).', $dry ? 'Would delete' : 'Deleted', $expired, count($orphans)));

        return self::SUCCESS;
    }
}
