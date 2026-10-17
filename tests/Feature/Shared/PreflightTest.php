<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SEC-083 — a deploy whose alerts would go nowhere does not pass (security plan G-20)
|--------------------------------------------------------------------------
*/

afterEach(function (): void {
    app()->instance('env', 'testing');
});

test('production fails with no alert address, and with a mailer that only logs', function (array $config, string $says): void {
    app()->instance('env', 'production');
    config($config + ['monitoring.email' => 'oncall@mu.edu.sa', 'mail.default' => 'smtp', 'sentry.dsn' => 'https://k@sentry.example/1']);

    $this->artisan('track:preflight')->expectsOutputToContain($says)->assertFailed();
})->with([
    'no address' => [['monitoring.email' => null], 'ALERT_EMAIL'],
    'log mailer' => [['mail.default' => 'log'], 'MAIL_MAILER'],
]);

test('production passes with a real alert path, and only warns about error tracking', function (): void {
    app()->instance('env', 'production');
    config(['monitoring.email' => 'oncall@mu.edu.sa', 'mail.default' => 'smtp', 'sentry.dsn' => null]);

    $this->artisan('track:preflight')
        ->expectsOutputToContain('SENTRY_LARAVEL_DSN')
        ->expectsOutputToContain('Alert path configured.')
        ->assertSuccessful();
});

test('a developer machine is told, not failed', function (): void {
    app()->instance('env', 'local');
    config(['monitoring.email' => null, 'mail.default' => 'log']);

    $this->artisan('track:preflight')->assertSuccessful();
});
