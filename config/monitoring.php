<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Alert thresholds — PRD 8.8
|--------------------------------------------------------------------------
|
| "Alerts when the error rate exceeds 1% over 5 minutes, a queue job fails, the queue backlog exceeds
| 1,000 jobs, or disk usage exceeds 80%." Checked by `track:monitor` (scheduled every five minutes)
| and, for failed jobs, the moment one fails. Alerts are written to the `alerts` log channel at
| CRITICAL — which Sentry also captures once a DSN is set — and mailed to ALERT_EMAIL when it is set.
|
*/

return [

    'error_rate' => (float) env('ALERT_ERROR_RATE', 0.01),

    'window_minutes' => (int) env('ALERT_WINDOW_MINUTES', 5),

    // Below this many requests in the window, a rate is noise: two errors in three requests at 3 a.m.
    // is not an incident.
    'min_requests' => (int) env('ALERT_MIN_REQUESTS', 50),

    'queue_backlog' => (int) env('ALERT_QUEUE_BACKLOG', 1000),

    'disk_usage' => (float) env('ALERT_DISK_USAGE', 0.80),

    // One alert per condition per this many minutes, so a stuck queue is one message, not 288 a day.
    'repeat_after_minutes' => (int) env('ALERT_REPEAT_AFTER_MINUTES', 30),

    'email' => env('ALERT_EMAIL'),

];
