<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Models\Course;
use App\Modules\Imports\Pipeline\ImportReasons;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T336 — courses in bulk: created, matched, associated (FR-103)
|--------------------------------------------------------------------------
*/

test('new courses are created with their programs; an existing code is matched, not duplicated', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $hr = aProgram(['code' => 'HR', 'name_ar' => 'الموارد البشرية']);
    $cs = aProgram(['code' => 'CS', 'name_ar' => 'البرمجة وعلوم الحاسب']);
    $existing = Course::factory()->create(['organization_id' => $admin->organization_id, 'code' => 'ACC110', 'name_ar' => 'مبادئ المحاسبة']);
    $existing->programs()->attach($hr->id);

    $job = importBulk($admin, 'courses', courseFile([
        ['HR201', 'مبادئ إدارة الموارد البشرية', 'Principles of HRM', 'الموارد البشرية'],
        ['CS212', 'برمجة 2', 'Programming 2', 'CS، HR'],
        ['acc 110', 'اسم آخر', 'Another name', 'CS'],
    ]));

    expect($job->status)->toBe('completed')
        ->and($job->imported)->toBe(3)
        ->and(Course::query()->count())->toBe(3)
        ->and(Course::query()->where('code', 'HR201')->sole()->programs()->pluck('code')->all())->toBe(['HR'])
        ->and(Course::query()->where('code', 'CS212')->sole()->programs()->orderBy('code')->pluck('code')->all())->toBe(['CS', 'HR'])
        // Matched: the stored names stand, and the new programme is ADDED beside the existing one.
        ->and($existing->fresh()->name_ar)->toBe('مبادئ المحاسبة')
        ->and($existing->programs()->orderBy('code')->pluck('code')->all())->toBe(['CS', 'HR']);
});

test('nothing is written before the commit, and the preview says what will be created and matched', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'HR']);
    Course::factory()->create(['organization_id' => $admin->organization_id, 'code' => 'OLD1']);
    $before = CoreCounts::snapshot();
    $programs = DB::table('programs')->count();

    $job = checkBulk($admin, 'courses', courseFile([['NEW1', 'جديد', 'New', 'HR'], ['OLD1', 'قديم', 'Old', 'HR']]));

    expect(CoreCounts::snapshot())->toBe($before)
        ->and(DB::table('programs')->count())->toBe($programs)
        ->and($job->summary['will_create']['courses'])->toBe(1)
        ->and($job->summary['will_match']['courses'])->toBe(1)
        ->and(rowReasons($job)[3])->toContain(ImportReasons::EXISTING_COURSE);
});

test('a missing English name takes the Arabic one; a duplicate code in the file is refused naming its first row', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'HR']);

    $job = importBulk($admin, 'courses', courseFile([
        ['HR201', 'مبادئ الموارد البشرية', '', 'HR'],
        ['hr201', 'مكرر', 'Duplicate', 'HR'],
    ]));

    expect(Course::query()->sole()->name_en)->toBe('مبادئ الموارد البشرية')
        ->and(rowReasons($job)[3])->toContain(ImportReasons::DUPLICATE_IN_FILE)
        ->and($job->errors)->toBe(1);
});

test('the course template is headed so the mapping is suggested without a correction', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->get(route('imports.courses.template'))->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    postBulk($admin, 'courses', courseFile([]))
        ->assertCreated()
        ->assertJsonPath('data.mode', 'course')
        ->assertJson(['data' => ['suggested_mapping' => [
            'course_code' => 'رمز المقرر', 'name_ar' => 'الاسم بالعربية', 'name_en' => 'الاسم بالإنجليزية', 'programs' => 'البرامج',
        ]]]);
});

test('the same course file uploaded again is flagged as a duplicate and creates nothing (FR-067)', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'HR']);
    $file = courseFile([['HR201', 'مبادئ', 'Principles', 'HR']]);
    $first = importBulk($admin, 'courses', sameFileAgain($file));

    postBulk($admin, 'courses', sameFileAgain($file))->assertCreated()->assertJsonPath('data.duplicate_of', $first->id);
});
