<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\SectionInstructorHistory;
use App\Models\Student;
use App\Models\User;
use App\Modules\Imports\Jobs\CommitImportChunkJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

/*
|--------------------------------------------------------------------------
| T212 — the commit runs in 500-row chunks and links what exists (FR-064)
|--------------------------------------------------------------------------
|
| The chunking is observed one chunk at a time: the queue is faked, and each CommitImportChunkJob is
| run by hand. After each run exactly 500 more rows are settled, the checkpoint sits on the last of
| them, and exactly one follow-on job has been queued. Asserting only the end state would pass just
| as well for a commit that did all 1,100 rows in one transaction — which is the thing FR-064 rules
| out.
|
*/

/**
 * Run the queued chunk jobs one at a time, returning the job after each.
 *
 * @return list<ImportJob>
 */
function runChunksByHand(Illuminate\Support\Testing\Fakes\QueueFake $queue, ImportJob $job): array
{
    $states = [];

    // Bounded, so a job that never stops chaining fails the test instead of hanging it.
    for ($i = 0; $i < 10; $i++) {
        $pushed = $queue->pushed(CommitImportChunkJob::class);

        if ($pushed->count() <= $i) {
            break;
        }

        $pushed[$i]->handle();
        $states[] = $job->refresh()->replicate()->forceFill(['id' => $job->id]);
    }

    return $states;
}

test('1,100 rows commit as chunks of 500, 500 and 100, each advancing the checkpoint', function (): void {
    $world = importWorld();
    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile(rosterRows($world, 1100)), $world['term']->id));
    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    $queue = Queue::fake();

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    $ids = ImportRow::query()->where('import_job_id', $job->id)->orderBy('id')->pluck('id')->all();

    $states = runChunksByHand($queue, $job);

    // Three chunks, then the run that finds nothing left and completes.
    expect($states)->toHaveCount(4)
        ->and($states[0]->last_committed_row_id)->toBe($ids[499])
        ->and($states[0]->processed)->toBe(500)
        ->and($states[1]->last_committed_row_id)->toBe($ids[999])
        ->and($states[1]->processed)->toBe(1000)
        ->and($states[2]->last_committed_row_id)->toBe($ids[1099])
        ->and($states[2]->processed)->toBe(1100)
        ->and($states[2]->status)->toBe('committing')
        ->and($states[3]->status)->toBe('completed');

    expect(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(1100)
        ->and($job->refresh()->imported)->toBe(1100)
        ->and($job->errors)->toBe(0);
});

test('existing records are linked, not duplicated', function (): void {
    $world = importWorld();
    $section = Section::factory()->create([
        'term_id' => $world['term']->id,
        'branch_id' => $world['branch']->id,
        'course_id' => $world['course']->id,
        'program_id' => $world['program']->id,
        'section_number' => '131',
        'instructor_id' => $world['instructor']->id,
    ]);

    $student = Student::factory()->create(['university_id' => '441000020', 'name_ar' => 'موجود']);

    $sections = Section::query()->withoutGlobalScopes()->count();
    $courses = Course::query()->count();

    $job = importRoster($world, [
        rosterRow($world, ['university_id' => '441000020', 'student_name' => 'موجود']),
        rosterRow($world),
    ]);

    expect($job->status)->toBe('completed')
        ->and(Section::query()->withoutGlobalScopes()->count())->toBe($sections)
        ->and(Course::query()->count())->toBe($courses)
        ->and(Student::query()->withoutGlobalScopes()->where('university_id', '441000020')->count())->toBe(1)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $section->id)->count())->toBe(2)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('student_id', $student->id)->value('section_id'))->toBe($section->id);
});

test('an existing enrollment counts as a duplicate, and a withdrawal is never reopened by a file', function (): void {
    $world = importWorld();
    $section = Section::factory()->create([
        'term_id' => $world['term']->id,
        'branch_id' => $world['branch']->id,
        'course_id' => $world['course']->id,
        'program_id' => $world['program']->id,
        'section_number' => '131',
        'instructor_id' => $world['instructor']->id,
    ]);

    $student = Student::factory()->create(['university_id' => '441000021', 'name_ar' => 'منسحب']);
    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id, 'student_id' => $student->id, 'status' => 'withdrawn',
        'withdrawn_at' => now()->subDay(), 'withdraw_reason' => 'انسحاب رسمي',
    ]);

    $job = importRoster($world, [rosterRow($world, ['university_id' => '441000021', 'student_name' => 'منسحب'])]);

    expect($job->duplicates)->toBe(1)
        ->and($job->imported)->toBe(0)
        ->and($enrollment->refresh()->status)->toBe('withdrawn')
        ->and($enrollment->withdraw_reason)->toBe('انسحاب رسمي');
});

test('an existing section keeps its instructor whatever the file says', function (): void {
    $world = importWorld();
    $section = Section::factory()->create([
        'term_id' => $world['term']->id,
        'branch_id' => $world['branch']->id,
        'course_id' => $world['course']->id,
        'program_id' => $world['program']->id,
        'section_number' => '131',
        'instructor_id' => $world['instructor']->id,
    ]);

    importRoster($world, [rosterRow($world, ['instructor_email' => aUser('INSTRUCTOR')->email])]);

    expect($section->refresh()->instructor_id)->toBe($world['instructor']->id);
});

test('a created section is active, carries its programme, and opens its instructor history', function (): void {
    $world = importWorld();
    $job = importRoster($world, [rosterRow($world, ['section_number' => '777'])]);

    $section = Section::query()->withoutGlobalScopes()->where('section_number', '777')->firstOrFail();

    expect($job->imported)->toBe(1)
        ->and($section->status)->toBe('active')
        ->and($section->program_id)->toBe($world['program']->id)
        ->and($section->instructor_id)->toBe($world['instructor']->id)
        ->and($section->created_by)->toBe($world['admin']->id)
        ->and(SectionInstructorHistory::query()->where('section_id', $section->id)->whereNull('unassigned_at')->count())->toBe(1);
});

test('unknown courses are created when chosen, into the programme the operator picked', function (): void {
    $world = importWorld();
    $job = importRoster($world, [
        rosterRow($world, ['course_code' => 'phys110', 'course_name' => 'فيزياء عامة', 'section_number' => '401']),
    ], ['create_missing_courses' => true, 'new_course_program_id' => $world['program']->id]);

    $course = Course::query()->where('code', 'PHYS110')->firstOrFail();

    expect($job->imported)->toBe(1)
        ->and($course->name_ar)->toBe('فيزياء عامة')
        ->and($course->programs()->pluck('programs.id')->all())->toBe([$world['program']->id]);
});

test('unknown courses are skipped when not chosen, and the rows are reported', function (): void {
    $world = importWorld();
    $job = importRoster($world, [
        rosterRow($world),
        rosterRow($world, ['course_code' => 'PHYS110', 'section_number' => '401']),
    ]);

    expect($job->imported)->toBe(1)
        ->and($job->errors)->toBe(1)
        ->and(Course::query()->where('code', 'PHYS110')->exists())->toBeFalse()
        ->and(ImportRow::query()->where('import_job_id', $job->id)->where('status', 'skipped')->count())->toBe(1);
});

test('instructors are created when chosen, and cannot sign in until a reset (FR-059)', function (): void {
    $world = importWorld();
    importRoster($world, [
        rosterRow($world, ['section_number' => '402', 'instructor_email' => 'B.Alsahli@mu.edu.sa', 'instructor_name' => 'أ. بدر السهلي']),
    ], ['create_missing_instructors' => true]);

    $created = User::query()->where('email', 'b.alsahli@mu.edu.sa')->firstOrFail();

    expect($created->hasRole('INSTRUCTOR'))->toBeTrue()
        ->and($created->name_ar)->toBe('أ. بدر السهلي')
        ->and($created->first_login_at)->toBeNull()
        ->and(Hash::check('Track2026', $created->password))->toBeFalse();

    app('auth')->forgetGuards();

    // No password anyone was told opens it.
    $this->post('/login', ['email' => 'b.alsahli@mu.edu.sa', 'password' => 'Track2026']);
    $this->assertGuest();
});

test('the commit is attributed to the person who confirmed it', function (): void {
    $world = importWorld();
    importRoster($world, [rosterRow($world, ['section_number' => '778'])]);

    $entry = Illuminate\Support\Facades\DB::table('audit_logs')->where('action', 'section.created')->latest('id')->first();

    expect((int) $entry->user_id)->toBe($world['admin']->id);
});
