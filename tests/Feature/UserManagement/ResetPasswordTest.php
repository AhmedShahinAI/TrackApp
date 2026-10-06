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
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['reset_at']]);

    expect(Hash::check('Reset2027x', $target->fresh()->password))->toBeTrue();

    $this->post('/logout');

    $this->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'Reset2027x']);
    $this->assertAuthenticatedAs($target->fresh());
});

test('the old password stops working', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR', ['email' => 'target@mu.edu.sa']);

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x']);

    $this->post('/logout');

    $this->from('/login')
        ->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'Track2026'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);
});

test('the password appears in neither audit payload', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x']);

    $entry = DB::table('audit_logs')->where('action', 'user.password_reset')->first();

    expect($entry)->not->toBeNull();

    $payload = ($entry->old_values ?? '').($entry->new_values ?? '');

    // FR-007. The auditable fact is that a reset happened and sessions were revoked — not the
    // credential, which nothing in the log should let a reader reconstruct.
    expect($payload)->not->toContain('Reset2027x')
        ->and($payload)->not->toContain('argon2')
        ->and($payload)->not->toContain('$2y$')
        ->and($payload)->toContain('sessions_revoked');
});

test('the response carries no password field', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $body = $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x'])
        ->getContent();

    expect($body)->not->toContain('Reset2027x')
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
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x'])
        ->assertForbidden();
});

test('no email is sent', function (): void {
    Illuminate\Support\Facades\Mail::fake();

    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => 'Reset2027x']);

    // Deliberate: there is no SMTP dependency in this feature, and the confirmation dialog tells
    // the Super Admin to pass the password on themselves. A silently-failing mail would leave a
    // colleague locked out with nobody aware.
    Illuminate\Support\Facades\Mail::assertNothingSent();
});
