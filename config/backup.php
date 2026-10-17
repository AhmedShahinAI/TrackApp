<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Backups — PRD 8.3, runbook §6 (security plan Phase 10, G-19)
    |--------------------------------------------------------------------------
    |
    | `track:backup` runs the dump the runbook used to describe as a manual snippet: mysqldump in one
    | consistent transaction, gzipped, encrypted with AES-256 under the key file, then the private
    | upload files copied beside it. Daily from the scheduler, and once more when a term is closed.
    |
    | Off unless BACKUP_ENABLED is true, so a developer machine neither tries to dump a database it
    | does not have nor alerts that it never has. .env.production.example turns it on.
    |
    */

    'enabled' => (bool) env('BACKUP_ENABLED', false),

    // Where the encrypted dumps are written. A separate volume or mount, never under the web root.
    'path' => env('BACKUP_PATH', '/backups/track'),

    // The AES key file (runbook §8.4: root:backup 0640, plus an offline copy held by the owner).
    'key_file' => env('BACKUP_KEY_FILE', '/etc/track/backup.key'),

    // rsync destination for storage/app/private, e.g. "backup-host:/track/files". Empty skips it.
    'files_target' => env('BACKUP_FILES_TARGET', ''),

    // Daily dumps older than this are pruned; term-closure snapshots are kept.
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    // track:monitor alerts when the last successful backup is older than this.
    'stale_after_hours' => (int) env('BACKUP_STALE_AFTER_HOURS', 26),
];
