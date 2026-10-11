<?php

declare(strict_types=1);

use App\Modules\Imports\Modes\CentralMode;
use App\Modules\Imports\Pipeline\ImportReasons;

/*
|--------------------------------------------------------------------------
| T209 — the mapping step (FR-056)
|--------------------------------------------------------------------------
|
| The suggestion is computed at upload from the header row and must land each of the reference's
| headers on its target. A required target left unmapped is refused by name; a mapped column that the
| file does not have is refused by name. Only then does the job become `mapped`.
|
*/

/**
 * The world and a freshly uploaded two-row roster, at the mapping step.
 *
 * @return array{0: array<string, mixed>, 1: App\Models\ImportJob}
 */
function mappingWorld(): array
{
    $world = importWorld();

    return [$world, uploadRoster($world['admin'], rosterFile(rosterRows($world, 2)), $world['term']->id)];
}

test('the reference headers are suggested onto every target', function (): void {
    [$world, $job] = mappingWorld();
    $suggested = $job->file_metadata['suggested_mapping'];

    foreach (ROSTER_HEADERS as $target => $header) {
        expect($suggested[$target])->toBe($header, "target {$target}");
    }

    expect($suggested['program_code'])->toBeNull();
});

test('the suggestion tolerates the way Arabic headers are actually typed', function (): void {
    [$world, $job] = mappingWorld();
    $job = uploadRoster($world['admin'], rosterFile([], 'typed.xlsx', [
        'الرقم الجامعى', 'اسم  الطالب', 'رقم الشُّعبة', 'Course_Code', 'BRANCH', 'النوع', 'الفصل', 'البريد',
    ]), $world['term']->id);

    $suggested = $job->file_metadata['suggested_mapping'];

    expect($suggested['university_id'])->toBe('الرقم الجامعى')
        ->and($suggested['student_name'])->toBe('اسم  الطالب')
        ->and($suggested['section_number'])->toBe('رقم الشُّعبة')
        ->and($suggested['course_code'])->toBe('Course_Code')
        ->and($suggested['branch'])->toBe('BRANCH');
});

test('a confirmed mapping moves the job to mapped and echoes the suggestion', function (): void {
    [$world, $job] = mappingWorld();
    $this->actingAs($world['admin'])->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
        'options' => ['create_missing_courses' => true],
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'mapped')
        ->assertJsonPath('data.mapping.university_id', 'الرقم الجامعي')
        ->assertJsonPath('data.suggested_mapping.university_id', 'الرقم الجامعي')
        ->assertJsonPath('data.options.create_missing_courses', true)
        ->assertJsonPath('data.options.create_missing_instructors', false);
});

test('an unmapped required target is refused, naming it', function (string $target): void {
    [$world, $job] = mappingWorld();
    $mapping = $job->file_metadata['suggested_mapping'];
    $mapping[$target] = null;

    $this->actingAs($world['admin'])
        ->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => $mapping])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mapping.'.$target => __('imports.mapping.required', ['column' => ImportReasons::column($target)])]);

    expect($job->refresh()->status)->toBe('uploaded');
})->with(CentralMode::required());

test('an optional target may be left unmapped', function (): void {
    [$world, $job] = mappingWorld();
    $mapping = $job->file_metadata['suggested_mapping'];
    $mapping['course_name'] = null;
    $mapping['instructor_name'] = null;

    $this->actingAs($world['admin'])
        ->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => $mapping])
        ->assertOk();
});

test('a mapped column absent from the header row is refused, naming the column', function (): void {
    [$world, $job] = mappingWorld();
    $mapping = $job->file_metadata['suggested_mapping'];
    $mapping['student_name'] = 'عمود غير موجود';

    $this->actingAs($world['admin'])
        ->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => $mapping])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mapping.student_name' => __('imports.mapping.not_in_header', ['column' => 'عمود غير موجود'])]);
});

test('the mapping cannot be changed once the commit has begun', function (): void {
    [$world, $job] = mappingWorld();
    $job = importRoster($world, rosterRows($world, 1));

    $this->actingAs($world['admin'])
        ->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => $job->file_metadata['suggested_mapping']])
        ->assertConflict();
});

test('the browser form confirms the mapping and starts the check in one submission', function (): void {
    [$world, $job] = mappingWorld();
    $this->actingAs($world['admin'])
        ->put(route('api.v1.imports.mapping.update', $job), [
            'mapping' => $job->file_metadata['suggested_mapping'],
            'start_validation' => '1',
        ])
        ->assertRedirect(route('imports.show', $job));

    // The sync driver ran the check inside the request.
    expect($job->refresh()->status)->toBe('validated');
});
