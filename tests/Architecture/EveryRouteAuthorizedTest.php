<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T053 — every API route is authorized, and every one is tested
|--------------------------------------------------------------------------
|
| Constitution VIII. A route added without an authorization check is the single most likely way this
| platform leaks one branch's roster to another branch's coordinator, and it is invisible in review:
| the endpoint works, returns data, and looks finished.
|
| Two separate gates, because they fail differently. The first catches a route with no check at all.
| The second catches a route that has a check nobody has ever verified — the more common case, since
| an unchecked route usually breaks a demo, while an incorrectly-checked one does not.
|
| There are no /api/v1 routes yet; they arrive with the user stories in Phases 4 onward. Both gates
| are written now so the first route to land is already covered, and the third test below proves the
| gates would actually fire rather than passing on an empty list.
|
*/

/**
 * @return list<RouteDefinition>
 */
function apiRoutes(): array
{
    $routes = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (str_starts_with($route->uri(), 'api/v1')) {
            $routes[] = $route;
        }
    }

    return $routes;
}

/**
 * Routes whose authorization rule is "you are you".
 *
 * A third category the original gate did not model. `auth/me` returns the caller's own identity,
 * `auth/password` changes the caller's own password, `auth/logout` ends the caller's own session:
 * there is no permission to check, because the record IS the caller. `auth/login` is
 * unauthenticated by design (FR-001 — the one endpoint anyone may reach).
 *
 * Listed by name rather than allowed by pattern, so adding a self-scoped endpoint is a visible,
 * reviewable edit here and not a hole that a URL shape happens to fall through. Each is covered by
 * an allow/deny pair in tests/Authorization/UserEndpointsTest.php, where the deny case is "no
 * session at all".
 *
 * @var list<string>
 */
const SELF_SCOPED_ROUTES = [
    'api.v1.auth.login',
    'api.v1.auth.logout',
    'api.v1.auth.me',
    'api.v1.auth.password.update',
];

/**
 * Whether a route carries an authorization gate of its own.
 *
 * `auth:sanctum` is authentication, not authorization, and does not count — that is the distinction
 * the constitution draws and the one a leak depends on.
 *
 * @param  list<string>  $middleware
 */
function hasAuthorizationGate(array $middleware): bool
{
    foreach ($middleware as $name) {
        if (str_starts_with($name, 'can:') || str_starts_with($name, 'permission:') || str_starts_with($name, 'role:')) {
            return true;
        }
    }

    return false;
}

/**
 * Whether the action behind a route authorizes in its own body — `$this->authorize(...)`, a
 * `Gate::authorize`, a `denyAs404`, a controller helper named authorize…(), or a typed form request
 * (whose `authorize()` Laravel runs before the action). A controller that does this is correct; it
 * simply cannot be seen from the route definition.
 *
 * SEC-053 (G-09): judged on the route's own METHOD, not its file. A file-wide match let an
 * unprotected action pass because a different action in the same controller authorized.
 */
function actionAuthorizesInternally(RouteDefinition $route): bool
{
    $action = $route->getActionName();

    if (! str_contains($action, '@')) {
        return false;
    }

    [$class, $method] = explode('@', $action, 2);

    if (! class_exists($class) || ! method_exists($class, $method)) {
        return false;
    }

    $reflection = new ReflectionMethod($class, $method);

    foreach ($reflection->getParameters() as $parameter) {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()
            && is_subclass_of($type->getName(), Illuminate\Foundation\Http\FormRequest::class)) {
            return true;
        }
    }

    $file = $reflection->getFileName();

    if ($file === false) {
        return false;
    }

    $lines = file($file) ?: [];
    $body = implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

    return preg_match('/\b(authorize\w*|denyAs404)\s*\(/', $body) === 1;
}

/**
 * SEC-053 (G-09) — authenticated web pages whose rule is "you are you", by name, with the reason.
 *
 * The web stack was outside this gate entirely; a page added without `can:` was invisible to it.
 * These are the pages that legitimately have no permission: each shows or changes only the caller's
 * own account, or is the page every signed-in person lands on.
 *
 * @var array<string, string>
 */
const SELF_SCOPED_WEB_ROUTES = [
    'home' => 'the landing page; its panels are filtered by permission and scope inside',
    'profile' => 'the caller\'s own profile',
    'profile.preferences' => 'the caller\'s own preferences',
    'profile.password.edit' => 'the caller\'s own password form',
    'profile.password.update' => 'the caller\'s own password change (current password required)',
    'logout' => 'ends the caller\'s own session',
];

/**
 * Authenticated web routes: carry `auth`, and are not the API (gated above) or the dev gallery.
 *
 * @return list<RouteDefinition>
 */
function authenticatedWebRoutes(): array
{
    $routes = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (str_starts_with($route->uri(), 'api/')) {
            continue;
        }

        if (in_array('auth', $route->gatherMiddleware(), true)) {
            $routes[] = $route;
        }
    }

    return $routes;
}

test('every authenticated web route carries an authorization check (SEC-053)', function (): void {
    $offenders = [];

    foreach (authenticatedWebRoutes() as $route) {
        if (array_key_exists((string) $route->getName(), SELF_SCOPED_WEB_ROUTES)) {
            continue;
        }

        /** @var list<string> $middleware */
        $middleware = $route->gatherMiddleware();

        if (hasAuthorizationGate($middleware) || actionAuthorizesInternally($route)) {
            continue;
        }

        $offenders[] = implode('|', $route->methods()).' '.$route->uri().' ('.($route->getName() ?? 'unnamed').')';
    }

    expect($offenders)->toBe([]);
});

test('the web gate fires on an unprotected page, and every web exception exists (SEC-053, SEC-054)', function (): void {
    // The planted route SEC-054 asks for: authenticated, no `can:`, a closure. It must be caught.
    Route::middleware(['web', 'auth'])->get('__web_architecture_probe', fn (): string => 'leak')->name('__web_probe');
    Route::getRoutes()->refreshNameLookups();

    $caught = collect(authenticatedWebRoutes())->first(fn ($route) => $route->uri() === '__web_architecture_probe');

    expect($caught)->not->toBeNull()
        ->and(hasAuthorizationGate($caught->gatherMiddleware()))->toBeFalse()
        ->and(actionAuthorizesInternally($caught))->toBeFalse()
        ->and(array_key_exists('__web_probe', SELF_SCOPED_WEB_ROUTES))->toBeFalse();

    $names = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->getName())->filter()->all();

    expect(array_values(array_diff(array_keys(SELF_SCOPED_WEB_ROUTES), $names)))->toBe([]);
});

test('every api/v1 route carries an authorization check', function (): void {
    $offenders = [];

    foreach (apiRoutes() as $route) {
        /** @var list<string> $middleware */
        $middleware = $route->gatherMiddleware();

        if (in_array($route->getName(), SELF_SCOPED_ROUTES, true)) {
            continue;
        }

        if (hasAuthorizationGate($middleware) || actionAuthorizesInternally($route)) {
            continue;
        }

        $offenders[] = implode('|', $route->methods()).' '.$route->uri();
    }

    expect($offenders)->toBe([]);
});

test('every api/v1 route is named and exercised by an authorization test', function (): void {
    // An unnamed route cannot be referenced by a test, so naming is part of the gate rather than a
    // style preference.
    $unnamed = [];
    $untested = [];

    $authorizationTests = '';

    if (is_dir(base_path('tests/Authorization'))) {
        foreach (Finder::create()->files()->in(base_path('tests/Authorization'))->name('*.php') as $file) {
            $authorizationTests .= (string) file_get_contents((string) $file->getRealPath());
        }
    }

    foreach (apiRoutes() as $route) {
        $name = $route->getName();

        if ($name === null || $name === '') {
            $unnamed[] = implode('|', $route->methods()).' '.$route->uri();

            continue;
        }

        if (! str_contains($authorizationTests, $name)) {
            $untested[] = $name;
        }
    }

    expect($unnamed)->toBe([]);
    expect($untested)->toBe([], 'These routes have no authorization test naming them.');
});

test('both gates fire on a route that should fail them', function (): void {
    // Registered inside the test and never persisted. Without this, both gates above would pass on
    // an empty route list and keep passing after the first real route arrived unprotected.
    Route::middleware(['web', 'auth:sanctum'])
        ->get('api/v1/__architecture_probe', fn (): string => 'leak');

    Route::getRoutes()->refreshNameLookups();

    $probe = null;

    foreach (apiRoutes() as $route) {
        if ($route->uri() === 'api/v1/__architecture_probe') {
            $probe = $route;
        }
    }

    expect($probe)->not->toBeNull();

    /** @var list<string> $middleware */
    $middleware = $probe->gatherMiddleware();

    // auth:sanctum alone must not satisfy the authorization gate.
    expect(hasAuthorizationGate($middleware))->toBeFalse()
        ->and(actionAuthorizesInternally($probe))->toBeFalse()
        ->and($probe->getName())->toBeNull();

    // And a properly gated route must satisfy it.
    expect(hasAuthorizationGate(['web', 'auth:sanctum', 'can:section.view']))->toBeTrue();
});

test('every self-scoped exception names a route that exists', function (): void {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->all();

    $stale = array_values(array_diff(SELF_SCOPED_ROUTES, $names));

    expect($stale)->toBe([], 'These self-scoped exceptions name routes that no longer exist.');
});

test('a self-scoped route still refuses an unauthenticated caller', function (): void {
    // The exception is from the PERMISSION check, not from authentication. Every one of them must
    // still be behind auth:sanctum, or "you are you" is satisfied by being nobody.
    $unprotected = [];

    foreach (apiRoutes() as $route) {
        if (! in_array($route->getName(), SELF_SCOPED_ROUTES, true)) {
            continue;
        }

        if ($route->getName() === 'api.v1.auth.login') {
            continue;  // Unauthenticated by design (FR-001).
        }

        /** @var list<string> $middleware */
        $middleware = $route->gatherMiddleware();

        $authenticated = collect($middleware)->contains(
            fn (string $name) => str_contains($name, 'Authenticate') || str_starts_with($name, 'auth'),
        );

        if (! $authenticated) {
            $unprotected[] = $route->getName();
        }
    }

    expect($unprotected)->toBe([]);
});
