<?php

declare(strict_types=1);

use App\Modules\Shared\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            SetLocale::class,
        ]);

        /*
         * T148 — PREPENDED, not appended, and the distinction is the whole of whether it works.
         *
         * Both refusals this records are thrown, not returned: `can:` throws AuthorizationException
         * and a scoped route binding throws ModelNotFoundException. An exception propagates upward
         * through the middleware stack, so only a middleware that *wraps* the thrower ever sees it.
         * substituteBindings sits inside the web group, so appending would place this after the
         * binding — and every scope miss would go unrecorded while the 403 path kept working, which
         * is the worst kind of half-working.
         */
        $middleware->web(prepend: [
            App\Modules\Authorization\Http\Middleware\AuditDeniedAccess::class,
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

        /*
         * FR-024, FR-025 — a refusal says nothing about what exists.
         *
         * Laravel turns ModelNotFoundException into a 404 whose message is
         * "No query results for model [App\Models\Section] 1204". That message reaches the client,
         * in production as well as in debug, and it defeats the entire 404-not-403 design: a
         * coordinator probing another branch's ids gets back the model name and the id they asked
         * for, which is confirmation that the route and the record are real.
         *
         * Normalising it here rather than in each controller means a route added later inherits the
         * silence. The status is untouched; only the prose is replaced.
         */
        /*
         * The locale, before any refusal is rendered.
         *
         * SubstituteBindings throws its 404 from inside the web group, before SetLocale has run, so
         * the error view resolved its strings against the default locale — an English-speaking
         * member of staff who mistyped a URL was answered in Arabic, while every other page they
         * saw was English. Asking the middleware's own resolver keeps one answer for the question.
         */
        $exceptions->render(function (Throwable $e, Request $request): void {
            app()->setLocale(SetLocale::resolveFor($request));
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return response()->json(['message' => __('authorization.refusal.missing_title')], 404);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return response()->json(['message' => __('authorization.refusal.forbidden_title')], 403);
        });
    })->create();
