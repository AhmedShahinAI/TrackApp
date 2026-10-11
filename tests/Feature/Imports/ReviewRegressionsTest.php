<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Modules\Imports\Jobs\CommitImportChunkJob;
use App\Modules\Imports\Jobs\ValidateImportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\QueueFake;

/*
|--------------------------------------------------------------------------
| Phase 8 post-review regressions (2026-10-09)
|--------------------------------------------------------------------------
|
| One test per finding of the engineering review, each written to fail against the code the review
| read. The phase's own tests were green throughout — every one of these was a place where a test
| passed while the guarantee it named did not hold, because the tests ran one request at a time,
| one worker at a time, on the choices validation had seen.
|
*/

/**
 * A validated, previewed job, ready to commit.
 *
 * @param  array<string, mixed>  $world
 * @param  list<array<string, string>>  $rows
 * @param  array<string, bool|int>  $options
 */
function previewedJob(array $world, array $rows, array $options = []): ImportJob
{
    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile($rows), $world['term']->id), $options);
    test()->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    return $job->refresh();
}

/**
 * Run the queued commit jobs by hand, oldest first, until none is left — the way one worker would.
 */
function drainCommitQueue(QueueFake $queue, int $from = 0): void
{
    for ($i = $from; $i < 50; $i++) {
        $pushed = $queue->pushed(CommitImportChunkJob::class);

        if ($pushed->count() <= $i) {
            return;
        }

        $pushed[$i]->handle();
    }
}

test('1 — the commit refuses new create-or-skip choices instead of acting on choices nobody validated', function (): void {
    $world = importWorld();
    $courses = Course::query()->count();

    $job = previewedJob($world, [rosterRow($world, ['course_code' => 'PHYS110', 'section_number' => '401'])]);

    $this->actingAs($world['admin'])
        ->postJson(route('api.v1.imports.commit', $job), [
            'confirm' => true,
            'options' => ['create_missing_courses' => true, 'create_missing_instructors' => true],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['options' => __('imports.conflicts.options_at_mapping')]);

    expect($job->refresh()->status)->toBe('validated')
        ->and(Course::query()->count())->toBe($courses);
});

test('2 — a row skipped for its instructor leaves no course behind', function (): void {
    $world = importWorld();
    $courses = Course::query()->count();

    // "Create courses" on, "create instructors" off: the row names a new course AND an instructor with
    // no account, so it is skipped. The preview counted it under will_skip and showed 0 new courses.
    $job = previewedJob($world, [
        rosterRow($world, ['course_code' => 'PHYS110', 'section_number' => '401', 'instructor_email' => 'nobody.yet@mu.edu.sa']),
    ], ['create_missing_courses' => true, 'new_course_program_id' => $world['program']->id]);

    expect($job->summary['will_create']['courses'])->toBe(0);

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    expect($job->refresh()->status)->toBe('completed')
        ->and(ImportRow::query()->where('import_job_id', $job->id)->value('status'))->toBe('skipped')
        ->and(Course::query()->count())->toBe($courses)
        ->and(Course::query()->where('code', 'PHYS110')->exists())->toBeFalse();
});

test('3 — a second commit request is refused and starts no second chain', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 3));

    $queue = Queue::fake();

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    // The second click, made on a page that still showed `validated`. The job row has moved on, so
    // the compare-and-set finds nothing to claim.
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertConflict();

    expect($queue->pushed(CommitImportChunkJob::class))->toHaveCount(1);
});

test('3 — two commits racing past the check start one chain, not two', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 3));

    $queue = Queue::fake();

    /*
     * The race, reproduced in one process. The request loads the job through its route binding — and
     * at exactly that moment the other request's commit lands, which this listener plays by moving
     * the row to `committing` the instant the binding's SELECT returns. The request now holds a copy
     * that still says `validated` and passes canBeCommitted(), as the losing request of a real double
     * click does. Only the compare-and-set in the UPDATE can refuse it.
     */
    $raced = false;

    DB::listen(function ($query) use ($job, &$raced): void {
        if (! $raced && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, '"import_jobs"')) {
            $raced = true;
            DB::table('import_jobs')->where('id', $job->id)->update(['status' => 'committing']);
        }
    });

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertConflict();

    expect($raced)->toBeTrue()
        ->and($queue->pushed(CommitImportChunkJob::class))->toHaveCount(0);
});

/*
 * Redelivery, one run after the other. What stops two runs that are genuinely *simultaneous* is the
 * row lock each takes on the job (`lockForUpdate`) before reading the checkpoint — and that cannot
 * be exercised here: SQLite ignores FOR UPDATE and the suite runs on one connection. It holds on
 * MySQL and PostgreSQL, where the second transaction waits for the first to commit.
 */
test('3 — a duplicated chunk dispatch processes no row twice', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 600));

    $queue = Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    // The same run delivered twice — a redelivery, or a resume racing a slow worker.
    $first = $queue->pushed(CommitImportChunkJob::class)->first();
    $first->handle();
    (new CommitImportChunkJob($job->id))->handle();

    drainCommitQueue($queue, 1);

    $job->refresh();

    expect($job->status)->toBe('completed')
        ->and($job->processed)->toBe(600)
        ->and($job->imported)->toBe(600)
        ->and($job->errors)->toBe(0)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(600)
        ->and(ImportRow::query()->where('import_job_id', $job->id)->where('status', 'error')->count())->toBe(0)
        ->and(DB::table('audit_logs')->where('action', 'import.committed')->count())->toBe(1);
});

test('3 — a second resume is refused', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 2));

    Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();
    ImportJob::query()->whereKey($job->id)->update(['status' => 'failed']);

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.resume', $job))->assertAccepted();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.resume', $job))->assertConflict();
});

test('4 — when the queue gives up on a chunk, the import becomes resumable', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 2));

    Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    (new CommitImportChunkJob($job->id))->failed(new RuntimeException('Job timed out.'));

    expect($job->refresh()->status)->toBe('failed')
        ->and($job->canBeResumed())->toBeTrue();
});

test('4 — a commit whose worker died without a word can be resumed and finishes', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 3));

    $queue = Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    // A worker picked it up, wrote a heartbeat, and was killed before dispatching the next run.
    ImportJob::query()->whereKey($job->id)->update([
        'queued_at' => now()->subMinutes(ImportJob::STALE_AFTER_MINUTES + 10),
        'heartbeat_at' => now()->subMinutes(ImportJob::STALE_AFTER_MINUTES + 5),
    ]);

    expect($job->refresh()->canBeResumed())->toBeTrue();

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.resume', $job))->assertAccepted();

    // The first dispatch died with its worker; the resume's is the one that runs.
    drainCommitQueue($queue, 1);

    expect($job->refresh()->status)->toBe('completed')->and($job->imported)->toBe(3);
});

test('4 — a commit that is only slow is not resumable', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 2));

    Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();
    ImportJob::query()->whereKey($job->id)->update(['heartbeat_at' => now()->subMinute()]);

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.resume', $job))->assertConflict();
});

test('4 — a validation the queue gave up on can be re-mapped and checked again', function (): void {
    $world = importWorld();
    $queue = Queue::fake();

    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], rosterFile(rosterRows($world, 2)), $world['term']->id));
    expect($job->status)->toBe('validating');

    $queue->pushed(ValidateImportJob::class)->first()->failed(new RuntimeException('Job timed out.'));

    expect($job->refresh()->status)->toBe('failed')
        ->and($job->canBeMapped())->toBeTrue()
        ->and($job->canBeResumed())->toBeFalse();
});

test('5 — another import of the same file completing mid-commit does not switch this one to link-only', function (): void {
    $world = importWorld();
    $file = rosterFile(rosterRows($world, 600));
    $again = sameFileAgain($file);

    $job = mapAndValidate($world['admin'], uploadRoster($world['admin'], $file, $world['term']->id));
    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    $queue = Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();
    $queue->pushed(CommitImportChunkJob::class)->first()->handle(); // chunk 1 of 2

    // Meanwhile the same file, uploaded again for the same term, completes.
    $other = uploadRoster($world['admin'], $again, $world['term']->id);
    ImportJob::query()->whereKey($other->id)->update(['status' => 'completed']);

    drainCommitQueue($queue, 1);

    expect($job->refresh()->status)->toBe('completed')
        ->and($job->link_only)->toBeFalse()
        ->and($job->imported)->toBe(600);
});

test('5 — the duplicate warning does not depend on which upload came first', function (): void {
    $world = importWorld();
    $file = rosterFile(rosterRows($world, 2));
    $again = sameFileAgain($file);

    // Uploaded first, imported second.
    $first = uploadRoster($world['admin'], $file, $world['term']->id);
    $second = uploadRoster($world['admin'], $again, $world['term']->id);

    ImportJob::query()->whereKey($second->id)->update(['status' => 'completed']);

    expect($first->refresh()->duplicateOf())->toBe($second->id);
});

test('7 — an owner who loses the permission mid-import stops the import', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 600));

    $queue = Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();
    $queue->pushed(CommitImportChunkJob::class)->first()->handle(); // chunk 1 runs as an administrator

    $world['admin']->syncRoles(['INSTRUCTOR']);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    drainCommitQueue($queue, 1);

    expect($job->refresh()->status)->toBe('failed')
        ->and($job->failure_reason)->toBe(__('imports.failure.owner_revoked'))
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(500);
});

test('7 — a deactivated owner stops the import', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 2));

    Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    $world['admin']->forceFill(['is_active' => false])->saveQuietly();

    (new CommitImportChunkJob($job->id))->handle();

    expect($job->refresh()->status)->toBe('failed')
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(0);
});

test('8 — one section written as ١, 1 and 01 is one section', function (): void {
    $world = importWorld();

    $job = importRoster($world, [
        rosterRow($world, ['section_number' => '١']),
        rosterRow($world, ['section_number' => '1']),
        rosterRow($world, ['section_number' => '01']),
    ]);

    expect($job->imported)->toBe(3)
        ->and(Section::query()->withoutGlobalScopes()->where('course_id', $world['course']->id)->count())->toBe(1);
});

test('8 — a file\'s "131" links to the existing section stored as "0131"', function (): void {
    $world = importWorld();

    $existing = Section::factory()->create([
        'term_id' => $world['term']->id,
        'branch_id' => $world['branch']->id,
        'course_id' => $world['course']->id,
        'program_id' => $world['program']->id,
        'section_number' => '0131',
        'instructor_id' => $world['instructor']->id,
    ]);

    importRoster($world, [rosterRow($world, ['section_number' => '131'])]);

    expect(Section::query()->withoutGlobalScopes()->where('course_id', $world['course']->id)->count())->toBe(1)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $existing->id)->count())->toBe(1);
});

test('9 — a chunk reads students once, not once per row', function (): void {
    $world = importWorld();
    $job = previewedJob($world, rosterRows($world, 300));

    $queue = Queue::fake();
    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    $studentReads = 0;
    $enrollmentReads = 0;

    DB::listen(function ($query) use (&$studentReads, &$enrollmentReads): void {
        $sql = strtolower($query->sql);

        if (str_starts_with($sql, 'select') && str_contains($sql, 'from "students"')) {
            $studentReads++;
        }

        if (str_starts_with($sql, 'select') && str_contains($sql, 'from "section_enrollments"')) {
            $enrollmentReads++;
        }
    });

    $queue->pushed(CommitImportChunkJob::class)->first()->handle();

    expect($job->refresh()->imported)->toBe(300)
        ->and($studentReads)->toBeLessThanOrEqual(2)
        ->and($enrollmentReads)->toBeLessThanOrEqual(2);
});

test('10 — the wizard columns arrive in their own migration, not by editing one already run', function (): void {
    $original = (string) file_get_contents(database_path('migrations/2026_01_01_000018_create_import_jobs_table.php'));
    $added = (string) file_get_contents(database_path('migrations/2026_10_08_000001_add_wizard_state_to_import_jobs_table.php'));

    foreach (['summary', 'link_only', 'preview_fetched_at', 'commit_requested_at', 'queued_at', 'heartbeat_at'] as $column) {
        expect($original)->not->toContain("'{$column}'")
            ->and($added)->toContain("'{$column}'");
    }
});
