<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Models\ImportJob;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Modules\Imports\Pipeline\ImportReasons;

/*
|--------------------------------------------------------------------------
| T248 — the instructor's section list goes through the same six steps (FR-053)
|--------------------------------------------------------------------------
|
| The same endpoints and the same guarantees as the central import, with two differences that are
| the whole point of the mode: only university id and student name are read, and the section is the
| job's — nothing in the file can put a student anywhere else.
|
*/

test('an instructor walks all six steps, and nothing is saved before confirmation', function (): void {
    $world = sectionWorld();
    $instructor = $world['instructor'];
    $before = CoreCounts::snapshot();

    $job = uploadSectionList($instructor, $world['section'], rosterFile(sectionRows(3), 'list.xlsx', SECTION_HEADERS));

    expect($job->mode)->toBe('section')
        ->and($job->section_id)->toBe($world['section']->id)
        ->and($job->term_id)->toBe($world['section']->term_id)
        ->and($job->file_metadata['suggested_mapping'])->toBe(SECTION_HEADERS);

    $job = mapAndValidate($instructor, $job);
    expect($job->status)->toBe('validated')->and(CoreCounts::snapshot())->toBe($before);

    $this->actingAs($instructor)->getJson(route('api.v1.imports.preview', $job))
        ->assertOk()
        ->assertJsonPath('data.summary.will_create.students', 3)
        ->assertJsonPath('data.summary.will_create.sections', 0)
        ->assertJsonPath('data.summary.will_match.sections', 1);

    expect(CoreCounts::snapshot())->toBe($before);

    $this->actingAs($instructor)->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    expect($job->refresh()->status)->toBe('completed')
        ->and($job->imported)->toBe(3)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $world['section']->id)->count())->toBe(3)
        ->and(Section::query()->withoutGlobalScopes()->count())->toBe($before['sections']);
});

test('only university id and student name are required', function (): void {
    $world = sectionWorld();
    $job = uploadSectionList($world['instructor'], $world['section'], rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS));

    $this->actingAs($world['instructor'])
        ->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => SECTION_HEADERS])
        ->assertOk()
        ->assertJsonPath('data.status', 'mapped');

    $response = $this->actingAs($world['instructor'])
        ->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => ['university_id' => 'الرقم الجامعي']])
        ->assertUnprocessable();

    expect($response->json('errors'))->toBe([
        'mapping.student_name' => [__('imports.mapping.required', ['column' => ImportReasons::column('student_name')])],
    ]);
});

test('a section column in the file is ignored: every student goes to the job\'s section', function (): void {
    $world = sectionWorld();
    $other = Section::factory()->create(['term_id' => $world['term']->id, 'section_number' => '999']);

    $job = importSectionList($world['instructor'], $world['section'], [], rosterFile([
        ['university_id' => '443100001', 'student_name' => 'أ', 'section_number' => '999'],
        ['university_id' => '443100002', 'student_name' => 'ب', 'section_number' => '999'],
    ], 'list.xlsx', SECTION_HEADERS + ['section_number' => 'رقم الشعبة']));

    expect($job->imported)->toBe(2)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $world['section']->id)->count())->toBe(2)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $other->id)->count())->toBe(0);
});

test('the mapping step offers no create-or-skip choices, because a section list creates neither', function (): void {
    $world = sectionWorld();
    $job = uploadSectionList($world['instructor'], $world['section'], rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS));

    $this->actingAs($world['instructor'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(route('api.v1.imports.mapping.update', $job))
        ->assertDontSee(__('imports.mapping.create_courses'))
        ->assertSee(__('imports.modes.section'));
});

test('the same list for the same section is "previously imported"; for another section it is not', function (): void {
    $world = sectionWorld();
    $file = rosterFile(sectionRows(2), 'list.xlsx', SECTION_HEADERS);
    $again = sameFileAgain($file);
    $third = sameFileAgain($file);

    importSectionList($world['instructor'], $world['section'], [], $file);

    $repeat = uploadSectionList($world['instructor'], $world['section'], $again);
    expect($repeat->duplicateOf())->not->toBeNull();

    $otherSection = Section::factory()->create([
        'term_id' => $world['term']->id,
        'instructor_id' => $world['instructor']->id,
        'section_number' => '132',
    ]);

    // fresh(): the user object memoises the sections it can reach, and this one was created after.
    expect(uploadSectionList($world['instructor']->fresh(), $otherSection, $third)->duplicateOf())->toBeNull();
});

test('the section page offers the import to its instructor, and the import page carries the template', function (): void {
    $world = sectionWorld();

    $this->actingAs($world['instructor'])->get(route('sections.show', $world['section']))
        ->assertOk()
        ->assertSee(route('sections.import', $world['section']));

    $this->actingAs($world['instructor'])->get(route('sections.import', $world['section']))
        ->assertOk()
        ->assertSee(route('api.v1.sections.imports.store', $world['section']))
        ->assertSee(route('sections.import.template', $world['section']))
        ->assertDontSee('name="term_id"', false);
});

test('the template is the two columns, headed in the reader\'s language', function (): void {
    $world = sectionWorld();

    $content = $this->actingAs($world['instructor'])->get(route('sections.import.template', $world['section']))
        ->assertOk()
        ->assertDownload()
        ->streamedContent();

    $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
    file_put_contents($path, $content);

    $header = App\Modules\Imports\Pipeline\SpreadsheetReader::header($path, 'xlsx');

    expect($header[1] ?? null)->toBe(array_values(SECTION_HEADERS));
});

test('a section job, once created, is driven by its owner like any other', function (): void {
    $world = sectionWorld();
    $job = uploadSectionList($world['instructor'], $world['section'], rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS));

    // A colleague teaching another section cannot see it, let alone drive it.
    $this->actingAs(aUser('INSTRUCTOR'))->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => SECTION_HEADERS])->assertNotFound();

    expect(ImportJob::query()->withoutGlobalScopes()->find($job->id)?->status)->toBe('uploaded');
});
