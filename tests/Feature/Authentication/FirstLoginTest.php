<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T106 — the first-sign-in recommendation (FR-012)
|--------------------------------------------------------------------------
|
| Shown only when first_login_at was null BEFORE the request. The server sets it during the same
| request, which is why the decision has to be captured from the prior state — once it is written,
| the fact that this was the first sign-in is unrecoverable.
|
*/

test('the recommendation appears on the first sign-in only', function (): void {
    $user = aUser('SUPER_ADMIN', ['first_login_at' => null, 'last_login_at' => null]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026'])
        ->assertSessionHas('first_login', true);

    expect($user->fresh()->first_login_at)->not->toBeNull();
});

test('it does not appear on a later sign-in', function (): void {
    $user = aUser('SUPER_ADMIN', ['first_login_at' => null, 'last_login_at' => null]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026']);
    $this->post('/logout');

    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026'])
        ->assertSessionMissing('first_login');
});

test('the first_login_at stamp is never overwritten by a later sign-in', function (): void {
    $user = aUser('SUPER_ADMIN', ['first_login_at' => null, 'last_login_at' => null]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026']);
    $firstStamp = $user->fresh()->first_login_at;

    $this->post('/logout');
    $this->travel(5)->minutes();
    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026']);

    // The moment of the FIRST sign-in is a historical fact. last_login_at is the one that moves.
    expect($user->fresh()->first_login_at->timestamp)->toBe($firstStamp->timestamp)
        ->and($user->fresh()->last_login_at->timestamp)->toBeGreaterThan($firstStamp->timestamp);
});

test('the banner renders on the landing page and nowhere persists', function (): void {
    $user = aUser('SUPER_ADMIN', ['first_login_at' => null, 'last_login_at' => null]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026']);

    // The flash is read once by the view. A reload must not bring it back — a recommendation that
    // reappears is a recommendation people learn to ignore.
    $this->get('/home')->assertSee(__('auth.first_login.title'));
    $this->get('/home')->assertDontSee(__('auth.first_login.title'));
});

test('the api response reports first_login once', function (): void {
    $user = aUser('SUPER_ADMIN', ['first_login_at' => null, 'last_login_at' => null]);

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Track2026'])
        ->assertOk()
        ->assertJsonPath('data.first_login', true)
        ->assertJsonPath('data.landing_route', '/home');

    $this->postJson('/api/v1/auth/logout');

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Track2026'])
        ->assertJsonPath('data.first_login', false);
});
