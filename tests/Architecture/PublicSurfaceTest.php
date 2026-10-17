<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| The public surface is routes/public.php, and nothing else (T355, constitution "Public endpoints")
|--------------------------------------------------------------------------
|
| The constitution allows exactly one public feature, and requires any further public endpoint to be
| an amendment. That only holds if a public route cannot appear by accident — a module's Routes/web.php
| loads on `web`, which authenticates nobody, so a staff route that forgets its `auth` group is public.
|
| So the whole unauthenticated surface is pinned here:
|   - every route without an authentication middleware is either one of the sign-in and
|     infrastructure routes 001 shipped (listed below, each with its reason), or on the `public`
|     group;
|   - every `public` route is unauthenticated, throttled, and served by a controller under a
|     module's Http/Controllers/Public.
|
| A route that fails this is either a staff route missing `auth` — a defect — or a new public
| endpoint, which needs the amendment before it needs this list.
|
*/

/**
 * Unauthenticated by design before Amendment 2 — each a part of signing in, or not a page at all.
 *
 * @return array<string, string> route name or URI => reason
 */
function preAmendmentPublicRoutes(): array
{
    return [
        'login' => 'the sign-in page',
        'login.store' => 'the sign-in form',
        'api.v1.auth.login' => 'sign-in, for an API client',
        'locale.update' => 'the language switch on the sign-in page (FR-074)',
        'sanctum.csrf-cookie' => 'the CSRF cookie a client needs before it can sign in',
        'health' => 'the readiness probe (T276), outside every group',
        'up' => 'the framework liveness probe',
        '/' => 'the root, which redirects to sign-in',
        'dev.components' => 'local only (APP_ENV=local), never registered elsewhere',
        'dev.reference' => 'local only (APP_ENV=local), never registered elsewhere',
    ];
}

/** @return list<string> */
function middlewareOf(RouteDefinition $route): array
{
    return array_map('strval', app('router')->gatherRouteMiddleware($route));
}

function authenticates(RouteDefinition $route): bool
{
    foreach (middlewareOf($route) as $middleware) {
        if (str_starts_with($middleware, Illuminate\Auth\Middleware\Authenticate::class)) {
            return true;
        }
    }

    return false;
}

function isPublicGroup(RouteDefinition $route): bool
{
    return in_array('public', (array) $route->middleware(), true);
}

test('every unauthenticated route is a sign-in or infrastructure route, or on the public group', function (): void {
    $known = preAmendmentPublicRoutes();
    $offenders = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (authenticates($route) || isPublicGroup($route)) {
            continue;
        }

        $key = $route->getName() ?? $route->uri();

        if (! array_key_exists($key, $known)) {
            $offenders[] = implode('|', $route->methods()).' '.$route->uri();
        }
    }

    expect($offenders)->toBe([]);
});

test('every public route is unauthenticated, throttled, and served from a Public controller', function (): void {
    $offenders = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (! isPublicGroup($route)) {
            continue;
        }

        $middleware = middlewareOf($route);
        $controller = (string) $route->getControllerClass();

        if (authenticates($route)
            || ! in_array(Illuminate\Routing\Middleware\ThrottleRequests::class.':public', $middleware, true)
            || ! str_contains($controller, '\\Http\\Controllers\\Public\\')) {
            $offenders[] = $route->uri();
        }
    }

    expect($offenders)->toBe([]);
});

test('a controller under Http/Controllers/Public is only ever reached through the public group', function (): void {
    $offenders = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (str_contains((string) $route->getControllerClass(), '\\Http\\Controllers\\Public\\') && ! isPublicGroup($route)) {
            $offenders[] = $route->uri();
        }
    }

    expect($offenders)->toBe([]);
});

test('the public group is web plus the per-IP throttle, and nothing that resolves a user', function (): void {
    $group = app('router')->getMiddlewareGroups()['public'] ?? null;

    expect($group)->toBe(['web', App\Modules\Shared\Http\Middleware\HideRateLimitState::class, 'throttle:public']);
});

test('the scanner catches an unauthenticated route declared outside routes/public.php', function (): void {
    // The failure the first test exists for: a staff route in a module web.php without `auth`.
    Route::middleware('web')->get('/__probe/forgot-auth', fn () => 'x')->name('probe.forgot-auth');
    Route::getRoutes()->refreshNameLookups();

    $route = Route::getRoutes()->getByName('probe.forgot-auth');

    expect($route)->not->toBeNull()
        ->and(authenticates($route))->toBeFalse()
        ->and(isPublicGroup($route))->toBeFalse()
        ->and(array_key_exists('probe.forgot-auth', preAmendmentPublicRoutes()))->toBeFalse();
});
