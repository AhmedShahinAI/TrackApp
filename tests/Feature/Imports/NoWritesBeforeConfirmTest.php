<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Models\ImportRow;

/*
|--------------------------------------------------------------------------
| T207 — nothing is written before the operator confirms (FR-060)
|--------------------------------------------------------------------------
|
| The contract's central guarantee: no term, branch, course, section, student or enrollment is
| created or changed by any endpoint before POST /imports/{id}/commit.
|
| The file is built to give every step a reason to write if it were going to: new students, a
| course that does not exist with "create" switched on, an instructor with no account with "create"
| switched on, and a section that would have to be made. Asserting that a file of already-known
| records writes nothing would prove only that there was nothing to write.
|
| The counts are read with CoreCounts::snapshot(), the same probe quickstart 5.4 runs by hand, which
| counts the tables directly so no scope can hide a row.
|
*/

test('core tables are unchanged across upload, mapping, validation and preview', function (): void {
    $world = importWorld();

    $rows = [
        ...rosterRows($world, 5),
        rosterRow($world, ['course_code' => 'NEW900', 'section_number' => '900', 'program_code' => 'CS']),
        rosterRow($world, ['section_number' => '901', 'instructor_email' => 'nobody.yet@mu.edu.sa']),
    ];

    $file = rosterFile($rows, 'roster.xlsx', ROSTER_HEADERS + ['program_code' => 'رمز البرنامج']);

    $before = CoreCounts::snapshot();

    $job = uploadRoster($world['admin'], $file, $world['term']->id);
    expect(CoreCounts::snapshot())->toBe($before, 'after upload');

    $this->actingAs($world['admin'])->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
        'options' => ['create_missing_courses' => true, 'create_missing_instructors' => true],
    ])->assertOk();
    expect(CoreCounts::snapshot())->toBe($before, 'after mapping');

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.validate', $job))->assertAccepted();
    expect(CoreCounts::snapshot())->toBe($before, 'after validation');

    // Validation did happen — the rows are staged — and still nothing core moved.
    expect(ImportRow::query()->where('import_job_id', $job->id)->count())->toBe(7);

    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();
    expect(CoreCounts::snapshot())->toBe($before, 'after preview');

    // The control: the same job, confirmed, does write. Without this the test would also pass
    // against an import that never writes anything at all.
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    $after = CoreCounts::snapshot();

    expect($after['students'])->toBe($before['students'] + 7)
        ->and($after['section_enrollments'])->toBe($before['section_enrollments'] + 7)
        ->and($after['courses'])->toBe($before['courses'] + 1)
        ->and($after['sections'])->toBe($before['sections'] + 3);
});

test('no user account is created before confirmation either', function (): void {
    $world = importWorld();
    $users = App\Models\User::query()->withTrashed()->count();

    $job = uploadRoster($world['admin'], rosterFile([
        rosterRow($world, ['section_number' => '902', 'instructor_email' => 'new.instructor@mu.edu.sa']),
    ]), $world['term']->id);

    mapAndValidate($world['admin'], $job, ['create_missing_instructors' => true]);
    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    expect(App\Models\User::query()->withTrashed()->count())->toBe($users);
});
