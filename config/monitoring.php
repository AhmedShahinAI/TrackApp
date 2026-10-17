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
    // Phase 25 review, M-1: how many days past its retention a support request may still hold its
    // student before the monitor says the sweep has stopped. The sweep is daily, so 2 tolerates one missed
    // run and a slow one.
    'retention_grace_days' => (int) env('ALERT_RETENTION_GRACE_DAYS', 2),

    'repeat_after_minutes' => (int) env('ALERT_REPEAT_AFTER_MINUTES', 30),

    'email' => env('ALERT_EMAIL'),

    /*
     * SEC-082 (security plan G-20) — security conditions, counted from the audit log over the same
     * window. Any account lock alerts at once; failed sign-ins and refusals alert past a level.
     */
    'login_failures' => (int) env('ALERT_LOGIN_FAILURES', 50),
    'denied_per_user' => (int) env('ALERT_DENIED_PER_USER', 20),

    // SEC-091 (G-18): the header token that unlocks /health's component detail (runbook §8.2).
    'health_token' => env('HEALTH_TOKEN'),

];
