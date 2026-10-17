<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionEnrollment;

/*
|--------------------------------------------------------------------------
| T438 — visibility follows the student: an enrollment that moves takes the request with it
|--------------------------------------------------------------------------
|
| Scope is resolved on every query, from the student's active enrollments now — never stored on the
| request. So a move is immediate, both ways, with nothing to resync.
|
*/

test('when the active enrollment moves branch, the new coordinator gains the request and the old one loses it', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests/'.$request->reference)->assertOk();
    $this->actingAs($world['outsider'])->getJson('/api/v1/support-requests/'.$request->reference)->assertNotFound();

    // The move: withdrawn from the old branch's section, enrolled in the new one.
    $old = SectionEnrollment::query()->withoutGlobalScopes()->where('student_id', $request->student_id)->sole();
    $old->forceFill(['status' => 'withdrawn', 'withdrawn_at' => now(), 'withdraw_reason' => 'Moved branch'])->saveQuietly();

    SectionEnrollment::factory()->create([
        'student_id' => $request->student_id,
        'section_id' => Section::factory()->create(['branch_id' => $world['other']->id, 'term_id' => $world['term']->id])->id,
    ]);

    $this->actingAs($world['outsider'])->getJson('/api/v1/support-requests/'.$request->reference)->assertOk();
    $this->actingAs($world['outsider'])->getJson('/api/v1/support-requests')->assertJsonPath('meta.total', 1);
});

test('the old coordinator loses it in the same moment', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    $old = SectionEnrollment::query()->withoutGlobalScopes()->where('student_id', $request->student_id)->sole();
    $old->forceFill(['status' => 'withdrawn', 'withdrawn_at' => now(), 'withdraw_reason' => 'Moved branch'])->saveQuietly();

    $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests/'.$request->reference)->assertNotFound();
    $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests')->assertJsonPath('meta.total', 0);
});
