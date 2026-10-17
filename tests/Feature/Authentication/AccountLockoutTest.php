<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| SEC-040 — guessing is bounded per account, not only per address (security plan G-03)
|--------------------------------------------------------------------------
|
| The constitution's floor is five attempts a minute per email AND address, and it stays. On its own
| it bounds nothing for an attacker with many addresses: each new address is a fresh five a minute
| against the same account, and one IPv6 host holds a whole /64 of addresses. So:
|
|   - an account-level limit: 20 failures in 15 minutes for one email, from anywhere, then 15
|     minutes refused — whatever the password;
|   - the address half of the floor counts an IPv6 /64 as one address, as the support channel does;
|   - both refusals look the same to the caller (FR-009/FR-010: nothing to learn from which limit
|     tripped), and both are audited with the limit that tripped.
|
| The trade-off is recorded in the security plan: someone who knows an email can hold that account
| shut for 15 minutes. `php artisan track:login-unblock {email}` lets a Super Admin's operator end it.
|
*/

/**
 * One failed sign-in for $email, arriving from $ip.
 *
 * @return Illuminate\Testing\TestResponse<Symfony\Component\HttpFoundation\Response>
 */
function failFrom(object $test, string $email, string $ip): Illuminate\Testing\TestResponse
{
    return $test->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'wrong-entirely']);
}

test('twenty failures from twenty addresses lock the account, even against the right password', function (): void {
    $user = aUser('INSTRUCTOR', ['email' => 'target@mu.edu.sa']);

    for ($i = 1; $i <= 20; $i++) {
        failFrom($this, $user->email, "10.0.0.{$i}")->assertStatus(422);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Track2026'])
        ->assertStatus(429)
        ->assertJsonStructure(['message', 'retry_after']);

    $this->assertGuest();
});

test('the account lock lasts about fifteen minutes, not one', function (): void {
    $user = aUser('INSTRUCTOR', ['email' => 'long@mu.edu.sa']);

    for ($i = 1; $i <= 20; $i++) {
        failFrom($this, $user->email, "10.0.0.{$i}");
    }

    $retry = failFrom($this, $user->email, '10.0.2.1')->assertStatus(429)->json('retry_after');

    expect($retry)->toBeGreaterThan(60)->toBeLessThanOrEqual(900);
});

test('addresses in one IPv6 /64 count as one address', function (): void {
    $user = aUser('INSTRUCTOR', ['email' => 'six@mu.edu.sa']);

    for ($i = 1; $i <= 5; $i++) {
        failFrom($this, $user->email, "2001:db8:aa:bb::{$i}")->assertStatus(422);
    }

    failFrom($this, $user->email, '2001:db8:aa:bb::ffff')->assertStatus(429);

    // A different /64 is a different address.
    failFrom($this, $user->email, '2001:db8:aa:cc::1')->assertStatus(422);
});

test('both refusals look the same to the caller', function (): void {
    $byAddress = aUser('INSTRUCTOR', ['email' => 'addr@mu.edu.sa']);
    $byAccount = aUser('INSTRUCTOR', ['email' => 'acct@mu.edu.sa']);

    for ($i = 0; $i < 5; $i++) {
        failFrom($this, $byAddress->email, '10.9.9.9');
    }
    for ($i = 1; $i <= 20; $i++) {
        failFrom($this, $byAccount->email, "10.8.0.{$i}");
    }

    $a = failFrom($this, $byAddress->email, '10.9.9.9')->assertStatus(429)->json();
    $b = failFrom($this, $byAccount->email, '10.7.7.7')->assertStatus(429)->json();

    expect(array_keys($a))->toBe(array_keys($b))
        ->and(preg_replace('/\d+/', 'N', $a['message']))->toBe(preg_replace('/\d+/', 'N', $b['message']));
});

test('each refusal is audited with the limit that tripped', function (): void {
    $user = aUser('INSTRUCTOR', ['email' => 'audited@mu.edu.sa']);

    for ($i = 0; $i < 6; $i++) {
        failFrom($this, $user->email, '10.5.5.5');
    }
    for ($i = 1; $i <= 20; $i++) {
        failFrom($this, $user->email, "10.6.0.{$i}");
    }

    $reasons = DB::table('audit_logs')->where('action', 'auth.login_blocked')->pluck('new_values')
        ->map(fn ($v) => json_decode((string) $v, true)['limit'] ?? null)->unique()->sort()->values()->all();

    expect($reasons)->toBe(['account', 'address']);
});

test('a successful sign-in clears the account count', function (): void {
    $user = aUser('INSTRUCTOR', ['email' => 'clears@mu.edu.sa']);

    for ($i = 1; $i <= 19; $i++) {
        failFrom($this, $user->email, "10.4.0.{$i}");
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.4.1.1'])
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Track2026'])->assertOk();
    $this->postJson('/api/v1/auth/logout');

    // Nineteen more would have been the 38th: refused if the count had survived the success.
    for ($i = 1; $i <= 19; $i++) {
        failFrom($this, $user->email, "10.4.2.{$i}")->assertStatus(422);
    }
});

test('track:login-unblock ends an account lock and is audited', function (): void {
    $user = aUser('INSTRUCTOR', ['email' => 'unblock@mu.edu.sa']);

    for ($i = 1; $i <= 20; $i++) {
        failFrom($this, $user->email, "10.3.0.{$i}");
    }
    failFrom($this, $user->email, '10.3.1.1')->assertStatus(429);

    expect(Artisan::call('track:login-unblock', ['email' => 'Unblock@MU.edu.sa']))->toBe(0);

    failFrom($this, $user->email, '10.3.1.2')->assertStatus(422);

    expect(DB::table('audit_logs')->where('action', 'auth.login_unblocked')->count())->toBe(1);
});
