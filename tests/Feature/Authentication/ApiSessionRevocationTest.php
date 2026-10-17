<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| SEC-030 — a revoked session is revoked on /api/v1 too (security plan G-02)
|--------------------------------------------------------------------------
|
| The web stack has always carried `active` (EnsureUserIsActive) and `session.auth`
| (AuthenticateWebSession). The /api/v1 stack did not, so a deactivated account, or a session left
| behind by a password change elsewhere, kept working on every API endpoint its role allowed. The
| database-driver session purge in DeactivateUser and ResetPassword hid this in development and does
| nothing under Redis, which production runs.
|
| These tests sign in for real, through POST /login, so the session carries what a browser's would.
| `actingAs()` would hand the guard a user directly and skip the very session checks under test.
| Between requests the guards are forgotten, as a new HTTP request would find them, so the account is
| re-read from the database instead of reused from memory.
|
*/

/** Sign in through the form and make one web request, which stores the password hash in the session. */
function signInForReal(object $test, User $user): void
{
    $test->post('/login', ['email' => $user->email, 'password' => 'Track2026'])->assertRedirect();
    $test->get('/home')->assertOk();
}

/** What the next HTTP request would see: no user cached on any guard. */
function nextRequest(): void
{
    Auth::forgetGuards();
}

test('a deactivated account is refused on an API read', function (): void {
    $user = aUser('SUPER_ADMIN', ['email' => 'reader@mu.edu.sa']);
    signInForReal($this, $user);

    $this->getJson('/api/v1/auth/me')->assertOk();

    User::query()->whereKey($user->id)->update(['is_active' => false]);
    nextRequest();

    $this->getJson('/api/v1/auth/me')->assertForbidden();
});

test('a deactivated account is refused on an API write, and the write does not happen', function (): void {
    $user = aUser('SUPER_ADMIN', ['email' => 'writer@mu.edu.sa']);
    $other = aUser('INSTRUCTOR');
    signInForReal($this, $user);

    User::query()->whereKey($user->id)->update(['is_active' => false]);
    nextRequest();

    $this->patchJson("/api/v1/users/{$other->id}/deactivate")->assertForbidden();

    expect($other->fresh()->is_active)->toBeTrue();
});

test('the refusal ends the session, so the next API call is unauthenticated', function (): void {
    $user = aUser('SUPER_ADMIN', ['email' => 'ended@mu.edu.sa']);
    signInForReal($this, $user);

    User::query()->whereKey($user->id)->update(['is_active' => false]);
    nextRequest();
    $this->getJson('/api/v1/auth/me')->assertForbidden();

    nextRequest();
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('a session from before a password change is refused on the API', function (): void {
    $user = aUser('SUPER_ADMIN', ['email' => 'changed@mu.edu.sa']);
    signInForReal($this, $user);

    // The password changes somewhere else: an admin reset, or the user on another device.
    User::query()->whereKey($user->id)->update(['password' => Hash::make('A-new-passw0rd!')]);
    nextRequest();

    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('a deactivated account cannot write its language preference (SEC-034)', function (): void {
    $user = aUser('SUPER_ADMIN', ['email' => 'locale@mu.edu.sa', 'locale' => 'ar']);
    signInForReal($this, $user);

    User::query()->whereKey($user->id)->update(['is_active' => false]);
    nextRequest();

    $this->post(route('locale.update'), ['locale' => 'en'])->assertRedirect(route('login'));

    expect($user->fresh()->locale)->toBe('ar');
});

test('a guest can still switch language on the sign-in page (SEC-034)', function (): void {
    $this->from('/login')->post(route('locale.update'), ['locale' => 'en'])->assertRedirect();

    expect(session('locale'))->toBe('en');
});

test('the language switch is throttled (SEC-034)', function (): void {
    for ($i = 0; $i < 30; $i++) {
        $this->from('/login')->post(route('locale.update'), ['locale' => 'en'])->assertRedirect();
    }

    $this->from('/login')->post(route('locale.update'), ['locale' => 'en'])->assertTooManyRequests();
});

test('the API login itself still works for a guest', function (): void {
    aUser('INSTRUCTOR', ['email' => 'guest@mu.edu.sa']);

    $this->postJson('/api/v1/auth/login', ['email' => 'guest@mu.edu.sa', 'password' => 'Track2026'])
        ->assertOk();
});
