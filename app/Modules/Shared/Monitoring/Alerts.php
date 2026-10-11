<?php

declare(strict_types=1);

namespace App\Modules\Shared\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * PRD 8.8 — raise an operational alert, once per condition per repeat window.
 *
 * Written to the `alerts` log channel at CRITICAL (and so to Sentry, once a DSN is set), and mailed to
 * `monitoring.email` when one is configured. Throttled per key with an atomic cache add: a queue that
 * stays stuck for a night is one alert every half hour, not one every five minutes.
 *
 * Never throws. An alert that fails to send must not take down the job or the request that raised it
 * — the failure is logged where the alert would have gone.
 */
final class Alerts
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function raise(string $key, string $message, array $context = []): bool
    {
        $minutes = max(1, (int) config('monitoring.repeat_after_minutes', 30));

        if (! Cache::add('alerts:sent:'.$key, now()->getTimestamp(), now()->addMinutes($minutes))) {
            return false;
        }

        Log::channel('alerts')->critical($message, ['alert' => $key] + $context);

        $email = config('monitoring.email');

        if (is_string($email) && $email !== '') {
            try {
                Mail::raw($message."\n\n".json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), function ($mail) use ($email, $key): void {
                    $mail->to($email)->subject('[TRACK alert] '.$key);
                });
            } catch (Throwable $e) {
                Log::channel('alerts')->error('alert email could not be sent', ['alert' => $key, 'exception' => $e]);
            }
        }

        return true;
    }
}
