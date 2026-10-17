<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T312 — allow and deny for the five programme endpoints (contracts/academic.md, FR-095)
|--------------------------------------------------------------------------
|
| Each endpoint gets both halves, and the deny half holds the *neighbouring* permission rather than
| none: `program.view` must not be enough to create, and `program.create` must not be enough to edit.
| A test that only denies an empty-handed caller passes against a route that checks the wrong name.
|
*/

/** @return array<string, string> */
function validProgramPayload(): array
{
    return ['code' => 'NEW'.fake()->unique()->numberBetween(1, 9999), 'name_ar' => 'برنامج', 'name_en' => 'Program', 'unit' => 'admin'];
}

test('api.v1.programs.index — allow and deny', function (): void {
    test()->actingAs(aProgramCaller(['program.view']))->getJson(route('api.v1.programs.index'))->assertOk();
    test()->actingAs(aProgramCaller([]))->getJson(route('api.v1.programs.index'))->assertForbidden();
});

test('api.v1.programs.show — allow and deny', function (): void {
    $program = aProgram();

    test()->actingAs(aProgramCaller(['program.view']))->getJson(route('api.v1.programs.show', $program))->assertOk();
    test()->actingAs(aProgramCaller(['program.edit']))->getJson(route('api.v1.programs.show', $program))->assertForbidden();
});

test('api.v1.programs.store — allow and deny', function (): void {
    test()->actingAs(aProgramCaller(['program.create']))
        ->postJson(route('api.v1.programs.store'), validProgramPayload())
        ->assertCreated();

    test()->actingAs(aProgramCaller(['program.view', 'program.edit']))
        ->postJson(route('api.v1.programs.store'), validProgramPayload())
        ->assertForbidden();
});

test('api.v1.programs.update — allow and deny', function (): void {
    $program = aProgram();

    test()->actingAs(aProgramCaller(['program.edit']))
        ->putJson(route('api.v1.programs.update', $program), ['name_ar' => 'معدّل'])
        ->assertOk();

    test()->actingAs(aProgramCaller(['program.view', 'program.create']))
        ->putJson(route('api.v1.programs.update', $program), ['is_active' => false])
        ->assertForbidden();

    expect($program->fresh()->is_active)->toBeTrue();
});

test('api.v1.programs.courses.update — allow and deny', function (): void {
    $program = aProgram();

    test()->actingAs(aProgramCaller(['program.edit']))
        ->putJson(route('api.v1.programs.courses.update', $program), ['course_ids' => []])
        ->assertOk();

    test()->actingAs(aProgramCaller(['program.view', 'course.edit']))
        ->putJson(route('api.v1.programs.courses.update', $program), ['course_ids' => []])
        ->assertForbidden();
});

test('the seeded roles: a coordinator reads programmes, only a Super Admin changes them (FR-095)', function (): void {
    $program = aProgram();
    $coordinator = aUser('BRANCH_COORDINATOR');
    $instructor = aUser('INSTRUCTOR');

    test()->actingAs($coordinator)->getJson(route('api.v1.programs.index'))->assertOk();
    test()->actingAs($coordinator)->postJson(route('api.v1.programs.store'), validProgramPayload())->assertForbidden();
    test()->actingAs($coordinator)->putJson(route('api.v1.programs.update', $program), ['is_active' => false])->assertForbidden();

    test()->actingAs($instructor)->getJson(route('api.v1.programs.index'))->assertForbidden();

    test()->actingAs(aUser('SUPER_ADMIN'))->putJson(route('api.v1.programs.update', $program), ['is_active' => false])->assertOk();
});

// Two tests, not one: on the web stack `session.auth` signs out a session whose user changes
// mid-test, so a second actingAs() would be answered with the login redirect, not the refusal.
test('programs.index (the screen) — allow', function (): void {
    test()->actingAs(aProgramCaller(['program.view']))->get(route('programs.index'))->assertOk();
});

test('programs.index (the screen) — deny', function (): void {
    test()->actingAs(aProgramCaller([]))->get(route('programs.index'))->assertForbidden();
});

/*
 * FR-095 on the screen: a coordinator may READ the programme list (T315, plan.md Amendment 1) and
 * nothing else. The sidebar leaves it out for them, but the sidebar is not the authorization —
 * these two tests are, together with the deny cases above.
 */
test('programs.index — a coordinator reads the list, with no create, edit or import control', function (): void {
    aProgram(['code' => 'HR']);

    $this->actingAs(aUser('BRANCH_COORDINATOR'))->get(route('programs.index'))
        ->assertOk()
        ->assertSee('>HR<', false)
        ->assertDontSee(__('programs.actions.create'))
        ->assertDontSee(__('programs.actions.import_courses'))
        ->assertDontSee('data-program-id', false)
        ->assertDontSee('id="program-dialog"', false)
        ->assertDontSee('href="'.route('programs.index').'"', false);
});

test('programs.index — the Super Admin\'s sidebar links it, with its own icon', function (): void {
    $html = (string) $this->actingAs(aUser('SUPER_ADMIN'))->get(route('home'))->assertOk()->getContent();

    // T465: v2's Shell.jsx gives programmes `library` and students `graduation-cap`.
    expect($html)->toContain('href="'.route('programs.index').'"')
        ->and(config('navigation.items.programs.icon'))->toBe('library')
        ->and(config('navigation.items.students.icon'))->toBe('graduation-cap');

    // One icon, one destination: a collapsed sidebar tells entries apart by glyph alone. Only entries
    // that lead to the same route may share one (`sections` and `mysections`).
    $byIcon = collect((array) config('navigation.items'))->groupBy('icon')
        ->filter(fn ($items) => $items->pluck('route')->unique()->count() > 1);

    expect($byIcon->keys()->all())->toBe([]);
});
