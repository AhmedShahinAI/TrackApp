<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| T105 — the login throttle (FR-010)
|--------------------------------------------------------------------------
|
| Five failed attempts for the same email from the same address within one minute; the sixth is
| refused for 60 seconds with a visible countdown.
|
*/

beforeEach(function (): void {
    RateLimiter::clear('login|target@mu.edu.sa|127.0.0.1');
    RateLimiter::clear('login|other@mu.edu.sa|127.0.0.1');
});

test('the sixth attempt within a minute is refused with a countdown', function (): void {
    // The clock stands still, so "exactly 60" cannot become 59 when six sign-ins straddle a second.
    $this->freezeTime();

    aUser(attributes: ['email' => 'target@mu.edu.sa']);

    foreach (range(1, 5) as $attempt) {
        $this->from('/login')
            ->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);
    }

    /*
     * Asserted on the response rather than through the session() helper: after a redirect the
     * errors live on the response's session, and reading the test's own session gives whatever
     * state the last request left behind.
     */
    $this->from('/login')
        ->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'wrong'])
        ->assertSessionHasErrors('email')
        // Not the generic failure — the lockout says something different, with a number in it.
        ->assertSessionDoesntHaveErrors(['email' => __('auth.failed')])
        // FR-010's "visible countdown": the seconds reach the screen, which ticks them down.
        ->assertSessionHas('retry_after', 60);

    expect(DB::table('audit_logs')->where('action', 'auth.login_blocked')->count())->toBe(1);
});

test('the throttle refuses even the correct password once it has tripped', function (): void {
    aUser(attributes: ['email' => 'target@mu.edu.sa']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'wrong']);
    }

    /*
     * The check runs BEFORE the credential check, which is the point: an attacker who guesses
     * correctly on attempt six must not be let in, and must not learn that they guessed correctly.
     */
    $this->from('/login')
        ->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'Track2026']);

    $this->assertGuest();
});

test('the limit is per email and per address together', function (): void {
    aUser(attributes: ['email' => 'target@mu.edu.sa']);
    aUser(attributes: ['email' => 'other@mu.edu.sa']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'wrong']);
    }

    // A second account from the same address is unaffected: keying on IP alone would let one
    // attacker lock out a whole campus behind a NAT.
    $this->from('/login')
        ->post('/login', ['email' => 'other@mu.edu.sa', 'password' => 'wrong'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);
});

test('a successful sign-in clears the counter', function (): void {
    $user = aUser(attributes: ['email' => 'target@mu.edu.sa']);

    foreach (range(1, 4) as $attempt) {
        $this->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'Track2026']);
    $this->assertAuthenticatedAs($user);

    $this->post('/logout');

    // Without the clear, four stale failures plus two fresh ones would lock out someone who had
    // just signed in successfully.
    foreach (range(1, 5) as $attempt) {
        $this->from('/login')
            ->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);
    }
});

test('the api endpoint returns 429 with retry_after', function (): void {
    aUser(attributes: ['email' => 'target@mu.edu.sa']);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'target@mu.edu.sa',
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'target@mu.edu.sa',
        'password' => 'wrong',
    ])
        ->assertStatus(429)
        ->assertJsonStructure(['message', 'retry_after']);
});
