<?php

declare(strict_types=1);

use App\Modules\Shared\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // T276 — outside every group: no session, no cookies, no CSRF for a probe that runs every
            // few seconds. `/up` (Laravel's) stays as the bare liveness check; this is readiness.
            Route::get('/health', App\Modules\Shared\Http\Controllers\HealthController::class)
                ->middleware('throttle:60,1')
                ->name('health');

            // T012 — /api/v1 on the web stack plus Sanctum. The session guard is what authenticates
            // this platform, so these routes need the session and CSRF, not a bearer token.
            // `active` and `session.auth` as on every web group (SEC-031, G-02): without them a
            // deactivated account, or a session left over from a password change, kept the API.
            Route::middleware(['web', 'auth:sanctum', 'active', 'session.auth', 'password.own', 'throttle:api'])
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api_v1.php'));

            // T355 — the public surface (Amendment 2), in its own file so every unauthenticated route
            // of the platform can be read in one place. Loaded on the `public` group defined below
            // — `web` (CSRF through the guest session, the CSP, the locale) plus a per-IP throttle —
            // and never on auth:sanctum. PublicSurfaceTest holds the whole surface to this file.
            Route::middleware('public')->group(base_path('routes/public.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // T275 — first of all, globally: every log line a request writes, including one from a
        // middleware that refuses it, carries the request's id.
        // PRD 8.8 — counts each request's final status for the error-rate alert. Prepended before
        // AssignRequestId is, so the id stays outermost.
        $middleware->prepend(App\Modules\Shared\Monitoring\RequestOutcomes::class);
        $middleware->prepend(App\Modules\Shared\Http\Middleware\AssignRequestId::class);

        // SEC-070, SEC-071 (G-06) — the security headers on the responses `web` never sees: an
        // unmatched URL's 404, /up, /health. Fills in only where no policy was set.
        $middleware->append(App\Modules\Shared\Http\Middleware\EnsureSecurityHeaders::class);

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
            // T277 — outermost in the group, for the reason above: a refusal thrown by a scoped
            // binding or a `can:` check comes back through it as a rendered page, and that page
            // carries the policy too.
            App\Modules\Shared\Http\Middleware\ContentSecurityPolicy::class,
            App\Modules\Authorization\Http\Middleware\AuditDeniedAccess::class,
        ]);

        /*
         * Amendment 2 (T355, review) — the one group a public route may use. Defined once, here, so
         * a route added to routes/public.php cannot silently gain or lose part of its stack:
         *
         *   - `web`: the CSP and security headers (prepended above), CSRF, the locale. CSRF needs a
         *     session to hold its token, so a visitor gets Laravel's GUEST session cookie — what
         *     contracts/support-public.md specifies. It is never an authenticated session: nothing
         *     on this group signs anyone in, and FR-124's "no session" is about a student never
         *     authenticating, which this cannot do;
         *   - HideRateLimitState (Phase 20): wraps the throttle and removes the X-RateLimit-*
         *     counters it adds to every response it lets through — a live readout of the caller's
         *     limit that FR-130 forbids (T378 found it);
         *   - `throttle:public`: a per-IP floor (AppServiceProvider), so no public route is ever
         *     unthrottled. The contract's two-axis submission limits come with the routes.
         *
         * Never auth:sanctum, and nothing that resolves a user.
         */
        $middleware->group('public', [
            'web',
            App\Modules\Shared\Http\Middleware\HideRateLimitState::class,
            'throttle:public',
        ]);

        // ThrottleRequests is on Laravel's middleware priority list and is sorted ahead of anything
        // that is not — which put it outside HideRateLimitState and left its headers in place. On the
        // list, just before the throttle, the order above holds.
        $middleware->prependToPriorityList(
            before: Illuminate\Routing\Middleware\ThrottleRequests::class,
            prepend: App\Modules\Shared\Http\Middleware\HideRateLimitState::class,
        );

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

            // SEC-047 (D-3) — a password someone else set is replaced before anything else is done.
            'password.own' => App\Modules\Authentication\Http\Middleware\EnsurePasswordIsOwn::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // SEC-081 (G-10): never flashed back into the session after a validation failure — the
        // framework's own list, stated here so it cannot shrink unnoticed, plus the CSRF token.
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            '_token',
        ]);

        // PRD 8.8 — error tracking. Inert until SENTRY_LARAVEL_DSN names the university-approved
        // instance; personal data is not sent (config/sentry.php `send_default_pii` false).
        Sentry\Laravel\Integration::handles($exceptions);

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

        /*
         * What the caller asked for, not what the URL looks like.
         *
         * Laravel's default is `$request->is('api/*')`, which is right when /api is a separate stack
         * for machines. Here it is not: /api/v1 runs on the `web` stack with the session and CSRF
         * (see withRouting above) precisely so the screens can post to it, and every Phase 7 dialog
         * does. Under the default, a failed dialog submission answered 422 with a JSON body — the
         * browser showed the JSON, the form never came back, and `$errors` was never populated, so
         * every inline error binding in those dialogs was unreachable.
         *
         * `expectsJson()` is true for an API client (it sends `Accept: application/json`) and false
         * for a form post, which is the distinction that was actually meant. The explicit 404, 403
         * and 409 handlers below keep their own `is('api/*')` checks, so a bare API call still gets
         * a JSON refusal rather than an HTML page.
         */
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->expectsJson());

        /*
         * Phase 23 review — a policy that refuses "as not found" (Response::denyAsNotFound) throws an
         * AuthorizationException with status 404, which Laravel renders as a plain HttpException(404):
         * a different body ("Not Found", in English) and, for /api without Accept, HTML instead of
         * JSON. That difference was the tell: the support queue's refusal to an instructor did not look
         * like a missing route, so it confirmed the queue exists (SC-038). Mapped to the real thing, it
         * takes the NotFoundHttpException path below and is indistinguishable. AuditDeniedAccess has
         * already recorded it by then — it catches the original exception, before this mapping.
         */
        $exceptions->map(
            AuthorizationException::class,
            fn (AuthorizationException $e) => $e->hasStatus() && $e->status() === 404
                ? new NotFoundHttpException($e->getMessage() ?: 'Not Found', $e)
                : $e,
        );

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

        /*
         * A 409 is a business refusal, so a browser gets it back on the form it came from.
         *
         * The academic actions answer 409 for the states a person can actually reach — already
         * withdrawn, a term that does not exist yet, a transition the state machine forbids. For an
         * API client the status is the answer. For the dialogs, which post as ordinary forms, an
         * unhandled 409 renders the generic error page: the person loses the screen they were
         * working in and is told nothing about the one thing that went wrong.
         *
         * Flashed into the default error bag so the dialog shows it the same way it shows a
         * validation failure, because to the person at the keyboard it is the same kind of event.
         */
        $exceptions->render(function (ConflictHttpException $e, Request $request) {
            if ($request->is('api/*') && $request->expectsJson()) {
                return null;
            }

            return back()
                ->withInput()
                ->withErrors(['conflict' => $e->getMessage() ?: __('common.errors.generic')]);
        });
    })->create();
