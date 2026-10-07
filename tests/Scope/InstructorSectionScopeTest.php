<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionAccessGrant;

/*
|--------------------------------------------------------------------------
| T137 — an instructor's own sections plus whatever is shared (FR-022, FR-026)
|--------------------------------------------------------------------------
*/

test('an instructor sees the sections they teach and no others', function (): void {
    $instructor = aUser('INSTRUCTOR');

    $mine = Section::factory()->count(3)->create(['instructor_id' => $instructor->id]);
    Section::factory()->count(5)->create();

    $response = $this->actingAs($instructor)->getJson('/api/v1/sections')->assertOk();

    expect(collect((array) $response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe($mine->pluck('id')->sort()->values()->all());
});

test('a granted section joins the list and is labelled as shared', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $admin = aUser('SUPER_ADMIN');

    $own = Section::factory()->create(['instructor_id' => $instructor->id]);
    $granted = Section::factory()->create();

    SectionAccessGrant::query()->create([
        'section_id' => $granted->id,
        'user_id' => $instructor->id,
        'granted_by' => $admin->id,
        'granted_at' => now(),
    ]);

    $instructor->forgetScopeCache();

    $rows = collect((array) $this->actingAs($instructor)->getJson('/api/v1/sections')->assertOk()->json('data'));

    expect($rows->pluck('id')->sort()->values()->all())
        ->toBe(collect([$own->id, $granted->id])->sort()->values()->all());

    // FR-026: the one reached through a grant is distinguishable from the one they teach.
    expect($rows->firstWhere('id', $granted->id)['shared_access'])->toBeTrue();
    expect($rows->firstWhere('id', $own->id)['shared_access'])->toBeFalse();
});

test('an instructor who teaches nothing receives an empty list, not a refusal', function (): void {
    $instructor = aUser('INSTRUCTOR');
    Section::factory()->count(3)->create();

    $this->actingAs($instructor)
        ->getJson('/api/v1/sections')
        ->assertOk()
        ->assertJsonPath('data', []);
});

test('a revoked grant removes the section on the next request', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $admin = aUser('SUPER_ADMIN');
    $granted = Section::factory()->create();

    $grant = SectionAccessGrant::query()->create([
        'section_id' => $granted->id,
        'user_id' => $instructor->id,
        'granted_by' => $admin->id,
        'granted_at' => now(),
    ]);

    $instructor->forgetScopeCache();
    $this->actingAs($instructor)->getJson("/api/v1/sections/{$granted->id}")->assertOk();

    $grant->delete();

    // A fresh instance is a fresh request: nothing may survive in a memo across the boundary.
    $this->actingAs($instructor->fresh())->getJson("/api/v1/sections/{$granted->id}")->assertNotFound();
});
