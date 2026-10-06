<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T111 — absence as a requirement (FR-001)
|--------------------------------------------------------------------------
|
| "The platform MUST NOT offer any means for a person to create their own account, and the
| conventional self-registration address MUST NOT exist."
|
| Absence is the requirement, so it is tested rather than assumed. Without this, the first person to
| install a starter kit that ships a /register route would satisfy every other test in the suite.
|
*/

test('no self-registration or password-recovery route exists', function (string $path): void {
    $this->get($path)->assertNotFound();
})->with([
    '/register',
    '/password/forgot',
    '/password/reset',
    '/forgot-password',
    '/reset-password',
    '/password/confirm',
    '/two-factor-challenge',
    '/user/two-factor-authentication',
]);

test('nor under the api prefix', function (string $path): void {
    $this->getJson($path)->assertNotFound();
})->with([
    '/api/v1/register',
    '/api/v1/auth/register',
    '/api/v1/password/forgot',
    '/api/v1/password/reset',
    '/api/v1/auth/two-factor',
]);

test('posting to them is refused too', function (string $path): void {
    // A GET returning 404 while a POST works is the failure this catches — some kits register only
    // the POST half.
    $this->post($path)->assertNotFound();
})->with([
    '/register',
    '/password/email',
    '/reset-password',
]);

test('the route table names no registration route at all', function (): void {
    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->filter(fn (string $uri) => str_contains($uri, 'register')
            || str_contains($uri, 'forgot')
            || str_contains($uri, 'two-factor'))
        ->values()
        ->all();

    // Stronger than probing paths: this fails even for a route at an address nobody guessed.
    expect($uris)->toBe([]);
});
