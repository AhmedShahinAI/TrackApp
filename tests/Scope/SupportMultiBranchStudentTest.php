<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionEnrollment;

/*
|--------------------------------------------------------------------------
| T436 — a student enrolled in two branches is visible to both coordinators (FR-141)
|--------------------------------------------------------------------------
|
| The rule widens rather than narrows: a support request is about the platform, not about one section.
|
*/

test('both branches\' coordinators see the request, and can open and answer it', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    SectionEnrollment::factory()->create([
        'student_id' => $request->student_id,
        'section_id' => Section::factory()->create(['branch_id' => $world['other']->id, 'term_id' => $world['term']->id])->id,
    ]);

    foreach (['coordinator', 'outsider'] as $who) {
        $this->actingAs($world[$who])->getJson('/api/v1/support-requests')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reference', $request->display_reference);

        $this->actingAs($world[$who])->getJson('/api/v1/support-requests/'.$request->reference)->assertOk();
    }
});

test('a withdrawn second enrollment does not widen it', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    SectionEnrollment::factory()->withdrawn()->create([
        'student_id' => $request->student_id,
        'section_id' => Section::factory()->create(['branch_id' => $world['other']->id, 'term_id' => $world['term']->id])->id,
    ]);

    $this->actingAs($world['outsider'])->getJson('/api/v1/support-requests')->assertJsonPath('meta.total', 0);
});
