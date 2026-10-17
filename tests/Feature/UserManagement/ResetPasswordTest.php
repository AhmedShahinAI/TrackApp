<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| T110 — a Super Admin resets someone else's password (FR-015)
|--------------------------------------------------------------------------
*/

test('the reset succeeds and the new password works', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR', ['email' => 'target@mu.edu.sa']);

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x-Long'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['reset_at']]);

    expect(Hash::check('Reset2027x-Long', $target->fresh()->password))->toBeTrue();

    $this->post('/logout');

    $this->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'Reset2027x-Long']);
    $this->assertAuthenticatedAs($target->fresh());
});

test('the old password stops working', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR', ['email' => 'target@mu.edu.sa']);

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x-Long']);

    $this->post('/logout');

    $this->from('/login')
        ->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'Track2026'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);
});

test('the password appears in neither audit payload', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x-Long']);

    $entry = DB::table('audit_logs')->where('action', 'user.password_reset')->first();

    expect($entry)->not->toBeNull();

    $payload = ($entry->old_values ?? '').($entry->new_values ?? '');

    // FR-007. The auditable fact is that a reset happened and sessions were revoked — not the
    // credential, which nothing in the log should let a reader reconstruct.
    expect($payload)->not->toContain('Reset2027x-Long')
        ->and($payload)->not->toContain('argon2')
        ->and($payload)->not->toContain('$2y$')
        ->and($payload)->toContain('sessions_revoked');
});

test('the response carries no password field', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $body = $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x-Long'])
        ->getContent();

    expect($body)->not->toContain('Reset2027x-Long')
        ->and($body)->not->toContain('password');
});

test('a password failing the policy is refused', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'weak'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');

    // Unchanged.
    expect(Hash::check('Track2026', $target->fresh()->password))->toBeTrue();
});

test('a caller without user.reset_password is refused', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($instructor)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x-Long'])
        ->assertForbidden();
});

test('no email is sent', function (): void {
    Illuminate\Support\Facades\Mail::fake();

    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x-Long']);

    // Deliberate: there is no SMTP dependency in this feature, and the confirmation dialog tells
    // the Super Admin to pass the password on themselves. A silently-failing mail would leave a
    // colleague locked out with nobody aware.
    Illuminate\Support\Facades\Mail::assertNothingSent();
});

test('a session from before the reset is refused on the API (SEC-035)', function (): void {
    // G-02. The reset's own session purge only works under the database driver; `session.auth` on
    // the API stack is what ends the old session everywhere.
    $target = aUser('INSTRUCTOR', ['email' => 'api-reset@mu.edu.sa']);
    $this->post('/login', ['email' => 'api-reset@mu.edu.sa', 'password' => 'Track2026']);
    $this->get('/home')->assertOk();

    app(App\Modules\UserManagement\Actions\ResetPassword::class)->execute($target->fresh(), 'Reset2027x-Long');
    Illuminate\Support\Facades\Auth::forgetGuards();

    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});
