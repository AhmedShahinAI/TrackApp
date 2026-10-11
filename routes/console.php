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
