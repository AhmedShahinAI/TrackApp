<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T109 — deactivating an account (FR-011, FR-018, FR-049)
|--------------------------------------------------------------------------
*/

test('deactivation flips the flag and is audited', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($admin)
        ->patchJson("/api/v1/users/{$target->id}/deactivate", ['reason' => 'انتهاء التعاقد'])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($target->fresh()->is_active)->toBeFalse();

    $entry = DB::table('audit_logs')->where('action', 'user.deactivated')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->new_values)->toContain('انتهاء التعاقد');
});

test('the row is not erased', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($admin)->patchJson("/api/v1/users/{$target->id}/deactivate");

    /*
     * FR-049. Deactivation is not deletion, and not even a soft delete: the row stays fully
     * visible to an administrator, with deleted_at null. The distinction matters because a
     * soft-deleted user disappears from every relation query, and their name would stop rendering
     * on the records they created — which is precisely what FR-018 forbids.
     */
    $row = User::query()->find($target->id);

    expect($row)->not->toBeNull()
        ->and($row->deleted_at)->toBeNull()
        ->and($row->is_active)->toBeFalse();
});

test('the deactivated person cannot sign in afterwards', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('INSTRUCTOR', ['email' => 'target@mu.edu.sa']);

    $this->actingAs($admin)->patchJson("/api/v1/users/{$target->id}/deactivate");

    $this->post('/logout');

    $this->from('/login')
        ->post('/login', ['email' => 'target@mu.edu.sa', 'password' => 'Track2026'])
        ->assertSessionHasErrors(['email' => __('auth.deactivated')]);

    $this->assertGuest();
});

test('an existing session stops working on the very next request', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('SUPER_ADMIN');

    // The target is signed in and working.
    $this->actingAs($target)->get('/home')->assertOk();

    // Someone else deactivates them mid-session.
    $this->actingAs($admin)->patchJson("/api/v1/users/{$target->id}/deactivate");

    /*
     * FR-018's "end all of that person's active sessions". Purging session rows only works under
     * the database driver, so the guarantee is carried by EnsureUserIsActive, which refuses the
     * next request whatever the driver. This is the assertion that would fail if someone removed
     * the middleware and trusted the row purge alone.
     *
     * `fresh()`, because a real next request reads the account from the database. Until SEC-031 this
     * passed with the stale in-memory $target only by accident: the admin's request had left the
     * shared test session carrying the wrong password hash, so `session.auth` signed it out, and
     * EnsureUserIsActive was never what refused it.
     */
    $this->actingAs($target->fresh())->get('/home')->assertRedirect(route('login'));
});

test('their name still renders on records they created', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $author = aUser('INSTRUCTOR', ['name_ar' => 'أ. سلمان الرشيدي']);

    $section = Section::factory()->create(['created_by' => $author->id]);

    $this->actingAs($admin)->patchJson("/api/v1/users/{$author->id}/deactivate");

    // FR-018. The relation still resolves, so every screen that shows an author keeps showing one.
    expect($section->fresh()->creator)->not->toBeNull()
        ->and($section->fresh()->creator->name_ar)->toBe('أ. سلمان الرشيدي');
});

test('their audit entries keep their actor', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $target = aUser('SUPER_ADMIN');

    // The target does something auditable first.
    $this->actingAs($target)->post('/logout');

    $before = DB::table('audit_logs')->where('user_id', $target->id)->count();

    expect($before)->toBeGreaterThan(0);

    $this->actingAs($admin)->patchJson("/api/v1/users/{$target->id}/deactivate");

    // The entries still name them. A hard delete with a cascading foreign key would have taken
    // these with it, which is the loss FR-049 exists to prevent.
    expect(DB::table('audit_logs')->where('user_id', $target->id)->count())
        ->toBeGreaterThanOrEqual($before);
});

test('a Super Admin cannot deactivate their own account', function (): void {
    $admin = aUser('SUPER_ADMIN');

    /*
     * 409, not 403: the caller holds the permission, and the request is refused for what it would
     * do. A Super Admin locking themselves out of the only account that can create accounts is not
     * recoverable from inside the platform.
     */
    $this->actingAs($admin)
        ->patchJson("/api/v1/users/{$admin->id}/deactivate")
        ->assertStatus(409);

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('a caller without user.deactivate is refused', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $target = aUser('INSTRUCTOR');

    $this->actingAs($instructor)
        ->patchJson("/api/v1/users/{$target->id}/deactivate")
        ->assertForbidden();

    expect($target->fresh()->is_active)->toBeTrue();
});

test('a deactivated session is refused on the API too, whatever the session driver (SEC-035)', function (): void {
    /*
     * G-02. The test above proves /home; this proves /api/v1, which had neither `active` nor
     * `session.auth` until SEC-031. Signed in for real (not actingAs), and the action run as the admin
     * would run it, so the database-only session purge in DeactivateUser plays its part and is shown
     * not to be what holds.
     */
    $target = aUser('SUPER_ADMIN', ['email' => 'api-target@mu.edu.sa']);
    $this->post('/login', ['email' => 'api-target@mu.edu.sa', 'password' => 'Track2026']);
    $this->getJson('/api/v1/auth/me')->assertOk();

    app(App\Modules\UserManagement\Actions\DeactivateUser::class)->execute($target->fresh(), 'test');
    Illuminate\Support\Facades\Auth::forgetGuards();

    $this->getJson('/api/v1/auth/me')->assertForbidden();
});
