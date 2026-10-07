<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;

/*
|--------------------------------------------------------------------------
| T139 — a scope change is in force on the next request (FR-027)
|--------------------------------------------------------------------------
|
| Two caches can defeat this, and they fail in opposite directions, so both are asserted:
|
|   • the per-instance memo on User::coordinatorBranchIds(), which exists so one request does not
|     repeat the same query a dozen times, and which must not survive the request;
|   • Spatie's permission cache, which is shared process-wide and persists across requests by
|     design — a role change that does not flush it leaves the target with their old permissions
|     until the cache expires, which is the stale-permission failure FR-027 names.
|
| "No re-authentication" is the part that matters operationally: revoking a branch from someone who
| is signed in must take effect without waiting for them to sign out.
|
*/

test('a branch added to a coordinator is visible on their next request', function (): void {
    $organisation = anOrganisation();

    $first = Branch::factory()->create(['organization_id' => $organisation->id]);
    $second = Branch::factory()->create(['organization_id' => $organisation->id]);

    Section::factory()->count(2)->create(['branch_id' => $first->id]);
    Section::factory()->count(3)->create(['branch_id' => $second->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$first->id]);

    $this->actingAs($coordinator)->getJson('/api/v1/sections')->assertJsonCount(2, 'data');

    $admin = aUser('SUPER_ADMIN');
    $this->actingAs($admin)
        ->putJson("/api/v1/users/{$coordinator->id}/branches", [
            'branch_ids' => [$first->id, $second->id],
        ])
        ->assertOk();

    // No sign-out, no sign-in: the same person, the next request.
    $this->actingAs($coordinator->fresh())->getJson('/api/v1/sections')->assertJsonCount(5, 'data');
});

test('a branch removed from a coordinator disappears on their next request', function (): void {
    $organisation = anOrganisation();

    $keep = Branch::factory()->create(['organization_id' => $organisation->id]);
    $lose = Branch::factory()->create(['organization_id' => $organisation->id]);

    Section::factory()->count(2)->create(['branch_id' => $keep->id]);
    $gone = Section::factory()->create(['branch_id' => $lose->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$keep->id, $lose->id]);

    $this->actingAs($coordinator)->getJson("/api/v1/sections/{$gone->id}")->assertOk();

    $admin = aUser('SUPER_ADMIN');
    $this->actingAs($admin)
        ->putJson("/api/v1/users/{$coordinator->id}/branches", ['branch_ids' => [$keep->id]])
        ->assertOk();

    $this->actingAs($coordinator->fresh())->getJson("/api/v1/sections/{$gone->id}")->assertNotFound();
});

test('a role change is in force without re-authentication and without a stale permission set', function (): void {
    $user = aUser('INSTRUCTOR');
    $admin = aUser('SUPER_ADMIN');

    // An instructor holds no user.view, so the accounts list is refused.
    $this->actingAs($user)->getJson('/api/v1/users')->assertForbidden();

    $this->actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}/roles", ['roles' => ['SUPER_ADMIN']])
        ->assertOk();

    /*
     * Spatie caches the permission map process-wide. Without an explicit flush in the action this
     * assertion fails while the database is already correct — which is exactly the shape of bug
     * FR-027 is written against, and exactly the shape that never reproduces by hand because a
     * developer's next request is usually a new process.
     */
    $this->actingAs($user->fresh())->getJson('/api/v1/users')->assertOk();
});

test('the per-request memo does not survive into the next request', function (): void {
    $organisation = anOrganisation();

    $first = Branch::factory()->create(['organization_id' => $organisation->id]);
    $second = Branch::factory()->create(['organization_id' => $organisation->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$first->id]);

    expect($coordinator->coordinatorBranchIds())->toBe([$first->id]);

    $coordinator->coordinatorBranches()->sync([$first->id, $second->id]);

    // Same instance, memo intact — this is the behaviour the memo exists for.
    expect($coordinator->coordinatorBranchIds())->toBe([$first->id]);

    // A new instance is a new request, and it must see the truth.
    expect($coordinator->fresh()->coordinatorBranchIds())
        ->toBe(collect([$first->id, $second->id])->sort()->values()->all());
});

/*
|--------------------------------------------------------------------------
| The three ways a scope change failed to take effect
|--------------------------------------------------------------------------
|
| Each of these was measured before it was fixed, and each had the same shape: the role changed,
| the scope did not. They are separated from the tests above because those assert the mechanism
| works; these assert the specific ways it did not.
|
*/

test('demoting a coordinator takes their branch scope with the role', function (): void {
    $organisation = anOrganisation();
    $branch = Branch::factory()->create(['organization_id' => $organisation->id]);

    Section::factory()->count(3)->create(['branch_id' => $branch->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$branch->id]);

    $this->actingAs($coordinator->fresh())->getJson('/api/v1/sections')->assertJsonCount(3, 'data');

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->putJson("/api/v1/users/{$coordinator->id}/roles", ['roles' => ['INSTRUCTOR']])
        ->assertOk();

    /*
     * coordinator_branches is a plain pivot and BranchScope never asks what role the reader holds,
     * so leaving the rows behind kept whole-branch visibility alive under an instructor's role.
     * They now teach nothing, so they see nothing.
     */
    expect($coordinator->fresh()->coordinatorBranchIds())->toBe([]);

    $this->actingAs($coordinator->fresh())->getJson('/api/v1/sections')->assertJsonCount(0, 'data');
});

test('a coordinator can have their last branch revoked', function (): void {
    $organisation = anOrganisation();
    $branch = Branch::factory()->create(['organization_id' => $organisation->id]);
    Section::factory()->count(2)->create(['branch_id' => $branch->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$branch->id]);

    // `required` rejected [], so the last branch could never be taken away through the API.
    $this->actingAs(aUser('SUPER_ADMIN'))
        ->putJson("/api/v1/users/{$coordinator->id}/branches", ['branch_ids' => []])
        ->assertOk();

    expect($coordinator->fresh()->coordinatorBranchIds())->toBe([]);

    $this->actingAs($coordinator->fresh())->getJson('/api/v1/sections')->assertJsonCount(0, 'data');
});

test('the field is still required to be present', function (): void {
    $coordinator = aUser('BRANCH_COORDINATOR');

    // An empty list means "none"; an absent key means the caller forgot, and that stays a 422.
    $this->actingAs(aUser('SUPER_ADMIN'))
        ->putJson("/api/v1/users/{$coordinator->id}/branches", [])
        ->assertStatus(422);
});

test('the last holder of scope.bypass_all cannot be demoted', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)
        ->putJson("/api/v1/users/{$admin->id}/roles", ['roles' => ['INSTRUCTOR']])
        ->assertStatus(422);

    expect($admin->fresh()->can('scope.bypass_all'))->toBeTrue();
});

test('a second holder makes the demotion allowable again', function (): void {
    $first = aUser('SUPER_ADMIN');
    $second = aUser('SUPER_ADMIN');

    $this->actingAs($second)
        ->putJson("/api/v1/users/{$first->id}/roles", ['roles' => ['INSTRUCTOR']])
        ->assertOk();

    expect($first->fresh()->can('scope.bypass_all'))->toBeFalse()
        ->and($second->fresh()->can('scope.bypass_all'))->toBeTrue();
});
