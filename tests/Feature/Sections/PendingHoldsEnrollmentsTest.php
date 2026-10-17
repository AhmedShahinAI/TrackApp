<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Models\SectionEnrollment;

/*
|--------------------------------------------------------------------------
| T481 — a creator attaches a roster at creation, and it survives both approval and rejection (FR-175)
|--------------------------------------------------------------------------
|
| The roster goes through the existing section import mode, never a parallel path (T494). The
| create response hands back the import job, which the creator walks through the usual wizard.
|
*/

/**
 * An instructor's pending section, created with a three-student roster that is then committed.
 *
 * @param  array<string, mixed>  $world
 * @return array{0: App\Models\Section, 1: ImportJob}
 */
function sectionWithRoster(array $world): array
{
    app('auth')->forgetGuards();
    $response = test()->actingAs($world['instructor'])->post('/api/v1/sections', sectionPayload($world, [
        // The instructor teaches it: the section import mode's own rule (SectionPolicy::importRoster).
        'instructor_id' => $world['instructor']->id,
        'roster' => rosterFile(sectionRows(3), 'list.xlsx', SECTION_HEADERS),
    ]), ['Accept' => 'application/json'])->assertCreated();

    $job = ImportJob::query()->withoutGlobalScopes()->findOrFail($response->json('data.import_job_id'));
    $section = App\Models\Section::query()->withoutGlobalScopes()->findOrFail($response->json('data.id'));
    $world['instructor']->forgetScopeCache();

    return [$section, previewAndCommit($world['instructor'], mapAndValidate($world['instructor'], $job))];
}

test('the roster lands as a section-mode import of the new section', function (): void {
    $world = approvalWorld();
    [$section, $job] = sectionWithRoster($world);

    expect($section->status)->toBe('pending')
        ->and($job->mode)->toBe('section')
        ->and((int) $job->section_id)->toBe($section->id)
        ->and($job->status)->toBe('completed')
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $section->id)->count())->toBe(3);
});

test('the enrollments survive approval', function (): void {
    $world = approvalWorld();
    [$section] = sectionWithRoster($world);

    decideSection($world['coordinator'], $section, 'approve')->assertOk();

    expect(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $section->id)->count())->toBe(3);
});

test('the enrollments survive rejection, kept with the section', function (): void {
    $world = approvalWorld();
    [$section] = sectionWithRoster($world);

    decideSection($world['coordinator'], $section, 'reject', 'This section duplicates another one.')->assertOk();

    expect(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $section->id)->count())->toBe(3);
});

test('a roster the import refuses refuses the section with it', function (): void {
    $world = approvalWorld();
    $before = App\Models\Section::query()->withoutGlobalScopes()->count();
    $bad = Illuminate\Http\UploadedFile::fake()->createWithContent('roster.xlsx', 'not a spreadsheet');

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->post('/api/v1/sections', sectionPayload($world, ['instructor_id' => $world['instructor']->id, 'roster' => $bad]), ['Accept' => 'application/json'])
        ->assertUnprocessable();

    expect(App\Models\Section::query()->withoutGlobalScopes()->count())->toBe($before);
});
