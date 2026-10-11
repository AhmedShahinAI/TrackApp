<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use Illuminate\Support\Facades\Queue;

/*
|--------------------------------------------------------------------------
| T211 — the preview (FR-061)
|--------------------------------------------------------------------------
|
| Create-versus-match counts, warning and error counts, the first 100 rows each with a status, and
| 409 while validation has not finished.
|
| The counts are distinct-entity counts, not row counts: forty rows for one new section are one new
| section. A preview that said "40 new sections" would be the operator's first reason to distrust
| the whole screen.
|
*/

test('the summary separates what will be created from what will be matched', function (): void {
    $world = importWorld();
    // An existing section with one student already enrolled, and one known student not yet in it.
    $section = Section::factory()->create([
        'term_id' => $world['term']->id,
        'branch_id' => $world['branch']->id,
        'course_id' => $world['course']->id,
        'program_id' => $world['program']->id,
        'section_number' => '131',
        'instructor_id' => $world['instructor']->id,
    ]);

    $enrolled = Student::factory()->create(['university_id' => '441000010', 'name_ar' => 'مسجل مسبقًا']);
    SectionEnrollment::factory()->create(['section_id' => $section->id, 'student_id' => $enrolled->id, 'status' => 'active']);
    $known = Student::factory()->create(['university_id' => '441000011', 'name_ar' => 'معروف']);

    $rows = [
        rosterRow($world, ['university_id' => '441000010', 'student_name' => 'مسجل مسبقًا']), // match, match
        rosterRow($world, ['university_id' => '441000011', 'student_name' => 'معروف']),       // match section + student, new enrollment
        ...rosterRows($world, 3, ['section_number' => '132']),                                  // one new section, three new students
        rosterRow($world, ['university_id' => 'bad']),                                          // error
        rosterRow($world, ['university_id' => '441000011', 'student_name' => 'اسم آخر', 'section_number' => '133']), // warning: name mismatch
    ];

    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile($rows), $world['term']->id));

    $summary = $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))
        ->assertOk()
        ->json('data.summary');

    expect($summary['total_rows'])->toBe(7)
        ->and($summary['valid'])->toBe(5)
        ->and($summary['warnings'])->toBe(1)
        ->and($summary['errors'])->toBe(1)
        ->and($summary['will_create'])->toBe(['sections' => 2, 'students' => 3, 'enrollments' => 5, 'courses' => 0, 'instructors' => 0])
        ->and($summary['will_match'])->toBe(['sections' => 1, 'students' => 2, 'enrollments' => 1]);
});

test('the first 100 rows are returned, each with its own status', function (): void {
    $world = importWorld();
    $rows = rosterRows($world, 120);
    $rows[4]['university_id'] = 'bad';

    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile($rows), $world['term']->id));

    $response = $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    expect($response->json('data.rows'))->toHaveCount(100)
        ->and($response->json('meta'))->toBe(['per_page' => 100, 'total' => 120])
        ->and($response->json('data.rows.0.row_number'))->toBe(2)
        ->and($response->json('data.rows.0.status'))->toBe('valid')
        ->and($response->json('data.rows.4.status'))->toBe('error')
        ->and($response->json('data.rows.99.row_number'))->toBe(101);

    foreach ($response->json('data.rows') as $row) {
        expect($row)->toHaveKeys(['row_number', 'status', 'values', 'reasons']);
    }
});

test('the preview is refused with 409 while validation is unfinished', function (): void {
    $world = importWorld();
    Queue::fake();

    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile(rosterRows($world, 2)), $world['term']->id));

    expect($job->status)->toBe('validating');

    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertConflict();
});

test('the commit is refused with 409 until the preview has been opened', function (): void {
    $world = importWorld();
    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile(rosterRows($world, 2)), $world['term']->id));

    $this->actingAs($world['admin'])
        ->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])
        ->assertConflict();

    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    $this->actingAs($world['admin'])
        ->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])
        ->assertAccepted();
});

test('the commit requires an explicit confirmation', function (): void {
    $world = importWorld();
    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile(rosterRows($world, 1)), $world['term']->id));
    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    $this->actingAs($world['admin'])
        ->postJson(route('api.v1.imports.commit', $job), ['confirm' => false])
        ->assertUnprocessable();
});

test('the preview screen shows the counts, the rows, and the confirmation restating them', function (): void {
    $world = importWorld();
    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile(rosterRows($world, 3)), $world['term']->id));

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.preview.nothing_saved'))
        ->assertSee(__('imports.preview.new_students'))
        ->assertSee(__('imports.preview.confirm_body', [
            'sections' => 1, 'students' => 3, 'enrollments' => 3, 'matched' => 0, 'errors' => 0,
        ]));

    // Rendering the preview to its owner is the owner seeing it, so the commit gate is satisfied.
    expect($job->refresh()->preview_fetched_at)->not->toBeNull();
});
