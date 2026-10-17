<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;

/*
|--------------------------------------------------------------------------
| SEC-072, SEC-073 — production cannot run on a developer configuration (security plan G-12)
|--------------------------------------------------------------------------
*/

/**
 * Run the provider's check as boot() would, in a chosen environment with chosen settings.
 *
 * @param  array<string, mixed>  $config
 */
function checkConfiguration(string $environment, array $config): void
{
    app()->instance('env', $environment);
    config($config);

    $method = new ReflectionMethod(AppServiceProvider::class, 'refuseUnsafeProductionConfiguration');
    $method->invoke(new AppServiceProvider(app()));
}

afterEach(function (): void {
    app()->instance('env', 'testing');
});

const SAFE = ['app.debug' => false, 'session.secure' => true, 'logging.channels.json.level' => 'info'];

test('a safe production configuration boots', function (): void {
    checkConfiguration('production', SAFE);

    expect(true)->toBeTrue();
});

test('production refuses each unsafe setting', function (array $override, string $named): void {
    expect(fn () => checkConfiguration('production', $override + SAFE))
        ->toThrow(RuntimeException::class, $named);
})->with([
    'debug on' => [['app.debug' => true], 'APP_DEBUG'],
    'insecure cookie' => [['session.secure' => false], 'SESSION_SECURE_COOKIE'],
    'debug logging' => [['logging.channels.json.level' => 'debug'], 'LOG_LEVEL'],
]);

test('staging is held to the same rules', function (): void {
    expect(fn () => checkConfiguration('staging', ['app.debug' => true] + SAFE))->toThrow(RuntimeException::class);
});

test('a developer machine is not', function (): void {
    checkConfiguration('local', ['app.debug' => true, 'session.secure' => false, 'logging.channels.json.level' => 'debug']);

    expect(true)->toBeTrue();
});

test('the production example file carries values that pass, and no secret', function (): void {
    $example = (string) file_get_contents(base_path('.env.production.example'));

    expect($example)->toContain("\nAPP_ENV=production\n")
        ->and($example)->toContain("\nAPP_DEBUG=false\n")
        ->and($example)->toContain("\nSESSION_SECURE_COOKIE=true\n")
        ->and($example)->toContain("\nLOG_LEVEL=info\n")
        ->and($example)->not->toContain('DB_USERNAME=root');

    foreach (['APP_KEY', 'DB_PASSWORD', 'REDIS_PASSWORD', 'MAIL_PASSWORD', 'SENTRY_LARAVEL_DSN'] as $secret) {
        expect($example)->toMatch('/^'.$secret.'=$/m');
    }
});
