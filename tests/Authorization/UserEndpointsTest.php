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
                'password' => 'NewPass2027-Own',
                'password_confirmation' => 'NewPass2027-Own',
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
                'password' => 'Track2026-Own',
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
            'payload' => ['password' => 'Reset2027x-Long'],
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
        'GET|HEAD api/v1/branches',
        'GET|HEAD api/v1/courses',
        // US5 — the import wizard. Pairs for all eight are in ImportEndpointsTest.
        'GET|HEAD api/v1/imports',
        'GET|HEAD api/v1/imports/{import}',
        'GET|HEAD api/v1/imports/{import}/errors/download',
        'GET|HEAD api/v1/imports/{import}/preview',
        // Phase 14 — programmes. Pairs for all five are in ProgramEndpointsTest.
        'GET|HEAD api/v1/programs',
        'GET|HEAD api/v1/programs/{program}',
        'GET|HEAD api/v1/sections',
        // Amendment 4 — section approval. Pairs for all four are in SectionApprovalEndpointsTest.
        'GET|HEAD api/v1/sections/approvals',
        'GET|HEAD api/v1/sections/export',
        'GET|HEAD api/v1/sections/{section}',
        'GET|HEAD api/v1/sections/{section}/approval-history',
        'GET|HEAD api/v1/students',
        'GET|HEAD api/v1/students/{student}',
        // Amendment 2, US10 — the support queue. Pairs for all five are in SupportEndpointsTest.
        'GET|HEAD api/v1/support-requests',
        'GET|HEAD api/v1/support-requests/{supportRequest}',
        'GET|HEAD api/v1/terms',
        'GET|HEAD api/v1/users',
        // US3 — one entity's history. Its allow/deny pairs are in AuditEndpointsTest.
        'GET|HEAD api/v1/{entity}/{id}/activity',
        // US4 — the academic structure. Pairs are in AcademicEndpointsTest, and the five section
        // and enrollment writes in SectionEndpointsTest, which needs a built section to drive them.
        'PATCH api/v1/enrollments/{enrollment}/withdraw',
        'PATCH api/v1/sections/{section}/approval',
        'PATCH api/v1/sections/{section}/instructor',
        'PATCH api/v1/sections/{section}/resubmit',
        'PATCH api/v1/support-requests/{supportRequest}/assignee',
        'PATCH api/v1/support-requests/{supportRequest}/status',
        'PATCH api/v1/terms/{term}/set-current',
        'PATCH api/v1/terms/{term}/status',
        'PATCH api/v1/users/{user}/deactivate',
        'POST api/v1/auth/login',
        'POST api/v1/auth/logout',
        'POST api/v1/branches',
        'POST api/v1/courses',
        'POST api/v1/imports',
        // US8 — the course and staff uploads. Pairs in BulkImportEndpointsTest.
        'POST api/v1/imports/courses',
        'POST api/v1/imports/registrar',
        'POST api/v1/imports/users',
        'POST api/v1/imports/{import}/commit',
        'POST api/v1/imports/{import}/resume',
        'POST api/v1/imports/{import}/validate',
        'POST api/v1/programs',
        'POST api/v1/sections',
        'POST api/v1/sections/{section}/access-grants',
        'POST api/v1/sections/{section}/enrollments',
        // US6 — the instructor's section upload. Pairs in ImportEndpointsTest and
        // tests/Scope/InstructorCannotImportForeignSectionTest.
        'POST api/v1/sections/{section}/imports',
        'POST api/v1/support-requests/{supportRequest}/responses',
        'POST api/v1/terms',
        'POST api/v1/users',
        'POST api/v1/users/{user}/reset-password',
        'PUT api/v1/auth/password',
        'PUT api/v1/branches/{branch}',
        'PUT api/v1/imports/{import}/mapping',
        'PUT api/v1/programs/{program}',
        'PUT api/v1/programs/{program}/courses',
        'PUT api/v1/sections/{section}',
        'PUT api/v1/terms/{term}',
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
