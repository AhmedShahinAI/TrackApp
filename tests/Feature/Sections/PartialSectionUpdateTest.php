<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Program;
use App\Models\Section;

/*
|--------------------------------------------------------------------------
| PUT /sections/{id} — every field optional (contracts/academic.md)
|--------------------------------------------------------------------------
|
| An omitted field keeps its stored value. The uniqueness rule still judges the combination the
| edit would produce, and an explicit null programme is still refused (FR-090).
|
*/

test('an unrelated change alone is accepted and leaves every other field as it was', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create(['program_id' => aProgram()->id, 'degree' => 'bachelor']);
    $before = $section->only(['term_id', 'branch_id', 'course_id', 'program_id', 'section_number']);

    $this->actingAs($admin)
        ->putJson(route('api.v1.sections.update', $section), ['degree' => 'intermediate_diploma'])
        ->assertOk()
        ->assertJsonPath('data.degree', 'intermediate_diploma');

    expect($section->fresh()->only(array_keys($before)))->toBe($before);
});

test('the same unrelated change on a section whose programme was deactivated keeps that programme (spec Edge Cases)', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create(['program_id' => aProgram()->id]);
    Program::query()->whereKey($section->program_id)->update(['is_active' => false]);

    $this->actingAs($admin)
        ->putJson(route('api.v1.sections.update', $section), ['activity' => 'lecture'])
        ->assertOk();

    expect($section->fresh()->program_id)->toBe($section->program_id);
});

test('a partial edit is still judged as the combination it produces', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $other = AcademicTerm::factory()->create();
    $section = Section::factory()->create(['section_number' => '214']);
    Section::factory()->create([
        'term_id' => $other->id, 'branch_id' => $section->branch_id,
        'course_id' => $section->course_id, 'section_number' => '214',
    ]);

    // Only the term changes; the number it keeps already exists in that term.
    $this->actingAs($admin)
        ->putJson(route('api.v1.sections.update', $section), ['term_id' => $other->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.section_number.0', __('sections.errors.duplicate_key'));
});

test('an explicit null programme is refused, not filled in', function (): void {
    $section = Section::factory()->create();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->putJson(route('api.v1.sections.update', $section), ['program_id' => null])
        ->assertUnprocessable()
        ->assertJsonPath('errors.program_id.0', __('sections.errors.program_required'));
});
