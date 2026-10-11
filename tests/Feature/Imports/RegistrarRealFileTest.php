<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Models\ImportJob;
use App\Models\Section;
use App\Models\SectionEnrollment;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T325 — the registration unit's real export, unmodified, end to end (SC-020)
|--------------------------------------------------------------------------
|
| design-system/uploads/filedownloadservlet(21).xlsx, byte for byte: no reconstruction of it. This
| supersedes T221, which only recorded why the central mode cannot read it (that guard still runs,
| in CentralModeRejectsRegistrarFileTest).
|
| The file carries 22 students. tasks.md said 19 — the UI fixture's count, which seeds the first 19
| of them (store.js REAL_FILE.rows.slice(0,19)). This test asserts the file's own number.
|
*/

test('the real file loads end to end: 22 students in the resolved section, with status and sequence', function (): void {
    $world = registrarWorld();

    $upload = postRegistrar($world, realRegistrarFile())->assertCreated();

    expect($upload->json('data.total_rows'))->toBe(22)
        ->and($upload->json('data.resolved.branch_id'))->toBe($world['branch']->id)
        ->and($upload->json('data.resolved.course_id'))->toBe($world['course']->id);

    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    $before = CoreCounts::snapshot();

    $job = mapAndValidate($world['admin'], $job);

    // FR-102: checking writes nothing.
    expect(CoreCounts::snapshot())->toBe($before)
        ->and($job->summary)->toMatchArray(['total_rows' => 22, 'valid' => 22, 'errors' => 0]);

    $job = previewAndCommit($world['admin'], $job);

    $section = Section::query()->withoutGlobalScopes()->sole();
    $enrollments = SectionEnrollment::query()->withoutGlobalScopes()->with('student')->orderBy('roster_sequence')->get();

    expect($job->status)->toBe('completed')
        ->and($job->imported)->toBe(22)
        ->and($section->only(['term_id', 'branch_id', 'course_id', 'program_id', 'section_number', 'instructor_id', 'degree', 'activity']))->toBe([
            'term_id' => $world['term']->id,
            'branch_id' => $world['branch']->id,
            'course_id' => $world['course']->id,
            'program_id' => $world['program']->id,
            'section_number' => '2298',
            'instructor_id' => $world['instructor']->id,
            'degree' => 'intermediate_diploma',
            'activity' => 'lecture',
        ])
        ->and($enrollments)->toHaveCount(22)
        ->and($enrollments->pluck('roster_sequence')->all())->toBe(range(1, 22))
        ->and($enrollments->pluck('academic_status')->unique()->all())->toBe(['regular'])
        ->and($enrollments->pluck('status')->unique()->all())->toBe(['active'])
        ->and($enrollments->first()->student->university_id)->toBe('442104959')
        ->and($enrollments->first()->student->name_ar)->toBe('سعود بن محمد بن ابراهيم الشدوخي');
});

test('it ends on the report step; its error report is a 404, because the real file has no faulty row', function (): void {
    $world = registrarWorld();
    $job = importRegistrar($world, realRegistrarFile());

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.steps.report'));

    // contracts/imports.md: "404 when there is nothing to report". All 22 rows are valid.
    $this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job))->assertNotFound();
});

test('a registrar file with a faulty row produces a downloadable error report (FR-102)', function (): void {
    $world = registrarWorld();
    $job = importRegistrar($world, registrarFile(rows: [...registrarRows(2), ['3', 'not-an-id', 'طالب', 'منتظم']]));

    expect($job->errors)->toBe(1);

    $this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job))->assertOk();
});

test('a second upload of the same file creates nothing (FR-067)', function (): void {
    $world = registrarWorld();
    $job = importRegistrar($world, realRegistrarFile());

    $again = postRegistrar($world, realRegistrarFile())->assertCreated();

    expect($again->json('data.duplicate_of'))->toBe($job->id)
        ->and($again->json('data.resolved.section_id'))->toBe(Section::query()->withoutGlobalScopes()->sole()->id);

    $counts = CoreCounts::snapshot();
    $second = previewAndCommit($world['admin'], mapAndValidate($world['admin'], ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail()));

    expect($second->imported)->toBe(0)
        ->and(CoreCounts::snapshot())->toBe($counts);
});

test('the import is audited as committed, like every other mode', function (): void {
    $world = registrarWorld();
    importRegistrar($world, realRegistrarFile());

    expect(DB::table('audit_logs')->where('action', 'import.committed')->count())->toBe(1);
});
