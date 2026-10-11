<?php

declare(strict_types=1);

use App\Models\ImportRow;
use App\Models\SectionEnrollment;
use App\Models\Student;

/*
|--------------------------------------------------------------------------
| T249 — an existing student is linked, and a differing name keeps the stored one (FR-069)
|--------------------------------------------------------------------------
|
| The student already exists — enrolled in someone else's section, which this instructor cannot see.
| The lookup must find them anyway (it is unscoped, as EnrollStudent's is), or the import creates a
| second person with the same university id and the unique index refuses it.
|
*/

test('a student enrolled elsewhere is linked into this section, not created again', function (): void {
    $world = sectionWorld();

    $student = Student::factory()->create(['university_id' => '441200001', 'name_ar' => 'نورة السبيعي']);
    SectionEnrollment::factory()->create([
        'student_id' => $student->id,
        'section_id' => App\Models\Section::factory()->create()->id,
        'status' => 'active',
    ]);

    $job = importSectionList($world['instructor'], $world['section'], [
        ['university_id' => '441200001', 'student_name' => 'نورة السبيعي'],
    ]);

    expect($job->imported)->toBe(1)
        ->and(Student::query()->withoutGlobalScopes()->where('university_id', '441200001')->count())->toBe(1)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('student_id', $student->id)->count())->toBe(2);
});

test('a differing name raises a warning and the stored name is kept', function (): void {
    $world = sectionWorld();
    $student = Student::factory()->create(['university_id' => '441200002', 'name_ar' => 'نورة السبيعي']);

    $job = uploadSectionList($world['instructor'], $world['section'], rosterFile([
        ['university_id' => '441200002', 'student_name' => 'اسم آخر'],
    ], 'list.xlsx', SECTION_HEADERS));
    $job = mapAndValidate($world['instructor'], $job);

    $row = ImportRow::query()->where('import_job_id', $job->id)->firstOrFail();

    expect($row->status)->toBe('warning')
        ->and(array_column($row->reasons, 'reason_key'))->toContain('import.name_mismatch');

    // The student is not in this instructor's section, so the reason says the name differs without
    // quoting the stored one — the lookup is unscoped, and quoting it would disclose a stranger's name.
    $preview = $this->actingAs($world['instructor'])->getJson(route('api.v1.imports.preview', $job))->json('data.rows.0.reasons');
    expect($preview)->toContain(__('imports.reasons.name_mismatch_private'))
        ->and(implode(' ', $preview))->not->toContain('نورة السبيعي');

    $this->actingAs($world['instructor'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    expect($student->refresh()->name_ar)->toBe('نورة السبيعي')
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('student_id', $student->id)->where('section_id', $world['section']->id)->exists())->toBeTrue();
});

test('a student already in this section is counted as a duplicate, and a withdrawal stands', function (): void {
    $world = sectionWorld();
    $student = Student::factory()->create(['university_id' => '441200003', 'name_ar' => 'منسحبة']);

    $enrollment = SectionEnrollment::factory()->create([
        'student_id' => $student->id,
        'section_id' => $world['section']->id,
        'status' => 'withdrawn',
        'withdrawn_at' => now()->subDay(),
        'withdraw_reason' => 'انسحاب رسمي',
    ]);

    $job = importSectionList($world['instructor'], $world['section'], [
        ['university_id' => '441200003', 'student_name' => 'منسحبة'],
    ]);

    expect($job->duplicates)->toBe(1)
        ->and($job->imported)->toBe(0)
        ->and($enrollment->refresh()->status)->toBe('withdrawn');
});
