<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\ImportJob;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Modules\Imports\Pipeline\ImportReasons;

/*
|--------------------------------------------------------------------------
| T324 — the course by its Arabic name: one match, none, or several (FR-100)
|--------------------------------------------------------------------------
*/

test('an Arabic name matches one course unambiguously, however its letters and digits are written', function (string $written): void {
    $world = registrarWorld();

    postRegistrar($world, registrarFile(['اسم المقرر' => $written] + REGISTRAR_BLOCK, registrarRows(1)))
        ->assertCreated()
        ->assertJsonPath('data.resolved.course_id', $world['course']->id)
        ->assertJsonPath('data.resolved.course_candidates', []);
})->with(['برمجة 2', 'برمجه 2', 'برمجة ٢', '  برمجة   2 ', 'CS102']);

test('a name matching no course rejects the file, naming it', function (): void {
    $world = registrarWorld();

    $response = postRegistrar($world, registrarFile(['اسم المقرر' => 'هندسة البرمجيات'] + REGISTRAR_BLOCK, registrarRows(3)))
        ->assertUnprocessable();

    expect($response->json('errors.file.0'))->toBe(__('imports.registrar.course_unknown', ['value' => 'هندسة البرمجيات']))
        ->and(ImportJob::query()->withoutGlobalScopes()->count())->toBe(0);
});

test('several matches are not a refusal: the rows are flagged for the operator, and skipped unless they choose', function (): void {
    $world = registrarWorld();
    $twin = Course::factory()->create(['organization_id' => $world['course']->organization_id, 'code' => 'IT102', 'name_ar' => 'برمجة 2']);
    $twin->programs()->attach($world['program']->id);

    $upload = postRegistrar($world, registrarFile(rows: registrarRows(3)))->assertCreated();

    expect($upload->json('data.resolved.course_id'))->toBeNull()
        ->and($upload->json('data.resolved.course_candidates'))->toBe([$world['course']->id, $twin->id]);

    $job = mapAndValidate($world['admin'], ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail());
    $preview = $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    expect($preview->json('data.summary'))->toMatchArray(['warnings' => 3, 'will_skip' => 3])
        ->and(array_values(array_unique($preview->json('data.rows.*.status'))))->toBe(['warning'])
        ->and($preview->json('data.rows.0.reasons'))->toContain(ImportReasons::localise(ImportReasons::make(
            ImportReasons::AMBIGUOUS_COURSE, 'course', 'برمجة 2', ['value' => 'برمجة 2'],
        )));

    previewAndCommit($world['admin'], $job);

    expect(Section::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(0);
});

test('choosing one of the candidates at the confirmation step imports into that course', function (): void {
    $world = registrarWorld();
    $twin = Course::factory()->create(['organization_id' => $world['course']->organization_id, 'code' => 'IT102', 'name_ar' => 'برمجة 2']);
    $twin->programs()->attach($world['program']->id);

    importRegistrar($world, registrarFile(rows: registrarRows(2)), ['course_id' => $twin->id]);

    expect(Section::query()->withoutGlobalScopes()->sole()->course_id)->toBe($twin->id)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(2);
});

test('the choice must be one of the candidates', function (): void {
    $world = registrarWorld();
    Course::factory()->create(['organization_id' => $world['course']->organization_id, 'code' => 'IT102', 'name_ar' => 'برمجة 2']);
    $unrelated = Course::factory()->create(['organization_id' => $world['course']->organization_id, 'code' => 'MATH1']);

    postRegistrar($world, registrarFile(rows: registrarRows(1)))->assertCreated();
    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    $this->actingAs($world['admin'])->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
        'options' => ['course_id' => $unrelated->id],
    ])->assertUnprocessable()->assertJsonValidationErrors(['options.course_id']);
});
