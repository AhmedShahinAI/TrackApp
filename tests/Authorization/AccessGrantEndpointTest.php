<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionAccessGrant;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T142 — POST /sections/{id}/access-grants, allow and deny (FR-026)
|--------------------------------------------------------------------------
|
| Constitution VIII: every authorization test asserts the allow *and* the deny. A test that only
| proves the happy path passes just as well against an endpoint with no permission check at all.
|
*/

test('a Super Admin grants an instructor access to one section', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $instructor = aUser('INSTRUCTOR');
    $section = Section::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $instructor->id])
        ->assertCreated()
        ->assertJsonPath('data.section_id', $section->id)
        ->assertJsonPath('data.user_id', $instructor->id);

    expect(SectionAccessGrant::query()
        ->where('section_id', $section->id)
        ->where('user_id', $instructor->id)
        ->exists())->toBeTrue();

    // FR-026: the section is now in that instructor's list, labelled.
    $rows = collect((array) $this->actingAs($instructor->fresh())->getJson('/api/v1/sections')->json('data'));
    expect($rows->firstWhere('id', $section->id)['shared_access'])->toBeTrue();
});

test('the grant is audited with the section and the recipient', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $instructor = aUser('INSTRUCTOR');
    $section = Section::factory()->create();

    DB::table('audit_logs')->delete();

    $this->actingAs($admin)
        ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $instructor->id])
        ->assertCreated();

    $entry = DB::table('audit_logs')->where('action', 'section.access_granted')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($admin->id)
        ->and($entry->entity_id)->toBe($section->id);

    expect(json_decode((string) $entry->new_values, true)['user_id'] ?? null)->toBe($instructor->id);
});

/*
 * The two deny cases below each use a section the actor can SEE, and that is the point.
 *
 * The first drafts used a section neither of them taught, and both returned 404 rather than 403 —
 * correctly. Route-model binding resolves through the scoped query (R-01), so an invisible section
 * is absent before `can:` ever runs. A 403 there would have been the bug.
 *
 * Testing the permission therefore requires removing the scope question first: give the actor a
 * section they genuinely see, and what remains is purely "do they hold section.access_override".
 */
test('an instructor cannot grant access, even on a section they teach', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $section = Section::factory()->create(['instructor_id' => $instructor->id]);

    // Visible to them: the same id answers 200 on the read endpoint.
    $this->actingAs($instructor)->getJson("/api/v1/sections/{$section->id}")->assertOk();

    $this->actingAs($instructor)
        ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $instructor->id])
        ->assertForbidden();

    expect(SectionAccessGrant::query()->count())->toBe(0);
});

test('a coordinator cannot grant access on a section in their own branch', function (): void {
    $organisation = anOrganisation();
    $branch = App\Models\Branch::factory()->create(['organization_id' => $organisation->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$branch->id]);
    $coordinator->forgetScopeCache();

    $instructor = aUser('INSTRUCTOR');
    $section = Section::factory()->create(['branch_id' => $branch->id]);

    $this->actingAs($coordinator)->getJson("/api/v1/sections/{$section->id}")->assertOk();

    $this->actingAs($coordinator)
        ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $instructor->id])
        ->assertForbidden();
});

test('a section the caller cannot see is 404, not 403 — the scope answers first', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $foreign = Section::factory()->create();

    $this->actingAs($instructor)
        ->postJson("/api/v1/sections/{$foreign->id}/access-grants", ['user_id' => $instructor->id])
        ->assertNotFound();
});

test('an unauthenticated request is refused', function (): void {
    $section = Section::factory()->create();

    $this->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => 1])
        ->assertUnauthorized();
});

test('a duplicate grant is refused with 422 rather than silently repeated', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $instructor = aUser('INSTRUCTOR');
    $section = Section::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $instructor->id])
        ->assertCreated();

    $this->actingAs($admin)
        ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $instructor->id])
        ->assertStatus(422);

    expect(SectionAccessGrant::query()->count())->toBe(1);
});

test('the target must hold the instructor role', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $coordinator = aUser('BRANCH_COORDINATOR');
    $section = Section::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $coordinator->id])
        ->assertStatus(422);
});

test('a deactivated or deleted recipient is a 422, not a 404', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create();

    $deactivated = aUser('INSTRUCTOR');
    $deactivated->forceFill(['is_active' => false])->saveQuietly();

    $deleted = aUser('INSTRUCTOR');
    $deleted->delete();

    /*
     * `exists:users,id` ignores the soft-delete scope, so both of these passed validation and then
     * findOrFail() threw — the caller saw a 404 that reads as "no such section" and an access.denied
     * entry was written against a section they were entitled to act on. The request body is what is
     * wrong, so the answer is 422, and it is the same 422 for both: which of the two it is would
     * answer "does this id belong to a real account" for anyone holding the permission.
     */
    foreach ([$deactivated, $deleted] as $target) {
        $this->actingAs($admin)
            ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $target->id])
            ->assertStatus(422);
    }

    expect(SectionAccessGrant::query()->count())->toBe(0);
});
