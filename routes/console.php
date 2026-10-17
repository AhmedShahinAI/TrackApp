<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// PRD 8.8 — the periodic alert checks (error rate, queue backlog, disk). Needs the scheduler's cron
// entry on the server: `* * * * * php artisan schedule:run` (docs/runbook-deploy.md §2.3).
Schedule::command('track:monitor')->everyFiveMinutes()->withoutOverlapping()->onOneServer();

// T452 (FR-159, research.md R-12) — the support-request retention sweep. Daily and queued; the period is
// the `support_retention_months` setting (default 24), counted from each request's creation.
Schedule::job(new App\Modules\Support\Jobs\AnonymiseExpiredRequestsJob)->daily()->name('support:anonymise')->onOneServer();

// SEC-063 (G-05) — uploaded roster files past `imports.file_retention_days`, and orphans, are deleted.
Schedule::command('track:prune-import-files')->daily()->withoutOverlapping()->onOneServer();

// SEC-101 (G-19, PRD 8.3) — the encrypted backup at 02:00 Asia/Riyadh (23:00 UTC); off unless BACKUP_ENABLED.
Schedule::command('track:backup')->dailyAt('23:00')->withoutOverlapping()->onOneServer();
