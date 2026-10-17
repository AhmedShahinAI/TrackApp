<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * `track:backup [--label=]` — the encrypted database dump and upload copy of runbook §6 (SEC-100).
 *
 * Until now the backup was a snippet in a document, run by whoever remembered: nothing scheduled it,
 * nothing noticed when it stopped, and PRD 8.3's RPO of 24 hours rested on that memory. This is the
 * same procedure, run by the scheduler and watched by `track:monitor`.
 *
 *   1. `mysqldump --single-transaction` | gzip | `openssl enc -aes-256-cbc -pbkdf2` under the key file,
 *      to `<path>/track-<date>[-<label>].sql.gz.enc`. The database password reaches mysqldump through
 *      MYSQL_PWD in its environment, never on a command line another user could read in `ps`.
 *   2. `rsync -a` of storage/app/private to `files_target`, when one is set.
 *   3. On success only: a status marker (read by the monitor) and pruning of daily dumps past
 *      `retention_days`. A labelled dump (a term-closure snapshot) is never pruned.
 *
 * Any failed step exits 1 without touching the marker, so a broken backup ages into the monitor's
 * alert instead of looking fresh. Ordinary files only: there is nothing here to restore — restoring is
 * a person's decision (runbook §6, security plan approval A-8).
 */
final class Backup extends Command
{
    public const STATUS_FILE = 'app/backup-status.json';

    protected $signature = 'track:backup {--label= : Keep this dump outside daily pruning, e.g. term-closure-12}';

    protected $description = 'Write the encrypted database dump and copy the private upload files';

    public function handle(): int
    {
        if (! config('backup.enabled')) {
            $this->warn('Backups are disabled (BACKUP_ENABLED). Nothing was written.');

            return self::SUCCESS;
        }

        $connection = DB::connection();

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('track:backup dumps MySQL or MariaDB; this connection is '.$connection->getDriverName().'.');

            return self::FAILURE;
        }

        $keyFile = (string) config('backup.key_file');
        $dir = rtrim((string) config('backup.path'), '/');

        if (! is_readable($keyFile)) {
            $this->error('The backup key file is not readable: '.$keyFile.'. No dump was written.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($dir, 0750);

        $label = $this->option('label');
        $label = is_string($label) && $label !== '' ? '-'.preg_replace('/[^a-z0-9-]/', '', strtolower($label)) : '';
        $target = $dir.'/track-'.now()->format('Y-m-d-His').$label.'.sql.gz.enc';

        $db = $connection->getConfig();
        $dump = Process::env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])
            ->timeout(3600)
            // bash, not sh: pipefail is what makes a failed mysqldump fail the pipeline instead of
            // producing a valid, encrypted, EMPTY backup.
            ->run(['bash', '-c', sprintf(
                'set -o pipefail; mysqldump --single-transaction --routines --triggers --hex-blob -h %s -P %s -u %s %s | gzip | openssl enc -aes-256-cbc -pbkdf2 -pass file:%s -out %s',
                escapeshellarg((string) ($db['host'] ?? '127.0.0.1')),
                escapeshellarg((string) ($db['port'] ?? '3306')),
                escapeshellarg((string) ($db['username'] ?? '')),
                escapeshellarg((string) ($db['database'] ?? '')),
                escapeshellarg($keyFile),
                escapeshellarg($target),
            )]);

        if ($dump->failed()) {
            File::delete($target);
            $this->error('The database dump failed: '.trim($dump->errorOutput()));

            return self::FAILURE;
        }

        $filesTarget = (string) config('backup.files_target');

        if ($filesTarget !== '') {
            $copy = Process::timeout(3600)->run([
                'rsync', '-a', storage_path('app/private').'/', rtrim($filesTarget, '/').'/'.now()->format('Y-m-d').'/',
            ]);

            if ($copy->failed()) {
                $this->error('The upload files could not be copied: '.trim($copy->errorOutput()));

                return self::FAILURE;
            }
        }

        File::put(storage_path(self::STATUS_FILE), (string) json_encode([
            'finished_at' => now()->toIso8601String(),
            'file' => basename($target),
            'files_copied' => $filesTarget !== '',
        ]));

        $pruned = $this->prune($dir);

        $this->info('Backup written: '.basename($target).($pruned > 0 ? "; {$pruned} old dump(s) pruned" : '').'.');

        return self::SUCCESS;
    }

    /** When the last backup succeeded, or null if none ever has (read by track:monitor). */
    public static function lastSuccess(): ?Carbon
    {
        $raw = @file_get_contents(storage_path(self::STATUS_FILE));
        $finished = is_string($raw) ? (json_decode($raw, true)['finished_at'] ?? null) : null;

        return is_string($finished) ? Carbon::parse($finished) : null;
    }

    /** Daily dumps (no label) older than the retention go; labelled snapshots stay. */
    private function prune(string $dir): int
    {
        $cutoff = now()->subDays((int) config('backup.retention_days', 30))->getTimestamp();
        $pruned = 0;

        foreach (File::glob($dir.'/track-*.sql.gz.enc') as $file) {
            $isDaily = preg_match('/^track-\d{4}-\d{2}-\d{2}-\d{6}\.sql\.gz\.enc$/', basename($file)) === 1;

            if ($isDaily && File::lastModified($file) < $cutoff) {
                File::delete($file);
                $pruned++;
            }
        }

        return $pruned;
    }
}
