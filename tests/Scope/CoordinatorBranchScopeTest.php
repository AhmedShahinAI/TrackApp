<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;

/*
|--------------------------------------------------------------------------
| T136 — a coordinator's lists and their totals (FR-022)
|--------------------------------------------------------------------------
|
| The totals are asserted alongside the rows deliberately. A list that filters correctly while its
| count is computed from an unscoped query still discloses the size of another branch — the leak is
| smaller but it is the same leak, and a count is the easiest thing to compute off the scoped path
| by accident.
|
*/

test('a coordinator with two branches sees exactly those two branches of sections', function (): void {
    $organisation = anOrganisation();

    $first = Branch::factory()->create(['organization_id' => $organisation->id]);
    $second = Branch::factory()->create(['organization_id' => $organisation->id]);
    $other = Branch::factory()->create(['organization_id' => $organisation->id]);

    $mine = collect([$first, $second])->flatMap(
        fn (Branch $b) => Section::factory()->count(2)->create(['branch_id' => $b->id]),
    );
    Section::factory()->count(3)->create(['branch_id' => $other->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$first->id, $second->id]);
    $coordinator->forgetScopeCache();

    $response = $this->actingAs($coordinator)->getJson('/api/v1/sections')->assertOk();

    expect(collect((array) $response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe($mine->pluck('id')->sort()->values()->all());

    // The total travels with the page and must come off the same scoped query.
    expect($response->json('meta.total'))->toBe(4);
});

test('the scoped count on the landing page excludes other branches', function (): void {
    $organisation = anOrganisation();

    $mine = Branch::factory()->create(['organization_id' => $organisation->id]);
    $theirs = Branch::factory()->create(['organization_id' => $organisation->id]);

    Section::factory()->count(2)->create(['branch_id' => $mine->id]);
    Section::factory()->count(7)->create(['branch_id' => $theirs->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$mine->id]);
    $coordinator->forgetScopeCache();

    $this->actingAs($coordinator)
        ->get('/home')
        ->assertOk()
        ->assertSee('2')
        ->assertDontSee('>9<', false);

    expect(Section::query()->count())->toBe(2);
});

test('a coordinator with no branches sees nothing rather than everything', function (): void {
    Section::factory()->count(4)->create();

    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([]);
    $coordinator->forgetScopeCache();

    $response = $this->actingAs($coordinator)->getJson('/api/v1/sections')->assertOk();

    /*
     * An empty branch list must mean an empty result, not an absent constraint. `whereIn('x', [])`
     * is a false predicate in SQL, which is the behaviour wanted here — but an implementation that
     * skips the constraint when the list is empty would hand this caller the whole college, and the
     * mistake is easy to make while trying to avoid a pointless query.
     */
    expect($response->json('data'))->toBe([]);
});
