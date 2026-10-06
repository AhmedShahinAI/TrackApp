<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // T012 — /api/v1 on the web stack plus Sanctum. The session guard is what authenticates
            // this platform, so these routes need the session and CSRF, not a bearer token.
            Route::middleware(['web', 'auth:sanctum', 'throttle:api'])
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api_v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // T094 — appended to the web group, so it runs after the session and authentication have
        // resolved. Prepending it would mean $request->user() is still null and every page renders
        // in the default locale rather than the user's own (FR-074).
        $middleware->web(append: [
            App\Modules\Shared\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            // FR-011, FR-018 — refuses every request from a deactivated account, on any driver.
            'active' => App\Modules\Authentication\Http\Middleware\EnsureUserIsActive::class,

            /*
             * FR-015. AuthenticateSession stores the password hash on the session and rejects any
             * session carrying a stale one, which is how logoutOtherDevices ends other sessions
             * without a per-user session index. Laravel ships it unregistered.
             *
             * An alias applied per route group rather than appended to `web`, because `web` is also
             * the base of the /api/v1 stack — and there `auth:sanctum` has already called
             * Auth::shouldUse('sanctum'), so AuthenticateSession resolves the default guard to
             * Sanctum's RequestGuard, which has no viaRemember(). That was a 500 on every
             * authenticated API request, and route-level withoutMiddleware did not undo it.
             */
            'session.auth' => App\Modules\Authentication\Http\Middleware\AuthenticateWebSession::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
