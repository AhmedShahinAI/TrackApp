<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| T342 — forty courses and thirty staff accounts in under ten minutes, total (SC-023)
|--------------------------------------------------------------------------
|
| The time measured is the platform's: upload, check and commit for both files, through the real
| endpoints and the queue (synchronous under the test driver). The operator's minutes reading the
| preview are theirs, not the budget's. In the performance group, so the default run excludes it.
|
*/

test('40 courses and 30 staff accounts load in under 10 minutes total', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $programs = collect(range(1, 5))->map(fn (int $i) => aProgram(['code' => 'P'.$i]))->all();
    $branch = App\Models\Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'الزلفي', 'gender' => 'male']);

    $courses = array_map(fn (int $i): array => [
        'C'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'مقرر '.$i, 'Course '.$i, $programs[$i % 5]->code.'، '.$programs[($i + 1) % 5]->code,
    ], range(1, 40));

    $staff = array_map(fn (int $i): array => [
        'منسوب '.$i, 'Staff '.$i, 'staff'.$i.'@mu.edu.sa', $i % 5 === 0 ? 'BRANCH_COORDINATOR' : 'INSTRUCTOR', $i % 5 === 0 ? 'الزلفي (طلاب)' : '',
    ], range(1, 30));

    $started = microtime(true);

    $courseJob = importBulk($admin, 'courses', courseFile($courses));
    $staffJob = importBulk($admin, 'users', staffFile($staff));

    $seconds = microtime(true) - $started;

    expect($courseJob->imported)->toBe(40)
        ->and($staffJob->imported)->toBe(30)
        ->and(Course::query()->count())->toBe(40)
        ->and(User::query()->where('email', 'like', 'staff%@mu.edu.sa')->count())->toBe(30)
        ->and($branch->coordinators()->count())->toBe(6)
        ->and($seconds)->toBeLessThan(600);
})->group('performance');
