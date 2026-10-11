<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T308 — many courses to many programmes (FR-091)
|--------------------------------------------------------------------------
*/

test('a course belongs to several programmes and a programme holds several courses', function (): void {
    $organization = anOrganisation();
    [$islm, $acc] = Course::factory()->count(2)->create(['organization_id' => $organization->id])->all();
    $hr = aProgram(['code' => 'HR']);
    $mkt = aProgram(['code' => 'MKT']);
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->putJson(route('api.v1.programs.courses.update', $hr), ['course_ids' => [$islm->id, $acc->id]])
        ->assertOk()
        ->assertJsonPath('data.courses_count', 2);

    $this->actingAs($admin)->putJson(route('api.v1.programs.courses.update', $mkt), ['course_ids' => [$islm->id]])
        ->assertOk();

    expect($islm->programs()->pluck('code')->sort()->values()->all())->toBe(['HR', 'MKT'])
        ->and($hr->courses()->count())->toBe(2)
        ->and($this->actingAs($admin)->getJson(route('api.v1.programs.show', $hr))->json('data.courses'))->toHaveCount(2);
});

test('replacing the set is audited once with the previous and the new ids; an unchanged set writes nothing', function (): void {
    $courses = Course::factory()->count(3)->create(['organization_id' => anOrganisation()->id]);
    $program = aProgram();
    $program->courses()->attach([$courses[0]->id, $courses[1]->id]);
    DB::table('audit_logs')->delete();
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->putJson(route('api.v1.programs.courses.update', $program), [
        'course_ids' => [$courses[2]->id, $courses[1]->id],
    ])->assertOk();

    $entry = DB::table('audit_logs')->sole();
    $expectedNew = collect([$courses[1]->id, $courses[2]->id])->sort()->values()->all();

    expect($entry->action)->toBe('program.courses_changed')
        ->and(json_decode((string) $entry->old_values, true))->toBe(['course_ids' => [$courses[0]->id, $courses[1]->id]])
        ->and(json_decode((string) $entry->new_values, true))->toBe(['course_ids' => $expectedNew]);

    $this->actingAs($admin)->putJson(route('api.v1.programs.courses.update', $program), [
        'course_ids' => [$courses[1]->id, $courses[2]->id],
    ])->assertOk();

    expect(DB::table('audit_logs')->count())->toBe(1);
});

test('an empty list clears the associations; a missing one is refused', function (): void {
    $program = aProgram();
    $program->courses()->attach(Course::factory()->create(['organization_id' => anOrganisation()->id]));
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->putJson(route('api.v1.programs.courses.update', $program), [])
        ->assertUnprocessable()->assertJsonValidationErrors(['course_ids']);

    $this->actingAs($admin)->putJson(route('api.v1.programs.courses.update', $program), ['course_ids' => []])->assertOk();

    expect($program->courses()->count())->toBe(0);
});

test('another organization\'s course is refused', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $program = aProgram();
    $foreign = Course::factory()->create(['organization_id' => Organization::factory()->create()->id]);

    $this->actingAs($admin)
        ->putJson(route('api.v1.programs.courses.update', $program), ['course_ids' => [$foreign->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['course_ids.0']);

    expect($program->courses()->count())->toBe(0);
});

test('a programme can be created with its courses in one request', function (): void {
    $courses = Course::factory()->count(2)->create(['organization_id' => anOrganisation()->id]);

    $this->actingAs(aUser('SUPER_ADMIN'))->postJson(route('api.v1.programs.store'), [
        'code' => 'EC', 'name_ar' => 'التجارة الإلكترونية', 'name_en' => 'E-Commerce', 'unit' => 'tech',
        'course_ids' => $courses->pluck('id')->all(),
    ])->assertCreated()->assertJsonPath('data.courses_count', 2);
});
