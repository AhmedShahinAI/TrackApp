<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\ImportJob;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Section;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\InSetting;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\UiFixtureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/*
|--------------------------------------------------------------------------
| Phase 13 — a programme's code is stored uppercase (T294); settings lists validate through InSetting (T304)
|--------------------------------------------------------------------------
*/

test('a programme code is stored uppercase and trimmed, whoever writes it', function (): void {
    $program = Program::factory()->create(['code' => '  hr ']);

    expect($program->fresh()?->code)->toBe('HR');
});

test('"hr" and "HR" are one programme: the second is refused by the unique key', function (): void {
    $organization = Organization::factory()->create();
    Program::factory()->create(['organization_id' => $organization->id, 'code' => 'HR']);

    expect(fn () => Program::factory()->create(['organization_id' => $organization->id, 'code' => 'hr']))
        ->toThrow(QueryException::class);
});

test('InSetting accepts a configured value and refuses anything else, in the reader\'s language', function (string $locale): void {
    (new SettingSeeder)->run();
    app()->setLocale($locale);

    $validate = fn (mixed $value) => Validator::make(['degree' => $value], ['degree' => [new InSetting(Settings::SECTION_DEGREES)]]);

    expect($validate('bachelor')->passes())->toBeTrue()
        ->and($validate('intermediate_diploma')->passes())->toBeTrue()
        ->and($validate('doctorate')->fails())->toBeTrue()
        ->and($validate(['bachelor'])->fails())->toBeTrue();

    $message = $validate('doctorate')->errors()->first('degree');

    // The whole message, attribute included: a merely-Arabic template still passed with «في degree».
    expect($message)->toBe(__('validation.in_setting', ['attribute' => __('validation.attributes.degree')]))
        ->and($locale === 'ar' ? $message : '')->not->toContain('degree')
        ->and(preg_match('/\p{Arabic}/u', $message))->toBe($locale === 'ar' ? 1 : 0);
})->with(['ar', 'en']);

test('the section form validates degree and activity through InSetting', function (): void {
    (new SettingSeeder)->run();
    AcademicTerm::factory()->create(); // a section needs a term to exist (T TermRequiredFirst)
    $admin = aUser('SUPER_ADMIN');

    $response = $this->actingAs($admin)->postJson(route('api.v1.sections.store'), [
        'degree' => 'doctorate',
        'activity' => 'seminar',
    ])->assertStatus(422);

    expect($response->json('errors.degree.0'))->toBe(__('validation.in_setting', ['attribute' => __('validation.attributes.degree')]))
        ->and($response->json('errors'))->toHaveKey('activity');
});

/*
|--------------------------------------------------------------------------
| Review corrections (2026-10-10)
|--------------------------------------------------------------------------
*/

test('a lowercase, untrimmed or empty code is refused by the database, not only by the model', function (string $code): void {
    $program = Program::factory()->create(['code' => 'HR']);

    // Query-builder writes skip the Eloquent mutator — the path a bulk import would take.
    expect(fn () => Program::query()->whereKey($program->id)->update(['code' => $code]))
        ->toThrow(QueryException::class)
        ->and(fn () => DB::table('programs')->insert([
            'organization_id' => $program->organization_id, 'code' => $code,
            'name_ar' => 'برنامج', 'name_en' => 'Program', 'unit' => 'admin', 'is_active' => true,
        ]))->toThrow(QueryException::class)
        ->and($program->fresh()?->code)->toBe('HR');
})->with(['hr', ' HR', 'Hr', '']);

test('a missing code fails NOT NULL instead of being stored as an empty string', function (): void {
    expect(fn () => Program::factory()->create(['code' => null]))->toThrow(QueryException::class);
});

test('the same code is allowed in two organizations', function (): void {
    $first = Program::factory()->create(['organization_id' => Organization::factory()->create()->id, 'code' => 'HR']);
    $second = Program::factory()->create(['organization_id' => Organization::factory()->create()->id, 'code' => 'hr']);

    expect($second->code)->toBe($first->code);
});

test('a soft-deleted programme keeps its code reserved and stays the programme of its sections', function (): void {
    $section = Section::factory()->create();
    $program = $section->program;
    $program->delete();

    expect(fn () => Program::factory()->create(['organization_id' => $program->organization_id, 'code' => $program->code]))
        ->toThrow(QueryException::class)
        ->and($section->fresh()?->program?->id)->toBe($program->id);
});

test('a programme holding sections cannot be force-deleted, and the refusal is translated (FR-092)', function (): void {
    $section = Section::factory()->create();
    app()->setLocale('ar');

    expect(fn () => $section->program->forceDelete())
        ->toThrow(ConflictHttpException::class, __('programs.errors.has_sections'))
        ->and(Program::withTrashed()->whereKey($section->program_id)->exists())->toBeTrue()
        ->and(Program::factory()->create()->forceDelete())->toBeTrue();
});

test('removing a course or a programme removes its pivot rows', function (): void {
    $course = Course::factory()->create();
    $kept = Program::factory()->create();
    $purged = Program::factory()->create();
    $course->programs()->attach([$kept->id, $purged->id]);

    $purged->forceDelete();
    expect(DB::table('course_program')->where('course_id', $course->id)->pluck('program_id')->all())->toBe([$kept->id]);

    DB::table('courses')->where('id', $course->id)->delete();
    expect(DB::table('course_program')->where('program_id', $kept->id)->exists())->toBeFalse();
});

test('a factory-built section takes its course and programme from its branch\'s organization', function (): void {
    anOrganisation();
    $other = Organization::factory()->create();
    $section = Section::factory()->create(['branch_id' => Branch::factory()->create(['organization_id' => $other->id])->id]);

    expect($section->course->organization_id)->toBe($other->id)
        ->and($section->program->organization_id)->toBe($other->id);
});

test('the section form refuses another organization\'s, a deactivated, or a deleted programme', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $own = Program::factory()->create(['organization_id' => $admin->organization_id]);
    $foreign = Program::factory()->create(['organization_id' => Organization::factory()->create()->id]);
    $inactive = Program::factory()->inactive()->create(['organization_id' => $admin->organization_id]);
    $deleted = Program::factory()->create(['organization_id' => $admin->organization_id]);
    $deleted->delete();
    AcademicTerm::factory()->create(); // without a term the endpoint answers 409 before validating

    $errorsFor = fn (int $programId): array => $this->actingAs($admin)
        ->postJson(route('api.v1.sections.store'), ['program_id' => $programId])
        ->assertUnprocessable()
        ->json('errors');

    expect($errorsFor($own->id))->not->toHaveKey('program_id')
        ->and($errorsFor($foreign->id))->toHaveKey('program_id')
        ->and($errorsFor($inactive->id))->toHaveKey('program_id')
        ->and($errorsFor($deleted->id))->toHaveKey('program_id');
});

test('editing a section may keep its programme after that programme is deactivated', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create(['branch_id' => Branch::factory()->create(['organization_id' => $admin->organization_id])->id]);
    Program::query()->whereKey($section->program_id)->update(['is_active' => false]);
    $other = Program::factory()->inactive()->create(['organization_id' => $admin->organization_id]);

    $errorsFor = fn (int $programId): array => $this->actingAs($admin)
        ->putJson(route('api.v1.sections.update', $section), ['program_id' => $programId, 'section_number' => ''])
        ->assertUnprocessable()
        ->json('errors');

    expect($errorsFor($section->program_id))->not->toHaveKey('program_id')
        ->and($errorsFor($other->id))->toHaveKey('program_id');
});

test('the section form offers only the caller\'s organization\'s active programmes', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $own = Program::factory()->create(['organization_id' => $admin->organization_id]);
    Program::factory()->inactive()->create(['organization_id' => $admin->organization_id]);
    Program::factory()->create(['organization_id' => Organization::factory()->create()->id]);

    $options = (fn () => $this->createOptions(request()->setUserResolver(fn () => $admin)))
        ->call(app(App\Modules\Sections\Http\Controllers\SectionController::class));

    expect(array_keys($options['programOptions']))->toBe([$own->id]);
});

test('the import mapping step refuses another organization\'s programme for new courses', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $own = Program::factory()->create(['organization_id' => $admin->organization_id]);
    $foreign = Program::factory()->create(['organization_id' => Organization::factory()->create()->id]);
    $job = ImportJob::query()->create([
        'user_id' => $admin->id, 'mode' => 'central', 'term_id' => AcademicTerm::factory()->create()->id,
        'original_filename' => 'roster.xlsx', 'file_path' => 'imports/roster.xlsx',
        'file_hash' => str_repeat('e', 64), 'status' => 'uploaded',
    ]);

    $errorsFor = fn (int $programId): array => $this->actingAs($admin)
        ->putJson(route('api.v1.imports.mapping.update', $job), [
            'mapping' => ['unused' => null], 'options' => ['new_course_program_id' => $programId],
        ])->json('errors') ?? [];

    expect($errorsFor($foreign->id))->toHaveKey('options.new_course_program_id')
        ->and($errorsFor($own->id))->not->toHaveKey('options.new_course_program_id');
});

test('the seeded vocabulary is 47 permissions, with the programme and import grants T302 names', function (): void {
    // Twice: the seeders must be idempotent.
    foreach ([1, 2] as $run) {
        (new PermissionSeeder)->run();
        (new RoleSeeder)->run();
    }

    $six = ['program.view', 'program.create', 'program.edit', 'import.registrar.run', 'import.course.run', 'import.user.run'];
    $coordinator = Role::findByName('BRANCH_COORDINATOR', 'web');

    expect(PermissionSeeder::PERMISSIONS)->toHaveCount(47)
        ->and(Permission::query()->count())->toBe(47)
        ->and(Role::findByName('SUPER_ADMIN', 'web')->hasAllPermissions($six))->toBeTrue()
        ->and(array_values(array_filter($six, fn (string $name): bool => $coordinator->hasPermissionTo($name))))
        ->toBe(['program.view'])
        ->and(PermissionSeeder::PERMISSIONS)->not->toContain('program.delete');
});

test('re-running the settings seeder keeps an administrator\'s edit', function (): void {
    (new SettingSeeder)->run();
    DB::table('settings')->where('key', Settings::SECTION_DEGREES)
        ->update(['value' => json_encode([['value' => 'master', 'ar' => 'الماجستير', 'en' => 'Master']])]);
    Settings::forget();

    (new SettingSeeder)->run();

    expect(Settings::values(Settings::SECTION_DEGREES))->toBe(['master']);
});

test('the UI fixture seeder refuses to run outside local and testing', function (string $environment): void {
    app()->detectEnvironment(fn () => $environment);

    expect(fn () => (new UiFixtureSeeder)->run())->toThrow(RuntimeException::class)
        ->and(Program::query()->count())->toBe(0);
})->with(['production', 'staging']);
