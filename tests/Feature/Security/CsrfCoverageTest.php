<?php

declare(strict_types=1);

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T278 — every state-changing route is CSRF-protected (constitution security floor)
|--------------------------------------------------------------------------
|
| Laravel's CSRF check (Laravel 13: PreventRequestForgery — a token, or a same-origin Sec-Fetch-Site
| header from the browser) lives in the `web` group, and every form and every /api/v1 call in this
| application rides on the session, so a POST, PUT, PATCH or DELETE route outside that group — or
| inside it with an `except` entry — is a route another site can make a signed-in browser call.
|
| Two halves. The first reads the route table: every state-changing route resolves the CSRF
| middleware, with no exception list. The second proves the middleware is not a no-op: Laravel skips
| the check while unit tests run, so the refusal is exercised through a subclass that does not.
*/

/** @return list<RouteDefinition> */
function stateChangingRoutes(): array
{
    return array_values(array_filter(
        Route::getRoutes()->getRoutes(),
        static fn (RouteDefinition $route): bool => array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [],
    ));
}

test('every state-changing route resolves the CSRF middleware', function (): void {
    $router = app('router');
    $unprotected = [];

    foreach (stateChangingRoutes() as $route) {
        $middleware = $router->gatherRouteMiddleware($route);
        $protected = collect($middleware)->contains(
            static fn (mixed $m): bool => is_string($m) && is_a(explode(':', $m)[0], PreventRequestForgery::class, true),
        );

        if (! $protected) {
            $unprotected[] = implode('|', $route->methods()).' '.$route->uri();
        }
    }

    expect(stateChangingRoutes())->not->toBe([])
        ->and($unprotected)->toBe([]);
});

test('no route is excepted from the check, and a same-site page is not trusted as same-origin', function (): void {
    $middleware = app(PreventRequestForgery::class);

    expect($middleware->getExcludedPaths())->toBe([])
        ->and((new ReflectionProperty(PreventRequestForgery::class, 'allowSameSite'))->getValue())->toBeFalse();
});

test('a state-changing request without a token is refused', function (string $method): void {
    $middleware = new class(app(), app(Encrypter::class)) extends PreventRequestForgery
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    };

    $request = Request::create('/terms', $method);
    $request->setLaravelSession(app('session')->driver());

    expect(fn () => $middleware->handle($request, fn () => response('should not run')))
        ->toThrow(TokenMismatchException::class);
})->with(['POST', 'PUT', 'PATCH', 'DELETE']);

test('the same request with the session\'s token passes', function (): void {
    $middleware = new class(app(), app(Encrypter::class)) extends PreventRequestForgery
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    };

    $session = app('session')->driver();
    $session->start();

    $request = Request::create('/terms', 'POST', ['_token' => $session->token()]);
    $request->setLaravelSession($session);

    expect($middleware->handle($request, fn () => response('ran'))->getContent())->toBe('ran');
});

test('a cross-site request without a token is refused, whatever else it carries', function (): void {
    $middleware = new class(app(), app(Encrypter::class)) extends PreventRequestForgery
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    };

    // What a hostile page's form submission looks like: the browser marks it cross-site, and it
    // cannot read the session's token to send one.
    $request = Request::create('/terms', 'POST', ['_token' => 'guessed']);
    $request->headers->set('Sec-Fetch-Site', 'cross-site');
    $request->setLaravelSession(app('session')->driver());

    expect(fn () => $middleware->handle($request, fn () => response('should not run')))
        ->toThrow(TokenMismatchException::class);
});

test('the private file tree has no URL, signed or otherwise', function (): void {
    $storageRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(static fn (RouteDefinition $route): bool => str_starts_with($route->uri(), 'storage/'))
        ->map(static fn (RouteDefinition $route): string => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    expect($storageRoutes)->toBe([]);
});

test('reads are not state-changing: no GET route writes', function (): void {
    // The CSRF check does not cover GET, so a GET that changed state would be the hole. The
    // routes that do change state are named; every one is POST, PUT, PATCH or DELETE.
    $writers = collect(Route::getRoutes()->getRoutes())
        ->filter(static fn (RouteDefinition $route): bool => in_array('GET', $route->methods(), true))
        ->map(static fn (RouteDefinition $route): string => (string) $route->getName())
        ->filter(static fn (string $name): bool => preg_match('/\.(store|update|destroy|delete|withdraw|deactivate|commit|confirm|reset|status)$/', $name) === 1)
        ->values()
        ->all();

    expect($writers)->toBe([]);
});
