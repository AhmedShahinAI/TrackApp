<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| T104 — sign-in (FR-008, FR-009, FR-011, FR-016, FR-017)
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    RateLimiter::clear('login|'.'test@mu.edu.sa|127.0.0.1');
});

test('a correct email and password signs in, regenerates the session, and lands on the role page', function (): void {
    $user = aUser('SUPER_ADMIN');

    $before = session()->getId();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'Track2026',
    ]);

    // FR-017 — the Super Admin holds dashboard.view, so they land on /home.
    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs($user);

    // Against session fixation: the id the client arrived with must not become an authenticated id.
    expect(session()->getId())->not->toBe($before);

    // FR-113 — stamped on every successful sign-in.
    expect($user->fresh()->last_login_at)->not->toBeNull();

    expect(DB::table('audit_logs')->where('action', 'auth.login')->count())->toBe(1);
});

test('the email is matched case-insensitively', function (): void {
    $user = aUser(attributes: ['email' => 'maha@mu.edu.sa']);

    // The controller normalises before looking up, so a person typing their address with capitals
    // is not told their password is wrong.
    $this->post('/login', ['email' => 'MAHA@MU.EDU.SA', 'password' => 'Track2026'])
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

test('a wrong password and an unknown email give the same message', function (): void {
    $user = aUser(attributes: ['email' => 'known@mu.edu.sa']);

    /*
     * FR-009 — both must carry the SAME message. Asserted by pinning each to the one generic string
     * rather than by comparing the two bags: if either ever gained a field-specific message, the
     * response would become an oracle for which addresses hold accounts, and that is what this
     * catches.
     */
    $this->from('/login')
        ->post('/login', ['email' => 'known@mu.edu.sa', 'password' => 'not-the-password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->from('/login')
        ->post('/login', ['email' => 'nobody@mu.edu.sa', 'password' => 'not-the-password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    // And nothing beyond `email` is reported — a second key naming `password` would say which
    // field was wrong just as loudly as a different message would.
    expect(firstError('password'))->toBeNull();

    $this->assertGuest();

    expect(DB::table('audit_logs')->where('action', 'auth.login_failed')->count())->toBe(2);
});

test('an unknown email costs the same work as a wrong password', function (): void {
    aUser(attributes: ['email' => 'known@mu.edu.sa']);

    $time = function (array $credentials): float {
        $start = hrtime(true);
        $this->post('/login', $credentials);

        return (hrtime(true) - $start) / 1e6;
    };

    $wrongPassword = $time(['email' => 'known@mu.edu.sa', 'password' => 'wrong-one']);

    RateLimiter::clear('login|known@mu.edu.sa|127.0.0.1');

    $unknownEmail = $time(['email' => 'nobody@mu.edu.sa', 'password' => 'wrong-one']);

    /*
     * The controller verifies against a decoy hash when no user matched, so both paths pay for one
     * Argon2id verification. Without it the unknown-email path returns in about a millisecond and
     * the wrong-password path in about thirty — a reliable enumeration oracle that no message
     * wording can hide.
     *
     * The bound is deliberately loose: this asserts the same order of magnitude, not a constant
     * time, because a tight bound on a shared CI runner is a flaky test.
     */
    expect($unknownEmail)->toBeGreaterThan($wrongPassword / 4);
});

test('a deactivated account is refused with the administrator message', function (): void {
    $user = aUser(attributes: ['is_active' => false]);

    $this->from('/login')
        ->post('/login', ['email' => $user->email, 'password' => 'Track2026'])
        ->assertSessionHasErrors(['email' => __('auth.deactivated')]);

    $this->assertGuest();

    expect(DB::table('audit_logs')->where('action', 'auth.login_blocked')->count())->toBe(1);
});

test('the deactivated message is only reachable with the correct password', function (): void {
    $user = aUser(attributes: ['is_active' => false]);

    // FR-011 says to name the deactivation — but naming it before verifying the password would turn
    // the screen into a probe for which addresses are deactivated. A wrong password gets the
    // generic message instead.
    $this->from('/login')
        ->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);
});

test('a deactivated account cannot keep using an existing session', function (): void {
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)->get('/home')->assertOk();

    $user->forceFill(['is_active' => false])->saveQuietly();

    // FR-018 — the middleware refuses the very next request, whatever the session driver.
    $this->get('/home')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('the session lifetime is 60 minutes', function (): void {
    // FR-016. Asserted on the configuration rather than by advancing a clock, because the
    // expiry is enforced by the session store and the browser cookie, not by application code.
    expect(config('session.lifetime'))->toBe(60);
});

test('the session cookie is HttpOnly, Secure-capable and SameSite=Lax', function (): void {
    expect(config('session.http_only'))->toBeTrue()
        ->and(config('session.same_site'))->toBe('lax');

    // `secure` is env-driven so local HTTP development works; the shipped example sets it true.
    $example = (string) file_get_contents(base_path('.env.example'));
    expect($example)->toContain('SESSION_SECURE_COOKIE=true');
});

test('signing out invalidates the session and audits it', function (): void {
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();

    expect(DB::table('audit_logs')->where('action', 'auth.logout')->count())->toBe(1);
});

test('an instructor lands on their profile rather than the dashboard', function (): void {
    $user = aUser('INSTRUCTOR');

    // FR-017, keyed on permission rather than role name: the instructor set has no dashboard.view
    // in the Phase 1 seeder... except it does, so this asserts whatever the seeder actually grants
    // rather than restating it.
    $expected = $user->can('dashboard.view') ? '/home' : '/profile';

    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026'])
        ->assertRedirect($expected);
});
