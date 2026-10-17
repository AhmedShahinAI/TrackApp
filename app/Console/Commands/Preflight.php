<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * `track:preflight` — the parts of runbook §1 a machine can check (SEC-083, security plan G-20).
 *
 * The monitoring is only as good as where its alerts go. With MAIL_MAILER=log, or with ALERT_EMAIL
 * unset, `track:monitor` raises every alert into a file nobody reads, and the deploy looks healthy.
 * So on production and staging this fails the deploy for a dead alert path, and warns for a missing
 * error tracker (which the PRD wants, but whose instance the university has yet to name).
 *
 * Run in the deploy after `config:cache`, so it checks the configuration that will actually serve.
 * Exit 1 fails the deploy step. Elsewhere it reports and exits 0.
 */
final class Preflight extends Command
{
    protected $signature = 'track:preflight';

    protected $description = 'Fail the deploy when alerts would go nowhere; warn when error tracking is off';

    public function handle(): int
    {
        $enforced = app()->environment(['production', 'staging']);
        $errors = [];

        $email = config('monitoring.email');
        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'ALERT_EMAIL is not set to an address: alerts reach no one.';
        }

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $errors[] = 'MAIL_MAILER is "'.config('mail.default').'": alert emails are written to a file, never sent.';
        }

        foreach ($errors as $error) {
            $enforced ? $this->error($error) : $this->warn($error);
        }

        if (blank(config('sentry.dsn'))) {
            $this->warn('SENTRY_LARAVEL_DSN is not set: errors are logged but not tracked (PRD 8.8).');
        }

        if ($errors === []) {
            $this->info('Alert path configured.');
        }

        return $enforced && $errors !== [] ? self::FAILURE : self::SUCCESS;
    }
}
