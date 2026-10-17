<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\ImportJob;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use App\Modules\Imports\Pipeline\ImportReasons;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Phase 16 review corrections (2026-10-11)
|--------------------------------------------------------------------------
*/

/*
| 1 — every created account lands in its creator's organization
*/

test('an account created on the form lands in the creator\'s organization, not the first college', function (): void {
    Organization::factory()->create(['type' => 'college']); // the "first college", someone else's
    $other = Organization::factory()->create(['type' => 'college']);
    $admin = aUser('SUPER_ADMIN', ['organization_id' => $other->id]);

    $this->actingAs($admin)->postJson(route('api.v1.users.store'), [
        'name_ar' => 'مدرس', 'name_en' => 'Teacher', 'email' => 'form@mu.edu.sa',
        'password' => 'Track2026-Own', 'role' => 'INSTRUCTOR',
    ])->assertCreated();

    expect(User::query()->where('email', 'form@mu.edu.sa')->sole()->organization_id)->toBe($other->id);
});

test('an instructor the roster import creates lands in the operator\'s organization', function (): void {
    Organization::factory()->create(['type' => 'college']);
    $own = Organization::factory()->create(['type' => 'college']);
    $admin = aUser('SUPER_ADMIN', ['organization_id' => $own->id]);
    $term = AcademicTerm::factory()->create(['status' => 'active', 'is_current' => true]);
    Branch::factory()->create(['organization_id' => $own->id, 'campus_name_ar' => 'المجمعة', 'gender' => 'male']);
    $program = Program::factory()->create(['organization_id' => $own->id, 'code' => 'CS']);
    Course::factory()->create(['organization_id' => $own->id, 'code' => 'CS101'])->programs()->attach($program->id);

    $world = ['admin' => $admin, 'term' => $term];
    importRoster($world, [rosterRow($world, ['instructor_email' => 'new.instructor@mu.edu.sa'])], ['create_missing_instructors' => true]);

    expect(User::query()->where('email', 'new.instructor@mu.edu.sa')->sole()->organization_id)->toBe($own->id);
});

/*
| 2 — re-uploading a course or staff file is not frozen to link-only
*/

test('the same staff file, uploaded again after its rows were refused, creates the accounts once fixed', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $file = staffFile([['منسق', 'Coordinator', 'coord@mu.edu.sa', 'BRANCH_COORDINATOR', 'الزلفي (طلاب)']]);

    $first = importBulk($admin, 'users', sameFileAgain($file));
    expect($first->imported)->toBe(0);

    // The branch the file named is set up now.
    Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'الزلفي', 'gender' => 'male']);

    $second = importBulk($admin, 'users', sameFileAgain($file));

    expect($second->link_only)->toBeFalse()
        ->and($second->imported)->toBe(1)
        ->and(User::query()->where('email', 'coord@mu.edu.sa')->exists())->toBeTrue();
});

/*
| 3 — a new course never leaves with no programme
*/

test('a new course whose only programmes are deactivated is skipped, not created bare', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'OLD', 'is_active' => false]);

    $job = checkBulk($admin, 'courses', courseFile([['NEW101', 'مقرر جديد', 'New', 'OLD']]));
    expect($job->summary['will_skip'])->toBe(1);

    previewAndCommit($admin, $job);

    expect(Course::query()->where('code', 'NEW101')->exists())->toBeFalse()
        ->and(rowReasons($job->refresh())[2])->toContain(ImportReasons::SKIPPED_BY_CHOICE);
});

test('an EXISTING course naming only a deactivated programme is still matched, not skipped', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'OLD', 'is_active' => false]);
    Course::factory()->create(['organization_id' => $admin->organization_id, 'code' => 'KEEP1']);

    $job = importBulk($admin, 'courses', courseFile([['KEEP1', 'مقرر', 'Course', 'OLD']]));

    expect($job->duplicates)->toBe(1)->and($job->errors)->toBe(0);
});

/*
| 4 — a conflict names the row's own column
*/

test('an email taken between the check and the commit is a conflict reported against the email', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $job = checkBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 'race@mu.edu.sa', 'INSTRUCTOR', '']]));

    aUser('INSTRUCTOR', ['email' => 'race@mu.edu.sa']); // another path got there first

    previewAndCommit($admin, $job);

    $reason = collect(App\Models\ImportRow::query()->where('import_job_id', $job->id)->sole()->reasons)
        ->firstWhere('reason_key', ImportReasons::COMMIT_CONFLICT);

    expect($reason['column'])->toBe('email')->and($reason['value'])->toBe('race@mu.edu.sa');
});

/*
| 5, 6 — programmes at commit: created once, found if another import got there first
*/

test('a programme created by another import between the check and the commit is matched, not duplicated', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $job = checkBulk($admin, 'courses', courseFile([['DS101', 'علم البيانات', 'Data Science', 'DS']]), ['create_missing_programs' => true]);

    $first = aProgram(['code' => 'DS']); // the concurrent import's

    previewAndCommit($admin, $job);

    expect(Program::query()->where('code', 'DS')->count())->toBe(1)
        ->and(Course::query()->where('code', 'DS101')->sole()->programs()->pluck('programs.id')->all())->toBe([$first->id])
        ->and($job->refresh()->errors)->toBe(0);
});

test('a programme deactivated by the time of the commit is not attached unless confirmed', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'HR']);
    $job = checkBulk($admin, 'courses', courseFile([['HR201', 'مبادئ', 'Principles', 'HR، علم البيانات']]), ['create_missing_programs' => true]);

    // Named by the file as unknown at the check; by the commit, a deactivated programme holds the name.
    aProgram(['code' => 'DSX', 'name_ar' => 'علم البيانات', 'is_active' => false]);

    previewAndCommit($admin, $job);

    expect(Course::query()->where('code', 'HR201')->sole()->programs()->pluck('code')->all())->toBe(['HR']);
});

test('a generated programme code is long enough not to collide, and fits the column', function (): void {
    $admin = aUser('SUPER_ADMIN');

    importBulk($admin, 'courses', courseFile([['X101', 'مقرر', 'Course', 'برنامج بلا رمز']]), ['create_missing_programs' => true]);

    $code = Program::query()->where('name_ar', 'برنامج بلا رمز')->sole()->code;

    expect($code)->toMatch('/^IMP-[0-9A-F]{10}$/')->and(mb_strlen($code))->toBeLessThanOrEqual(20);
});

/*
| 7 — one hash per chunk, not per account
*/

test('the accounts of one chunk share one unusable hash, and none of them can sign in', function (): void {
    $admin = aUser('SUPER_ADMIN');

    importBulk($admin, 'users', staffFile([
        ['أ', 'A', 'a@mu.edu.sa', 'INSTRUCTOR', ''],
        ['ب', 'B', 'b@mu.edu.sa', 'INSTRUCTOR', ''],
    ]));

    $hashes = User::query()->whereIn('email', ['a@mu.edu.sa', 'b@mu.edu.sa'])->pluck('password')->unique();

    expect($hashes)->toHaveCount(1)
        ->and(Illuminate\Support\Facades\Hash::check('Track2026', (string) $hashes->first()))->toBeFalse();
});

/*
| 9 — branches only for a coordinator
*/

test('an instructor row is not refused over a branch it would not be given', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $job = importBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 'inst@mu.edu.sa', 'INSTRUCTOR', 'مقر مغلق (طلاب)']]));

    expect($job->imported)->toBe(1)
        ->and(User::query()->where('email', 'inst@mu.edu.sa')->sole()->coordinatorBranchIds())->toBe([]);
});

/*
| 10 — a spreadsheet cannot mint a Super Admin
*/

test('a staff file cannot create a Super Admin, by role name or label', function (string $role): void {
    $admin = aUser('SUPER_ADMIN');
    $before = User::query()->count();

    $job = importBulk($admin, 'users', staffFile([['مدير', 'Admin', 'escalate@mu.edu.sa', $role, '']]));

    expect(rowReasons($job)[2])->toContain(ImportReasons::UNKNOWN_ROLE)
        ->and(User::query()->count())->toBe($before);
})->with(['SUPER_ADMIN', 'super admin', 'سوبر أدمن', 'Super Admin']); // the name, and its labels in lang/{ar,en}/users.php

/*
| The guarantee the review asked for explicitly: nothing before confirmation
*/

test('check and preview write no programme, association, account or branch link', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'HR']);
    Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'الزلفي', 'gender' => 'male']);
    $counts = fn (): array => [
        'programs' => DB::table('programs')->count(), 'course_program' => DB::table('course_program')->count(),
        'users' => DB::table('users')->count(), 'coordinator_branches' => DB::table('coordinator_branches')->count(),
        'courses' => DB::table('courses')->count(),
    ];
    $before = $counts();

    $courses = checkBulk($admin, 'courses', courseFile([['NEW1', 'جديد', 'New', 'HR، برنامج جديد']]), ['create_missing_programs' => true]);
    $staff = checkBulk($admin, 'users', staffFile([['منسق', 'Coord', 'c@mu.edu.sa', 'BRANCH_COORDINATOR', 'الزلفي (طلاب)']]));
    $this->actingAs($admin)->getJson(route('api.v1.imports.preview', $courses))->assertOk();
    $this->actingAs($admin)->getJson(route('api.v1.imports.preview', $staff))->assertOk();

    expect($counts())->toBe($before);
});

test('a committed job cannot be committed again', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'HR']);
    $job = importBulk($admin, 'courses', courseFile([['ONCE1', 'مرة', 'Once', 'HR']]));

    $this->actingAs($admin)->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertConflict();

    expect(Course::query()->where('code', 'ONCE1')->count())->toBe(1)
        ->and(ImportJob::query()->withoutGlobalScopes()->find($job->id)?->status)->toBe('completed');
});
