<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\User;
use App\Modules\Imports\Jobs\CommitImportChunkJob;
use App\Modules\Imports\Pipeline\ImportReasons;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Phase 9 post-review regressions (2026-10-09)
|--------------------------------------------------------------------------
|
| One test per finding of the US6 review, each written to fail against the code the review read.
|
*/

/**
 * A user who is branch-scoped (so they keep seeing jobs in their branch) and may also run section
 * imports — the caller for whom "can see the job" outlives "may import into its section".
 */
function branchScopedImporter(App\Models\Branch $branch): User
{
    $user = aUser('INSTRUCTOR');

    $role = Role::findOrCreate('TEACHING_COORDINATOR', 'web');
    $role->syncPermissions(['import.section.run', 'import.view', 'section.view', 'student.view', 'dashboard.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles(['TEACHING_COORDINATOR']);
    $user->coordinatorBranches()->sync([$branch->id]);

    return $user->fresh();
}

test('1 — the preview does not quote the stored name of a student outside the section', function (): void {
    $world = sectionWorld();
    Student::factory()->create(['university_id' => '441900001', 'name_ar' => 'اسم سري']);

    $job = mapAndValidate($world['instructor'], uploadSectionList($world['instructor'], $world['section'], rosterFile([
        ['university_id' => '441900001', 'student_name' => 'x'],
    ], 'probe.xlsx', SECTION_HEADERS)));

    $reasons = $this->actingAs($world['instructor'])->getJson(route('api.v1.imports.preview', $job))->json('data.rows.0.reasons');

    expect(implode(' ', $reasons))->not->toContain('اسم سري')
        ->and($reasons)->toContain(__('imports.reasons.name_mismatch_private'));
});

test('1 — it still quotes it for a student already in the section, who the instructor can see', function (): void {
    $world = sectionWorld();
    $student = Student::factory()->create(['university_id' => '441900002', 'name_ar' => 'منى العتيبي']);
    SectionEnrollment::factory()->create(['student_id' => $student->id, 'section_id' => $world['section']->id, 'status' => 'active']);

    $job = mapAndValidate($world['instructor'], uploadSectionList($world['instructor'], $world['section'], rosterFile([
        ['university_id' => '441900002', 'student_name' => 'منى'],
    ], 'list.xlsx', SECTION_HEADERS)));

    $reasons = $this->actingAs($world['instructor'])->getJson(route('api.v1.imports.preview', $job))->json('data.rows.0.reasons');

    expect(implode(' ', $reasons))->toContain('منى العتيبي');
});

test('2 — every later step refuses once the owner may no longer import into the section', function (): void {
    $world = sectionWorld();
    $owner = branchScopedImporter($world['branch']);
    Section::query()->withoutGlobalScopes()->whereKey($world['section']->id)->update(['instructor_id' => $owner->id]);

    $job = uploadSectionList($owner, $world['section'], rosterFile(sectionRows(2), 'list.xlsx', SECTION_HEADERS));

    // The section moves on. The job is still in the owner's branch scope, so they can still reach it.
    Section::query()->withoutGlobalScopes()->whereKey($world['section']->id)->update(['instructor_id' => aUser('INSTRUCTOR')->id]);
    $owner = $owner->fresh();

    $this->actingAs($owner)->getJson(route('api.v1.imports.show', $job))->assertOk();

    $this->actingAs($owner)->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => SECTION_HEADERS])->assertForbidden();

    ImportJob::query()->whereKey($job->id)->update(['status' => 'mapped']);
    $this->actingAs($owner)->postJson(route('api.v1.imports.validate', $job))->assertForbidden();

    ImportJob::query()->whereKey($job->id)->update(['status' => 'validated', 'preview_fetched_at' => now()]);
    $this->actingAs($owner)->getJson(route('api.v1.imports.preview', $job))->assertForbidden();
    $this->actingAs($owner)->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertForbidden();

    ImportJob::query()->whereKey($job->id)->update(['status' => 'failed', 'commit_requested_at' => now()]);
    $this->actingAs($owner)->postJson(route('api.v1.imports.resume', $job))->assertForbidden();
});

test('3 — the same name written with other hamza, tatweel, spacing or case is not a mismatch', function (string $stored, string $given): void {
    expect(App\Modules\Imports\Pipeline\RowValidator::sameName($given, ['name_ar' => $stored, 'name_en' => 'Mohammed Ahmed']))->toBeTrue();
})->with([
    ['محمد أحمد', 'محمد احمد'],
    ['محمد أحمد', 'محمـد أحمد'],
    ['محمد أحمد', 'محمد  أحمد'],
    ['مُحَمَّد أحمد', 'محمد أحمد'],
    ['هدى الزهراني', 'هدي الزهراني'],
    ['محمد أحمد', 'MOHAMMED  AHMED'],
]);

test('3 — a genuinely different name is still a mismatch', function (): void {
    expect(App\Modules\Imports\Pipeline\RowValidator::sameName('خالد سعيد', ['name_ar' => 'محمد أحمد', 'name_en' => null]))->toBeFalse();
});

test('4 — an instructor\'s list does not restore a deleted student', function (): void {
    $world = sectionWorld();
    $student = Student::factory()->create(['university_id' => '441900003', 'name_ar' => 'محذوف']);
    $student->delete();

    $job = importSectionList($world['instructor'], $world['section'], [['university_id' => '441900003', 'student_name' => 'محذوف']]);

    expect($student->refresh()->trashed())->toBeTrue()
        ->and($job->imported)->toBe(0)
        ->and(ImportRow::query()->where('import_job_id', $job->id)->value('status'))->toBe('error')
        ->and(array_column(ImportRow::query()->where('import_job_id', $job->id)->first()->reasons, 'reason_key'))
        ->toContain(ImportReasons::STUDENT_UNAVAILABLE);
});

test('4 — the central import, from the registration unit\'s roster, still restores', function (): void {
    $world = importWorld();
    $student = Student::factory()->create(['university_id' => '441900004', 'name_ar' => 'محذوف']);
    $student->delete();

    importRoster($world, [rosterRow($world, ['university_id' => '441900004', 'student_name' => 'محذوف'])]);

    expect($student->refresh()->trashed())->toBeFalse();
});

test('5 — a section in an archived term takes no list', function (): void {
    $world = sectionWorld();
    AcademicTerm::query()->whereKey($world['term']->id)->update(['status' => 'archived']);

    $this->actingAs($world['instructor'])
        ->post(route('api.v1.sections.imports.store', $world['section']), ['file' => rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS)], ['Accept' => 'application/json'])
        ->assertConflict();

    expect(ImportJob::query()->withoutGlobalScopes()->count())->toBe(0);
});

test('6 — an import stopped for lost authority leaves an audit entry', function (): void {
    $world = sectionWorld();
    $job = mapAndValidate($world['instructor'], uploadSectionList($world['instructor'], $world['section'], rosterFile(sectionRows(2), 'list.xlsx', SECTION_HEADERS)));
    $this->actingAs($world['instructor'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    Queue::fake();
    $this->actingAs($world['instructor'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    Section::query()->withoutGlobalScopes()->whereKey($world['section']->id)->update(['instructor_id' => aUser('INSTRUCTOR')->id]);
    DB::table('audit_logs')->delete();

    (new CommitImportChunkJob($job->id))->handle();

    $entry = DB::table('audit_logs')->where('action', 'access.denied')->first();

    expect($job->refresh()->status)->toBe('failed')
        ->and($entry)->not->toBeNull()
        ->and((int) $entry->user_id)->toBe($world['instructor']->id)
        ->and($entry->entity_type)->toBe('ImportJob')
        ->and(json_decode((string) $entry->new_values, true)['reason'])->toBe('import_authority_lost');
});

test('7 — a section job\'s mapping refuses the central-only options', function (): void {
    $world = sectionWorld();
    $job = uploadSectionList($world['instructor'], $world['section'], rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS));

    $this->actingAs($world['instructor'])
        ->putJson(route('api.v1.imports.mapping.update', $job), [
            'mapping' => SECTION_HEADERS,
            'options' => ['create_missing_courses' => true, 'new_course_program_id' => $world['program']->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['options' => __('imports.conflicts.options_not_in_section_mode')]);

    expect($job->refresh()->option('create_missing_courses'))->toBeFalse();
});

test('8 — a section commit does not load the course catalogue', function (): void {
    $world = sectionWorld();
    $job = mapAndValidate($world['instructor'], uploadSectionList($world['instructor'], $world['section'], rosterFile(sectionRows(3), 'list.xlsx', SECTION_HEADERS)));
    $this->actingAs($world['instructor'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    $queue = Queue::fake();
    $this->actingAs($world['instructor'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    $courseReads = 0;
    DB::listen(function ($query) use (&$courseReads): void {
        if (str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'from "courses"')) {
            $courseReads++;
        }
    });

    $queue->pushed(CommitImportChunkJob::class)->first()->handle();

    expect($job->refresh()->imported)->toBe(3)->and($courseReads)->toBe(0);
});

test('9 — a section number with a slash still downloads its template', function (): void {
    $world = sectionWorld();
    Section::query()->withoutGlobalScopes()->whereKey($world['section']->id)->update(['section_number' => '1/2']);

    $this->actingAs($world['instructor'])->get(route('sections.import.template', $world['section']))
        ->assertOk()
        ->assertDownload('section-1-2-template.xlsx');
});
