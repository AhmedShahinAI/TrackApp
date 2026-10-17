<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SupportRequest;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Monitoring\Alerts;
use App\Modules\Shared\Monitoring\RequestOutcomes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * `track:monitor` — PRD 8.8's periodic alert checks, scheduled every five minutes (routes/console.php).
 *
 * Error rate over the window, queue backlog, disk usage. A failed job alerts on its own, the moment it
 * fails (AppServiceProvider). Each condition alerts once per repeat window (Alerts); the command
 * prints what it measured either way, so a cron log shows the numbers, not only the incidents.
 */
final class Monitor extends Command
{
    protected $signature = 'track:monitor';

    protected $description = 'Check the PRD 8.8 alert thresholds: error rate, queue backlog, disk usage, and overdue support retention';

    public function handle(): int
    {
        $raised = 0;

        // Error rate.
        $window = (int) config('monitoring.window_minutes', 5);
        ['requests' => $requests, 'errors' => $errors] = RequestOutcomes::window($window);
        $rate = $requests > 0 ? $errors / $requests : 0.0;
        $this->line(sprintf('errors: %d of %d requests in %d min (%.2f%%)', $errors, $requests, $window, $rate * 100));

        if ($requests >= (int) config('monitoring.min_requests', 50) && $rate > (float) config('monitoring.error_rate', 0.01)) {
            $raised += (int) Alerts::raise('error_rate', sprintf('Server error rate %.1f%% over the last %d minutes', $rate * 100, $window), compact('requests', 'errors'));
        }

        // Queue backlog.
        $connection = (string) config('queue.default');

        if ($connection !== 'sync') {
            try {
                $backlog = Queue::connection($connection)->size();
                $this->line(sprintf('queue backlog: %d', $backlog));

                if ($backlog > (int) config('monitoring.queue_backlog', 1000)) {
                    $raised += (int) Alerts::raise('queue_backlog', "Queue backlog is {$backlog} jobs", ['connection' => $connection]);
                }
            } catch (Throwable $e) {
                // A queue store that cannot be reached cannot run imports either — that is the alert.
                $this->line('queue backlog: unreachable');
                $raised += (int) Alerts::raise('queue_unreachable', "The queue store ({$connection}) cannot be reached", ['error' => $e->getMessage()]);
            }
        }

        // Disk usage of the volume that holds storage/ — uploads, logs, the sessions of a file driver.
        $total = @disk_total_space(storage_path());
        $free = @disk_free_space(storage_path());

        if (is_float($total) && is_float($free) && $total > 0) {
            $used = 1 - $free / $total;
            $this->line(sprintf('disk usage: %.1f%%', $used * 100));

            if ($used > (float) config('monitoring.disk_usage', 0.80)) {
                $raised += (int) Alerts::raise('disk_usage', sprintf('Disk usage is %.0f%% on the storage volume', $used * 100), ['path' => storage_path()]);
            }
        }

        $raised += $this->checkRetention();
        $raised += $this->checkSecurity($window);
        $raised += $this->checkAuditGrants();
        $raised += $this->checkBackup();

        $this->line($raised > 0 ? "alerts raised: {$raised}" : 'no new alerts');

        return self::SUCCESS;
    }

    /**
     * SEC-082 (security plan G-20) — the security conditions, read from the audit log.
     *
     * The audit log already records every failed sign-in, every lock and every refusal; nothing was
     * watching it. Over the same window as the error rate:
     *
     *   - any `auth.login_blocked` — an account or address hit its limit, which is either an attack or
     *     a locked-out colleague, and either way someone should know now;
     *   - failed sign-ins above `monitoring.login_failures` — guessing spread thin enough to trip no
     *     single limit still shows up in the total;
     *   - one account drawing more than `monitoring.denied_per_user` refusals — someone walking ids.
     *
     * Each alert carries counts and user ids, never an email or an address: the alert channel is read
     * by operations, and the audit log is where the details already are.
     */
    private function checkSecurity(int $window): int
    {
        $since = now()->subMinutes($window);
        $raised = 0;

        try {
            $blocked = DB::table('audit_logs')->where('action', 'auth.login_blocked')->where('created_at', '>=', $since)->count();
            $failed = DB::table('audit_logs')->where('action', 'auth.login_failed')->where('created_at', '>=', $since)->count();
            $prowlers = DB::table('audit_logs')
                ->where('action', 'access.denied')
                ->where('created_at', '>=', $since)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                // Counted here, not with a raw HAVING (constitution: no raw SQL); one window's refusals.
                ->countBy()
                ->filter(static fn (int $n): bool => $n > (int) config('monitoring.denied_per_user', 20))
                ->keys()
                ->all();
        } catch (Throwable $e) {
            return (int) Alerts::raise('security_unreadable', 'The audit log could not be read for the security checks', ['error' => $e::class]);
        }

        $this->line(sprintf('security: %d blocked, %d failed sign-ins, %d accounts over the refusal level', $blocked, $failed, count($prowlers)));

        if ($blocked > 0) {
            $raised += (int) Alerts::raise('login_blocked', "{$blocked} sign-in attempt(s) refused by the lockout in the last {$window} minutes", ['count' => $blocked]);
        }

        if ($failed > (int) config('monitoring.login_failures', 50)) {
            $raised += (int) Alerts::raise('login_failures', "{$failed} failed sign-ins in the last {$window} minutes", ['count' => $failed]);
        }

        if ($prowlers !== []) {
            $raised += (int) Alerts::raise('access_denied_spike', count($prowlers).' account(s) refused unusually often in the last '.$window.' minutes', ['user_ids' => array_map('intval', $prowlers)]);
        }

        return $raised;
    }

    /**
     * SEC-102 (G-19) — the backup is fresh. A backup that stopped is only discovered at restore time
     * otherwise, which is the one moment it cannot be fixed. Only where backups are enabled.
     */
    private function checkBackup(): int
    {
        if (! config('backup.enabled')) {
            return 0;
        }

        $last = Backup::lastSuccess();
        $hours = (int) config('backup.stale_after_hours', 26);
        $this->line('backup: '.($last === null ? 'never succeeded' : 'last success '.$last->diffForHumans()));

        return $last === null || $last->lt(now()->subHours($hours))
            ? (int) Alerts::raise('backup_stale', $last === null ? 'No backup has ever succeeded' : "The last successful backup is older than {$hours} hours", ['last_success' => $last?->toIso8601String()])
            : 0;
    }

    /**
     * SEC-082 — the audit log is still append-only for the application's database user.
     *
     * `track:audit-grants --strict` gates every deploy, but a grant restored by hand between deploys
     * would go unnoticed until the next one. The same two no-op statements, every run, on MySQL or
     * MariaDB; a driver without grants has nothing to check.
     */
    private function checkAuditGrants(): int
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return 0;
        }

        $allowed = AuditGrants::mutationsAllowed(static fn (string $sql): mixed => DB::connection()->statement($sql));

        return $allowed === []
            ? 0
            : (int) Alerts::raise('audit_grants_drift', 'The application database user can '.implode(' and ', $allowed).' audit_logs', ['allowed' => $allowed]);
    }

    /**
     * Support retention overdue (FR-159, SC-037; Phase 25 review, M-1).
     *
     * A failed sweep already alerts, as every failed job does. This catches the silent cases: the
     * scheduler or the workers stopped, or the job was never dispatched. Any request still holding its
     * student more than `monitoring.retention_grace_days` past its retention means no sweep has run.
     * It is one indexed EXISTS, using (anonymised_at, created_at).
     */
    private function checkRetention(): int
    {
        try {
            $months = Settings::int(Settings::SUPPORT_RETENTION_MONTHS);
            $overdue = now()->subMonthsNoOverflow(max(1, $months))
                ->subDays(max(1, (int) config('monitoring.retention_grace_days', 2)));

            $late = SupportRequest::query()->withoutGlobalScopes()
                ->whereNull('anonymised_at')
                ->where('created_at', '<=', $overdue)
                ->exists();
        } catch (Throwable $e) {
            $this->line('support retention: unreadable');

            return (int) Alerts::raise('support_retention_unreadable', 'Support retention could not be checked', ['error' => $e->getMessage()]);
        }

        $this->line('support retention: '.($late ? 'OVERDUE' : 'on time'));

        return $late
            ? (int) Alerts::raise('support_retention_overdue', 'Support requests past their retention have not been anonymised — the daily sweep is not running', ['retention_months' => $months])
            : 0;
    }
}
