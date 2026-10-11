<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Shared\Monitoring\Alerts;
use App\Modules\Shared\Monitoring\RequestOutcomes;
use Illuminate\Console\Command;
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

    protected $description = 'Check the PRD 8.8 alert thresholds: error rate, queue backlog, disk usage';

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

        $this->line($raised > 0 ? "alerts raised: {$raised}" : 'no new alerts');

        return self::SUCCESS;
    }
}
