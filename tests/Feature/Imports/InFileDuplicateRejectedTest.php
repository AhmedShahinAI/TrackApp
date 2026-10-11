<?php

declare(strict_types=1);

use App\Models\ImportRow;
use App\Models\SectionEnrollment;
use App\Modules\Imports\Pipeline\ImportReasons;

/*
|--------------------------------------------------------------------------
| T250 — a student listed twice in one section's file: the second is rejected, naming the first
|--------------------------------------------------------------------------
|
| In a section list the identity of a row is the university id alone, so the same student twice is
| the same row twice — whatever the names say, and however the digits were typed.
|
*/

test('the second occurrence is an error naming the row of the first', function (): void {
    $world = sectionWorld();

    $job = uploadSectionList($world['instructor'], $world['section'], rosterFile([
        ['university_id' => '443300001', 'student_name' => 'لين الحربي'],
        ['university_id' => '443300002', 'student_name' => 'هديل الغامدي'],
        ['university_id' => '443300001', 'student_name' => 'لين الحربي'],
    ], 'list.xlsx', SECTION_HEADERS));
    $job = mapAndValidate($world['instructor'], $job);

    $second = ImportRow::query()->where('import_job_id', $job->id)->where('row_number', 4)->firstOrFail();
    $reason = collect($second->reasons)->firstWhere('reason_key', ImportReasons::DUPLICATE_IN_FILE);

    expect($second->status)->toBe('error')
        ->and($reason['params']['row'] ?? null)->toBe(2)
        ->and(ImportReasons::localise($reason))->toBe('مكرر داخل الملف (الصف 2)');

    $this->actingAs($world['instructor'])->getJson(route('api.v1.imports.preview', $job))->assertOk();
    $this->actingAs($world['instructor'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    expect($job->refresh()->imported)->toBe(2)
        ->and($job->errors)->toBe(1)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $world['section']->id)->count())->toBe(2);
});

test('a duplicate written in Arabic-Indic digits is still the same student', function (): void {
    $world = sectionWorld();

    $job = uploadSectionList($world['instructor'], $world['section'], rosterFile([
        ['university_id' => '443300003', 'student_name' => 'ترف السبيعي'],
        ['university_id' => '٤٤٣٣٠٠٠٠٣', 'student_name' => 'ترف السبيعي'],
    ], 'list.xlsx', SECTION_HEADERS));
    $job = mapAndValidate($world['instructor'], $job);

    expect(ImportRow::query()->where('import_job_id', $job->id)->where('row_number', 3)->value('status'))->toBe('error');
});

test('the same name with different university ids is two students, not a duplicate', function (): void {
    $world = sectionWorld();

    $job = importSectionList($world['instructor'], $world['section'], [
        ['university_id' => '443300004', 'student_name' => 'محمد العتيبي'],
        ['university_id' => '443300005', 'student_name' => 'محمد العتيبي'],
    ]);

    expect($job->imported)->toBe(2)->and($job->errors)->toBe(0);
});
