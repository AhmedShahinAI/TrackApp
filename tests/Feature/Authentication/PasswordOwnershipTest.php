<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Shared\Rules\PasswordPolicy;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| SEC-046, SEC-047 — the owner's password decisions (security plan G-04, D-2 and D-3)
|--------------------------------------------------------------------------
|
| D-2: twelve characters, a letter and a digit, wherever a password is set.
| D-3: a password a Super Admin set — on creation or by reset — is replaced by its owner before they
|      do anything else. Fixture accounts (aUser) are unaffected: the flag defaults to false, and only
|      setting a password for someone raises it.
|
*/

const OWN_PASSWORD = 'My-own-passw0rd';

/** Sign in as a freshly created account, with the password the admin gave it. */
function signInAsNewlyCreated(object $test): User
{
    $admin = aUser('SUPER_ADMIN');

    $test->actingAs($admin)->postJson('/api/v1/users', [
        'name_ar' => 'حساب جديد', 'name_en' => 'New Account', 'email' => 'fresh@mu.edu.sa',
        'password' => 'Admin-chose-2026', 'role' => 'INSTRUCTOR',
    ])->assertCreated();

    Auth::forgetGuards();
    $test->post('/logout');
    $test->post('/login', ['email' => 'fresh@mu.edu.sa', 'password' => 'Admin-chose-2026']);

    return User::query()->where('email', 'fresh@mu.edu.sa')->sole();
}

test('the policy is twelve characters with a letter and a digit (D-2)', function (string $password, bool $passes): void {
    expect(PasswordPolicy::passes($password))->toBe($passes);
})->with([
    'eleven' => ['Abcdefghi12', false],
    'twelve' => ['Abcdefghij12', true],
    'no digit' => ['Abcdefghijkl', false],
    'no letter' => ['123456789012', false],
    'Arabic letters count' => ['كلمةمرورطويلة1', true],
]);

test('an admin-created password of eleven characters is refused (D-2)', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->postJson('/api/v1/users', [
        'name_ar' => 'اسم', 'name_en' => 'Name', 'email' => 'short@mu.edu.sa',
        'password' => 'Short-pass1', 'role' => 'INSTRUCTOR',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

test('a new account must change its password before anything else (D-3)', function (): void {
    $user = signInAsNewlyCreated($this);

    expect($user->password_change_required)->toBeTrue();

    $this->get('/home')->assertRedirect(route('profile.password.edit'));
    $this->get(route('profile.password.edit'))->assertOk()->assertSee(__('auth.password_change.required'));
    $this->getJson('/api/v1/sections')->assertForbidden()->assertJsonPath('code', 'password_change_required');
    $this->getJson('/api/v1/auth/me')->assertOk();
});

test('choosing their own password lifts the requirement (D-3)', function (): void {
    $user = signInAsNewlyCreated($this);

    $this->put('/profile/password', [
        'current_password' => 'Admin-chose-2026',
        'password' => OWN_PASSWORD,
        'password_confirmation' => OWN_PASSWORD,
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->password_change_required)->toBeFalse();

    Auth::forgetGuards();
    $this->get('/home')->assertOk();
});

test('an admin reset raises the requirement again (D-3)', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    expect($target->password_change_required)->toBeFalse();

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset-by-admin-2027'])
        ->assertOk();

    expect($target->fresh()->password_change_required)->toBeTrue();
});

test('sign-out stays reachable while the requirement stands (D-3)', function (): void {
    signInAsNewlyCreated($this);

    $this->post('/logout')->assertRedirect();
    $this->assertGuest();
});

test('existing accounts are not affected by the requirement', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->get('/home')->assertOk();
});
