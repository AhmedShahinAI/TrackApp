<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\SectionInstructorHistory;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T175 — reassigning a section (FR-048, SC-018)
|--------------------------------------------------------------------------
|
| Access moves. Authorship does not. That is the whole requirement, and it is easy to get wrong in
| the obvious direction: an implementation that "transfers the section to its new instructor" by
| also rewriting who created its records would look correct on every screen and would have quietly
| destroyed the accountability trail.
|
*/

test('access moves to the new instructor and leaves the old one', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $outgoing = aUser('INSTRUCTOR');
    $incoming = aUser('INSTRUCTOR');

    $section = Section::factory()->create(['instructor_id' => $outgoing->id]);

    expect(test()->actingAs($outgoing->fresh())->getJson("/api/v1/sections/{$section->id}")->status())->toBe(200);

    test()->actingAs($admin)
        ->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $incoming->id])
        ->assertOk()
        ->assertJsonPath('data.instructor.id', $incoming->id);

    test()->actingAs($incoming->fresh())->getJson("/api/v1/sections/{$section->id}")->assertOk();

    // 404, not 403: the section is simply no longer theirs to see (research.md R-01).
    test()->actingAs($outgoing->fresh())->getJson("/api/v1/sections/{$section->id}")->assertNotFound();
});

test('the history gains a closed row and a new open one', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $outgoing = aUser('INSTRUCTOR');
    $incoming = aUser('INSTRUCTOR');

    /*
     * The open row is NOT seeded here any more, and that is the point of the change.
     *
     * This test used to create it by hand, which meant it certified ChangeSectionInstructor against
     * a starting state the application never produced: section creation opened no history row at
     * all, so in reality the first handover left a history that began with the *second* instructor.
     * The test was green for the whole phase over a broken path.
     *
     * The factory now opens the row exactly as SectionController::store does, so what follows is
     * measured against a section that could actually exist.
     */
    $section = Section::factory()->create(['instructor_id' => $outgoing->id]);

    expect(SectionInstructorHistory::query()->where('section_id', $section->id)->count())
        ->toBe(1, 'creating a section with an instructor must open its history');

    test()->actingAs($admin)
        ->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $incoming->id])
        ->assertOk();

    $rows = SectionInstructorHistory::query()
        ->where('section_id', $section->id)->orderBy('id')->get();

    expect($rows)->toHaveCount(2);

    /*
     * Closed, not overwritten. Updating the existing row would answer "who teaches it now" and lose
     * "who taught it in March" — which is the question an investigation actually asks.
     */
    expect($rows[0]->instructor_id)->toBe($outgoing->id)
        ->and($rows[0]->unassigned_at)->not->toBeNull()
        ->and($rows[1]->instructor_id)->toBe($incoming->id)
        ->and($rows[1]->unassigned_at)->toBeNull();
});

test('every existing record keeps its original author', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $outgoing = aUser('INSTRUCTOR');
    $incoming = aUser('INSTRUCTOR');

    $section = Section::factory()->create([
        'instructor_id' => $outgoing->id,
        'created_by' => $outgoing->id,
    ]);

    $student = Student::factory()->create();
    SectionEnrollment::factory()->create([
        'section_id' => $section->id, 'student_id' => $student->id, 'status' => 'active',
    ]);

    // Every audit entry about this section, before the handover.
    $before = DB::table('audit_logs')
        ->where('entity_type', 'Section')->where('entity_id', $section->id)
        ->pluck('user_id', 'id')->all();

    test()->actingAs($admin)
        ->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $incoming->id])
        ->assertOk();

    /*
     * SC-018. Nothing that was written by the outgoing instructor now claims to have been written
     * by the incoming one — not the section's own `created_by`, and not a single audit entry.
     */
    expect($section->fresh()->created_by)->toBe($outgoing->id);

    $after = DB::table('audit_logs')
        ->where('entity_type', 'Section')->where('entity_id', $section->id)
        ->whereIn('id', array_keys($before))
        ->pluck('user_id', 'id')->all();

    expect($after)->toBe($before);
});

test('the change is recorded once, naming both instructors', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $outgoing = aUser('INSTRUCTOR');
    $incoming = aUser('INSTRUCTOR');

    $section = Section::factory()->create(['instructor_id' => $outgoing->id]);

    DB::table('audit_logs')->delete();

    test()->actingAs($admin)
        ->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $incoming->id])
        ->assertOk();

    $actions = DB::table('audit_logs')->where('entity_type', 'Section')->pluck('action')->all();

    // One entry, not `section.updated` beside it (FR-028).
    expect($actions)->toBe(['section.instructor_changed']);

    $entry = DB::table('audit_logs')->where('action', 'section.instructor_changed')->first();

    expect(json_decode((string) $entry->old_values, true)['instructor_id'])->toBe($outgoing->id)
        ->and(json_decode((string) $entry->new_values, true)['instructor_id'])->toBe($incoming->id);
});

test('a section can be left with no instructor', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $outgoing = aUser('INSTRUCTOR');

    $section = Section::factory()->create(['instructor_id' => $outgoing->id]);

    test()->actingAs($admin)
        ->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => null])
        ->assertOk();

    expect($section->fresh()->instructor_id)->toBeNull();

    // The outgoing period still closes: a section with nobody assigned is a real state, and the
    // history has to say when it started.
    $open = SectionInstructorHistory::query()
        ->where('section_id', $section->id)->whereNull('unassigned_at')->count();

    expect($open)->toBe(0);
});

test('reassigning to the same instructor changes nothing', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $instructor = aUser('INSTRUCTOR');

    $section = Section::factory()->create(['instructor_id' => $instructor->id]);

    DB::table('audit_logs')->delete();

    test()->actingAs($admin)
        ->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $instructor->id])
        ->assertOk();

    /*
     * No entry, and the history is untouched — one row, still open, still the founding assignment.
     *
     * Previously asserted as zero rows, which was only true because section creation opened none.
     * Now that it does, "nothing happened" means the existing row is neither closed nor duplicated:
     * a no-op reassignment that closed the open period and reopened an identical one would look
     * like a handover to the same person in every history anyone later reads.
     */
    $rows = SectionInstructorHistory::query()->where('section_id', $section->id)->get();

    expect(DB::table('audit_logs')->where('action', 'section.instructor_changed')->count())->toBe(0)
        ->and($rows)->toHaveCount(1)
        ->and($rows[0]->instructor_id)->toBe($instructor->id)
        ->and($rows[0]->unassigned_at)->toBeNull();
});
