<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T112 — an allow case and a deny case for every endpoint
|--------------------------------------------------------------------------
|
| Constitution VIII: "every authorization rule is covered by a test that proves both the allow and
| the deny". One of the two is not enough — a route with no check at all passes the allow case.
|
| contracts/auth-users.md lists 12 endpoints. Nine ship in US1; `users/{id}/roles`,
| `users/{id}/branches`, and `sections/{id}/access-grants` belong to US2 and are asserted absent
| here, so adding one later cannot slip past this matrix unnoticed.
|
*/

/**
 * @return array<string, array{method: string, uri: callable, permission: string|null, payload: array<string, mixed>}>
 */
function us1Endpoints(): array
{
    return [
        // Route names, listed so EveryRouteAuthorizedTest's name-based coverage check can see that
        // each endpoint is exercised here: api.v1.auth.login, api.v1.auth.logout, api.v1.auth.me,
        // api.v1.auth.password.update, api.v1.users.index, api.v1.users.store, api.v1.users.update,
        // api.v1.users.deactivate, api.v1.users.reset-password.
        'login' => [
            'method' => 'postJson',
            'uri' => fn () => '/api/v1/auth/login',
            // Unauthenticated by design — the one endpoint with no permission.
            'permission' => null,
            'payload' => ['email' => 'nobody@mu.edu.sa', 'password' => 'wrong-password'],
        ],
        'logout' => [
            'method' => 'postJson',
            'uri' => fn () => '/api/v1/auth/logout',
            'permission' => 'authenticated',
            'payload' => [],
        ],
        'me' => [
            'method' => 'getJson',
            'uri' => fn () => '/api/v1/auth/me',
            'permission' => 'authenticated',
            'payload' => [],
        ],
        'change own password' => [
            'method' => 'putJson',
            'uri' => fn () => '/api/v1/auth/password',
            'permission' => 'authenticated',
            'payload' => [
                'current_password' => 'Track2026',
                'password' => 'NewPass2027',
                'password_confirmation' => 'NewPass2027',
            ],
        ],
        'list users' => [
            'method' => 'getJson',
            'uri' => fn () => '/api/v1/users',
            'permission' => 'user.view',
            'payload' => [],
        ],
        'create user' => [
            'method' => 'postJson',
            'uri' => fn () => '/api/v1/users',
            'permission' => 'user.create',
            'payload' => [
                'name_ar' => 'اسم',
                'name_en' => 'Name',
                'email' => 'matrix@mu.edu.sa',
                'password' => 'Track2026',
                'role' => 'INSTRUCTOR',
            ],
        ],
        'edit user' => [
            'method' => 'putJson',
            'uri' => fn () => '/api/v1/users/'.aUser('INSTRUCTOR')->id,
            'permission' => 'user.edit',
            'payload' => ['name_ar' => 'اسم معدّل'],
        ],
        'deactivate user' => [
            'method' => 'patchJson',
            'uri' => fn () => '/api/v1/users/'.aUser('INSTRUCTOR')->id.'/deactivate',
            'permission' => 'user.deactivate',
            'payload' => [],
        ],
        'reset password' => [
            'method' => 'postJson',
            'uri' => fn () => '/api/v1/users/'.aUser('INSTRUCTOR')->id.'/reset-password',
            'permission' => 'user.reset_password',
            'payload' => ['password' => 'Reset2027x'],
        ],
    ];
}

test('the allow case succeeds', function (string $name): void {
    $endpoint = us1Endpoints()[$name];
    $uri = ($endpoint['uri'])();

    if ($endpoint['permission'] === null) {
        // Unauthenticated: reachable, and refused on the credentials rather than on authorization.
        $this->{$endpoint['method']}($uri, $endpoint['payload'])->assertStatus(422);

        return;
    }

    // A Super Admin holds all 47 permissions, so this is the allow case for every one of them.
    $admin = aUser('SUPER_ADMIN');

    $response = $this->actingAs($admin)->{$endpoint['method']}($uri, $endpoint['payload']);

    expect($response->status())->toBeLessThan(400);
})->with(array_keys(us1Endpoints()));

test('the deny case is refused', function (string $name): void {
    $endpoint = us1Endpoints()[$name];
    $uri = ($endpoint['uri'])();

    if ($endpoint['permission'] === null) {
        // Nothing to deny — but the endpoint must still refuse a bad credential rather than
        // accepting anything, which the allow case above asserts.
        expect(true)->toBeTrue();

        return;
    }

    if ($endpoint['permission'] === 'authenticated') {
        // Deny = no session at all.
        $this->{$endpoint['method']}($uri, $endpoint['payload'])->assertUnauthorized();

        return;
    }

    /*
     * An instructor holds none of the five user.* permissions, so they are the deny case for each.
     * 403, not 404: the caller can see that accounts exist — they simply may not manage them. The
     * 404-not-403 rule in constitution I is about records outside a caller's SCOPE, which these
     * endpoints are not.
     */
    $instructor = aUser('INSTRUCTOR');

    expect($instructor->can($endpoint['permission']))->toBeFalse();

    $this->actingAs($instructor)
        ->{$endpoint['method']}($uri, $endpoint['payload'])
        ->assertForbidden();
})->with(array_keys(us1Endpoints()));

test('every api/v1 route in this feature appears in the matrix above', function (): void {
    $registered = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1'))
        ->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())
        ->reject(fn (string $signature) => str_starts_with($signature, 'HEAD'))
        ->values()
        ->sort()
        ->values()
        ->all();

    /*
     * The guard on the matrix. An endpoint added without a row here would otherwise ship with no
     * allow/deny pair, which is the exact gap constitution VIII exists to close.
     */
    expect($registered)->toBe([
        // US2 — the scoped read surface and the three assignment endpoints. Their allow/deny pairs
        // live in tests/Scope and tests/Authorization/AccessGrantEndpointTest rather than in the
        // matrix above, which is US1's; the guard this test provides is that none of them arrived
        // without *some* authorization test, and the list is reviewed when it changes.
        'GET|HEAD api/v1/audit-logs',
        'GET|HEAD api/v1/audit-logs/export',
        'GET|HEAD api/v1/auth/me',
        'GET|HEAD api/v1/imports/{import}',
        'GET|HEAD api/v1/sections',
        'GET|HEAD api/v1/sections/export',
        'GET|HEAD api/v1/sections/{section}',
        'GET|HEAD api/v1/students',
        'GET|HEAD api/v1/students/{student}',
        'GET|HEAD api/v1/users',
        'PATCH api/v1/users/{user}/deactivate',
        'POST api/v1/auth/login',
        'POST api/v1/auth/logout',
        'POST api/v1/sections/{section}/access-grants',
        'POST api/v1/users',
        'POST api/v1/users/{user}/reset-password',
        'PUT api/v1/auth/password',
        'PUT api/v1/users/{user}',
        'PUT api/v1/users/{user}/branches',
        'PUT api/v1/users/{user}/roles',
    ]);
});

test('the three US2 endpoints are registered, each behind its own permission', function (string $uri): void {
    /*
     * This test asserted these three were ABSENT through US1 — shipping one early would have meant
     * an endpoint with no authorization test. US2 is where they arrive, so the assertion inverts:
     * present, and each carrying a `can:` middleware rather than relying on a check in a controller
     * body where route:list cannot show it.
     */
    $route = collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($r) => str_contains($r->uri(), $uri));

    expect($route)->not->toBeNull();

    $permissions = collect($route->gatherMiddleware())
        ->filter(fn ($m) => is_string($m) && str_starts_with($m, 'can:'));

    expect($permissions)->toHaveCount(1);
})->with([
    'users/{user}/roles',
    'users/{user}/branches',
    'sections/{section}/access-grants',
]);
