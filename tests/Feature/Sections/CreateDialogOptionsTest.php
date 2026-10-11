<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Course;
use App\Models\Organization;

/*
|--------------------------------------------------------------------------
| The section create dialog offers only the caller's organization's records
|--------------------------------------------------------------------------
|
| None of Course, Branch or User carries an organization scope, so the dialog's lists must draw the
| boundary themselves. Unfiltered, a Super Admin was shown another college's course codes, campuses
| and instructors' names.
|
*/

test('courses, branches, programmes and instructors of another organization are not offered', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $foreign = Organization::factory()->create();

    Course::factory()->create(['organization_id' => $admin->organization_id, 'code' => 'OWN101']);
    Course::factory()->create(['organization_id' => $foreign->id, 'code' => 'FOREIGN101']);
    Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'مقرنا']);
    Branch::factory()->create(['organization_id' => $foreign->id, 'campus_name_ar' => 'مقر غريب']);
    aProgram(['code' => 'OWN']);
    App\Models\Program::factory()->create(['organization_id' => $foreign->id, 'code' => 'ALIEN']);
    aUser('INSTRUCTOR', ['name_ar' => 'مدرس منا']);
    aUser('INSTRUCTOR', ['name_ar' => 'مدرس غريب', 'organization_id' => $foreign->id]);
    aUser('INSTRUCTOR', ['name_ar' => 'مدرس موقوف', 'is_active' => false]);
    App\Models\AcademicTerm::factory()->create();

    $this->actingAs($admin)->get(route('sections.index'))
        ->assertOk()
        ->assertSee('OWN101')->assertDontSee('FOREIGN101')
        ->assertSee('مقرنا')->assertDontSee('مقر غريب')
        ->assertSee('OWN · ', false)->assertDontSee('ALIEN')
        ->assertSee('مدرس منا')->assertDontSee('مدرس غريب')->assertDontSee('مدرس موقوف');
});
