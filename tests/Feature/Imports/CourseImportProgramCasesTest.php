<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Program;
use App\Modules\Imports\Pipeline\ImportReasons;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T337 — unknown and deactivated programmes (spec acceptance 2, Edge Cases)
|--------------------------------------------------------------------------
*/

test('an unknown program flags the row; skipped unless the operator chose to create it', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $job = importBulk($admin, 'courses', courseFile([['DS101', 'مقدمة في علم البيانات', 'Intro to DS', 'علم البيانات']]));

    expect(rowReasons($job)[2])->toContain(ImportReasons::UNKNOWN_PROGRAM, ImportReasons::SKIPPED_BY_CHOICE)
        ->and($job->summary['will_skip'])->toBe(1)
        ->and(Course::query()->count())->toBe(0)
        ->and(Program::query()->count())->toBe(0);
});

test('with "create programs" chosen, the program is created once, in the chosen unit, and associated', function (): void {
    $admin = aUser('SUPER_ADMIN');
    DB::table('audit_logs')->delete();

    $job = importBulk($admin, 'courses', courseFile([
        ['DS101', 'مقدمة في علم البيانات', 'Intro to DS', 'علم البيانات'],
        ['DS102', 'تعلم الآلة', 'Machine Learning', 'علم البيانات، DS'],
    ]), ['create_missing_programs' => true, 'new_program_unit' => 'tech']);

    $byName = Program::query()->where('name_ar', 'علم البيانات')->sole();
    $byCode = Program::query()->where('code', 'DS')->sole();

    expect($job->summary['will_create']['programs'])->toBe(2)
        ->and($byName->unit)->toBe('tech')
        ->and($byName->code)->toStartWith('IMP-')
        ->and($byCode->name_ar)->toBe('DS')
        ->and(Course::query()->where('code', 'DS102')->sole()->programs()->count())->toBe(2)
        ->and(Course::query()->where('code', 'DS101')->sole()->programs()->pluck('programs.id')->all())->toBe([$byName->id])
        // Each created programme is audited as one created by hand would be (FR-093).
        ->and(DB::table('audit_logs')->where('action', 'program.created')->count())->toBe(2);
});

test('a deactivated program is associated only on confirmation; the row\'s other programs still apply', function (bool $confirm): void {
    $admin = aUser('SUPER_ADMIN');
    $active = aProgram(['code' => 'HR']);
    $retired = aProgram(['code' => 'OLD', 'is_active' => false]);

    $job = importBulk($admin, 'courses', courseFile([['HR201', 'مبادئ', 'Principles', 'HR، OLD']]), $confirm ? ['associate_inactive_programs' => true] : []);

    $expected = $confirm ? [$active->id, $retired->id] : [$active->id];

    expect(rowReasons($job)[2])->toContain(ImportReasons::INACTIVE_PROGRAM)
        ->and(Course::query()->sole()->programs()->orderBy('programs.id')->pluck('programs.id')->all())->toBe($expected);
})->with(['not confirmed' => [false], 'confirmed' => [true]]);

test('a program name two programs share is refused rather than guessed', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'MKT', 'name_ar' => 'التسويق']);
    aProgram(['code' => 'DMK', 'name_ar' => 'التسويق']);

    $job = importBulk($admin, 'courses', courseFile([['MKT150', 'مبادئ التسويق', 'Marketing', 'التسويق']]));

    expect(rowReasons($job)[2])->toContain(ImportReasons::AMBIGUOUS_PROGRAM)
        ->and(Course::query()->count())->toBe(0);
});

test('another organization\'s program is unknown here', function (): void {
    $admin = aUser('SUPER_ADMIN');
    Program::factory()->create(['organization_id' => App\Models\Organization::factory()->create()->id, 'code' => 'HR']);

    $job = checkBulk($admin, 'courses', courseFile([['HR201', 'مبادئ', 'Principles', 'HR']]));

    expect(rowReasons($job)[2])->toContain(ImportReasons::UNKNOWN_PROGRAM);
});
