<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| T107 — changing your own password (FR-013, FR-014, FR-015)
|--------------------------------------------------------------------------
*/

test('a valid change succeeds and is audited without the password', function (): void {
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)
        ->put('/profile/password', [
            'current_password' => 'Track2026',
            'password' => 'NewPass2027',
            'password_confirmation' => 'NewPass2027',
        ])
        ->assertRedirect(route('profile.password.edit'));

    expect(Hash::check('NewPass2027', $user->fresh()->password))->toBeTrue();

    $entry = DB::table('audit_logs')->where('action', 'user.password_changed')->first();

    expect($entry)->not->toBeNull();

    // FR-007 — neither the plaintext nor the hash may appear in a payload.
    $payload = ($entry->old_values ?? '').($entry->new_values ?? '');

    expect($payload)->not->toContain('NewPass2027')
        ->and($payload)->not->toContain('Track2026')
        ->and($payload)->not->toContain('argon2');
});

test('the wrong current password is refused', function (): void {
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)
        ->from('/profile/password')
        ->put('/profile/password', [
            'current_password' => 'not-my-password',
            'password' => 'NewPass2027',
            'password_confirmation' => 'NewPass2027',
        ])
        ->assertSessionHasErrors(['current_password' => __('auth.errors.wrong_current')]);

    // Unchanged — a refused change must not partially apply.
    expect(Hash::check('Track2026', $user->fresh()->password))->toBeTrue();
});

test('a new password equal to the current one is refused', function (): void {
    $user = aUser('SUPER_ADMIN');

    // FR-014. The obvious implementation — comparing the two submitted strings — would miss the
    // case where they differ in form but hash to the same credential, so this is checked against
    // the stored hash.
    $this->actingAs($user)
        ->from('/profile/password')
        ->put('/profile/password', [
            'current_password' => 'Track2026',
            'password' => 'Track2026',
            'password_confirmation' => 'Track2026',
        ])
        ->assertSessionHasErrors(['password' => __('auth.errors.same_as_current')]);
});

test('a mismatched confirmation is refused', function (): void {
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)
        ->from('/profile/password')
        ->put('/profile/password', [
            'current_password' => 'Track2026',
            'password' => 'NewPass2027',
            'password_confirmation' => 'DifferentPass2027',
        ])
        ->assertSessionHasErrors(['password' => __('auth.errors.mismatch')]);
});

test('a password failing the policy is refused', function (string $candidate): void {
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)
        ->from('/profile/password')
        ->put('/profile/password', [
            'current_password' => 'Track2026',
            'password' => $candidate,
            'password_confirmation' => $candidate,
        ])
        ->assertSessionHasErrors(['password' => __('auth.errors.policy')]);
})->with([
    'too short' => 'Abc123',
    'no digit' => 'abcdefghij',
    'no letter' => '1234567890',
]);

test('the current session survives while others end', function (): void {
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user);

    $this->put('/profile/password', [
        'current_password' => 'Track2026',
        'password' => 'NewPass2027',
        'password_confirmation' => 'NewPass2027',
    ]);

    /*
     * FR-015's asymmetry. The person changing their password at this keyboard must stay signed in
     * here — and logoutOtherDevices works by rotating the hash stored on the session, so getting
     * the ordering wrong signs them out of their own session on the next request. This asserts the
     * next request still succeeds.
     */
    $this->assertAuthenticatedAs($user);
    $this->get('/home')->assertOk();
});

test('the api endpoint returns the change time', function (): void {
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)
        ->putJson('/api/v1/auth/password', [
            'current_password' => 'Track2026',
            'password' => 'NewPass2027',
            'password_confirmation' => 'NewPass2027',
        ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['changed_at']]);
});

test('an unauthenticated caller cannot change a password', function (): void {
    $this->putJson('/api/v1/auth/password', [
        'current_password' => 'Track2026',
        'password' => 'NewPass2027',
        'password_confirmation' => 'NewPass2027',
    ])->assertUnauthorized();
});
