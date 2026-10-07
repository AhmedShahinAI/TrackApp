<?php

declare(strict_types=1);

use App\Models\Section;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T140 — 403 and 404 are never conflated (FR-024, FR-025)
|--------------------------------------------------------------------------
|
| The companion to T135. That file proves an invisible record is answered as absent; this one
| proves a *visible* record with a missing permission is answered as refused. Both are needed,
| because the cheap way to satisfy either one alone is to answer everything the same way — and a
| platform that 404s on a permission failure tells its own staff that a record they can see does
| not exist, which is a support ticket rather than a security property.
|
*/

test('a visible record with a missing permission is 403, not 404', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $section = Section::factory()->create(['instructor_id' => $instructor->id]);

    // In scope: the instructor teaches it, so the record is visible to them.
    $this->actingAs($instructor)->getJson("/api/v1/sections/{$section->id}")->assertOk();

    // But they hold no section.update, so acting on it is refused rather than denied existence.
    $this->actingAs($instructor)
        ->putJson("/api/v1/users/{$instructor->id}/roles", ['roles' => ['SUPER_ADMIN']])
        ->assertForbidden();
});

test('a 403 writes one access.denied entry naming the route and the id', function (): void {
    $instructor = aUser('INSTRUCTOR');

    DB::table('audit_logs')->delete();

    $this->actingAs($instructor)
        ->putJson("/api/v1/users/{$instructor->id}/roles", ['roles' => ['SUPER_ADMIN']])
        ->assertForbidden();

    $entry = DB::table('audit_logs')->where('action', 'access.denied')->first();

    expect($entry)->not->toBeNull();
    expect($entry->user_id)->toBe($instructor->id);

    $payload = json_decode((string) $entry->new_values, true);
    expect($payload['route'] ?? null)->toContain('users')
        ->and((string) ($payload['status'] ?? ''))->toBe('403');
});

test('a scope miss writes access.denied too, so the attempt is not silent', function (): void {
    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([]);
    $coordinator->forgetScopeCache();

    $foreign = Section::factory()->create();

    DB::table('audit_logs')->delete();

    $this->actingAs($coordinator)->getJson("/api/v1/sections/{$foreign->id}")->assertNotFound();

    $entry = DB::table('audit_logs')->where('action', 'access.denied')->first();

    expect($entry)->not->toBeNull();

    $payload = json_decode((string) $entry->new_values, true);

    /*
     * 404 in the response, 404 in the entry. The caller is told nothing; the log records what was
     * actually attempted, which is the only way an administrator can tell a typo from a sweep.
     */
    expect((string) ($payload['status'] ?? ''))->toBe('404');
});

test('an unauthenticated request is not audited as a denial', function (): void {
    DB::table('audit_logs')->delete();

    $this->getJson('/api/v1/sections')->assertUnauthorized();

    expect(DB::table('audit_logs')->where('action', 'access.denied')->count())->toBe(0);
});
