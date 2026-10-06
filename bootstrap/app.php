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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
