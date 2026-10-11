<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T338 — staff accounts in bulk, with their roles and branches (FR-104)
|--------------------------------------------------------------------------
*/

test('accounts are created with their role and, for coordinators, their branches', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $male = Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'الزلفي', 'gender' => 'male']);
    $female = Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'الزلفي', 'gender' => 'female']);

    $job = importBulk($admin, 'users', staffFile([
        ['د. هند المطيري', 'Dr. Hind', 'H.Almutairi@mu.edu.sa', 'INSTRUCTOR', ''],
        ['أ. سالم العنزي', 'Salem', 'salem@mu.edu.sa', 'منسق فرع', 'الزلفي (طلاب)، الزلفي (طالبات)'],
        ['م. ريم', 'Reem', 'reem@mu.edu.sa', 'Branch Coordinator', 'الزلفي (طالبات)'],
    ]));

    $hind = User::query()->where('email', 'h.almutairi@mu.edu.sa')->sole();
    $salem = User::query()->where('email', 'salem@mu.edu.sa')->sole();
    $reem = User::query()->where('email', 'reem@mu.edu.sa')->sole();

    expect($job->imported)->toBe(3)
        ->and($hind->hasRole('INSTRUCTOR'))->toBeTrue()
        ->and($hind->name_ar)->toBe('د. هند المطيري')
        ->and($hind->organization_id)->toBe($admin->organization_id)
        ->and($salem->hasRole('BRANCH_COORDINATOR'))->toBeTrue()
        ->and($salem->coordinatorBranchIds())->toEqualCanonicalizing([$male->id, $female->id])
        ->and($reem->coordinatorBranchIds())->toBe([$female->id]);
});

test('a branch named for a non-coordinator grants nothing', function (): void {
    $admin = aUser('SUPER_ADMIN');
    Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'الزلفي', 'gender' => 'male']);

    importBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 't@mu.edu.sa', 'INSTRUCTOR', 'الزلفي (طلاب)']]));

    expect(User::query()->where('email', 't@mu.edu.sa')->sole()->coordinatorBranchIds())->toBe([]);
});

test('nothing is written before the commit, and the creation is audited per account after it', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $before = User::query()->count();

    $job = checkBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 'one@mu.edu.sa', 'INSTRUCTOR', '']]));

    expect(User::query()->count())->toBe($before)
        ->and($job->summary['will_create']['accounts'])->toBe(1);

    DB::table('audit_logs')->delete();
    previewAndCommit($admin, $job);

    expect(DB::table('audit_logs')->where('action', 'user.created')->count())->toBe(1)
        ->and(DB::table('audit_logs')->where('action', 'import.committed')->count())->toBe(1);
});

test('the role may be written as the role name or its label in either language', function (string $role): void {
    $admin = aUser('SUPER_ADMIN');

    importBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 'any@mu.edu.sa', $role, '']]));

    expect(User::query()->where('email', 'any@mu.edu.sa')->sole()->hasRole('INSTRUCTOR'))->toBeTrue();
    // The labels as lang/{ar,en}/users.php write them (a dataset is built before the app boots).
})->with(['INSTRUCTOR', 'instructor', 'مدرس', 'Instructor']);
