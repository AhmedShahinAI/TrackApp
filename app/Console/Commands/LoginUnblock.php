<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Audit\Services\Auditor;
use App\Modules\Authentication\Http\Controllers\LoginController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\RateLimiter;

/**
 * `track:login-unblock {email}` — end an account lock early (SEC-044, security plan G-03).
 *
 * The per-account sign-in limit (LoginController::ACCOUNT_ATTEMPTS) has a known cost: anyone who
 * knows a staff email can hold that account shut for fifteen minutes by failing on purpose. This is
 * the way back in before the window ends, for the operator a locked-out person calls. It clears only
 * the account-wide count; the per-address floor is a minute long and left alone.
 *
 * Audited, because ending a lock is a security decision someone made: `auth.login_unblocked`, on the
 * console channel, with the email it was for.
 */
final class LoginUnblock extends Command
{
    protected $signature = 'track:login-unblock {email : The sign-in email whose account lock to end}';

    protected $description = 'End the per-account sign-in lock for one email, and audit it';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $key = LoginController::accountThrottleKey($email);

        $wasLocked = RateLimiter::tooManyAttempts($key, LoginController::ACCOUNT_ATTEMPTS);
        RateLimiter::clear($key);

        app(Auditor::class)->record('auth.login_unblocked', null, [], [
            'email' => $email,
            'was_locked' => $wasLocked,
            'channel' => 'console',
        ]);

        $this->info($wasLocked
            ? "The account lock for {$email} is ended."
            : "{$email} was not locked; its failure count is reset.");

        return self::SUCCESS;
    }
}
