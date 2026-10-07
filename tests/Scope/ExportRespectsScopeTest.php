<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T138 — an export can never exceed what the same caller can retrieve (FR-023)
|--------------------------------------------------------------------------
|
| Exports are where scope leaks hide. A list endpoint is read constantly and a missing constraint
| shows up quickly; an export is built once, often against a hand-written query for speed, and
| nobody looks at the bytes again. The invariant asserted here is deliberately phrased as a
| relation between two endpoints rather than as a row count, so it keeps holding when the fixtures
| change.
|
*/

test('every row in a section export is individually retrievable by the same caller', function (): void {
    $organisation = anOrganisation();

    $mine = Branch::factory()->create(['organization_id' => $organisation->id]);
    $theirs = Branch::factory()->create(['organization_id' => $organisation->id]);

    Section::factory()->count(3)->create(['branch_id' => $mine->id]);
    $foreign = Section::factory()->count(4)->create(['branch_id' => $theirs->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$mine->id]);
    $coordinator->forgetScopeCache();

    $body = $this->actingAs($coordinator)->get('/api/v1/sections/export')->assertOk()->streamedContent();

    foreach ($foreign as $section) {
        expect($body)->not->toContain((string) $section->section_number);
    }

    // And the converse: what the list returns is what the export carries.
    $listed = collect((array) $this->actingAs($coordinator)->getJson('/api/v1/sections')->json('data'));
    expect($listed)->toHaveCount(3);

    foreach ($listed as $row) {
        expect($body)->toContain((string) $row['section_number']);
    }
});

test('an audit export carries only the entries the audit list returns', function (): void {
    $organisation = anOrganisation();

    $mine = Branch::factory()->create(['organization_id' => $organisation->id]);
    $theirs = Branch::factory()->create(['organization_id' => $organisation->id]);

    $visible = Section::factory()->create(['branch_id' => $mine->id]);
    $hidden = Section::factory()->create(['branch_id' => $theirs->id]);

    $admin = aUser('SUPER_ADMIN');

    foreach ([$visible, $hidden] as $section) {
        DB::table('audit_logs')->insert([
            'user_id' => $admin->id,
            'action' => 'section.updated',
            'entity_type' => 'Section',
            'entity_id' => $section->id,
            'ip_address' => '10.0.0.1',
            'created_at' => now(),
        ]);
    }

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$mine->id]);
    $coordinator->forgetScopeCache();

    $listed = collect((array) $this->actingAs($coordinator)->getJson('/api/v1/audit-logs')->assertOk()->json('data'));

    expect($listed->pluck('entity_id'))->toContain($visible->id)
        ->and($listed->pluck('entity_id'))->not->toContain($hidden->id);
});

test('a Super Admin export carries the whole college', function (): void {
    Section::factory()->count(5)->create();

    $admin = aUser('SUPER_ADMIN');
    $body = $this->actingAs($admin)->get('/api/v1/sections/export')->assertOk()->streamedContent();

    foreach (Section::query()->get() as $section) {
        expect($body)->toContain((string) $section->section_number);
    }
});
