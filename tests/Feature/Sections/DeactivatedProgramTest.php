<?php

declare(strict_types=1);

use App\Models\Program;
use App\Models\Section;

/*
|--------------------------------------------------------------------------
| T311 — a deactivated programme: refused for new sections, kept on existing ones (spec Edge Cases)
|--------------------------------------------------------------------------
*/

test('a deactivated programme is refused when creating a section', function (): void {
    $inactive = aProgram(['is_active' => false]);

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->postJson(route('api.v1.sections.store'), aValidSectionRequest(['program_id' => $inactive->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['program_id']);

    expect(Section::query()->count())->toBe(0);
});

test('it is not offered in the create dialog', function (): void {
    aProgram(['code' => 'LIVE', 'name_ar' => 'برنامج فعّال']);
    aProgram(['code' => 'GONE', 'name_ar' => 'برنامج موقوف', 'is_active' => false]);

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('sections.index'))
        ->assertOk()
        ->assertSee('LIVE · برنامج فعّال')
        ->assertDontSee('GONE · برنامج موقوف');
});

test('an existing section keeps it, and shows it on the list, the detail and the API', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $program = aProgram(['code' => 'RE', 'name_ar' => 'إدارة وتطوير العقار']);
    $section = Section::factory()->create(['program_id' => $program->id]);

    $this->actingAs($admin)->putJson(route('api.v1.programs.update', $program), ['is_active' => false])->assertOk();

    expect($section->fresh()->program_id)->toBe($program->id);

    $this->actingAs($admin)->get(route('sections.index'))->assertOk()->assertSee('إدارة وتطوير العقار');
    $this->actingAs($admin)->get(route('sections.show', $section))->assertOk()->assertSee('إدارة وتطوير العقار');
    $this->actingAs($admin)->getJson(route('api.v1.sections.show', $section))
        ->assertOk()
        ->assertJsonPath('data.program.code', 'RE');
});

test('editing that section may keep its deactivated programme', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create(['program_id' => aProgram()->id]);
    Program::query()->whereKey($section->program_id)->update(['is_active' => false]);

    // The whole record, as the edit form sends it, with only the activity changed.
    $this->actingAs($admin)
        ->putJson(route('api.v1.sections.update', $section), [
            'term_id' => $section->term_id,
            'branch_id' => $section->branch_id,
            'course_id' => $section->course_id,
            'program_id' => $section->program_id,
            'section_number' => $section->section_number,
            'degree' => $section->degree,
            'activity' => 'lecture',
        ])
        ->assertOk();

    expect($section->fresh()->program_id)->not->toBeNull();
});
