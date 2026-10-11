<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\ImportJob;

/*
|--------------------------------------------------------------------------
| T334 — the registrar upload and its confirmation panel, rendered
|--------------------------------------------------------------------------
*/

test('step 1 asks for the term and the instructor the export cannot carry (FR-101)', function (): void {
    $world = registrarWorld();

    $this->actingAs($world['admin'])->get(route('imports.create.registrar'))
        ->assertOk()
        ->assertSee(__('imports.modes.registrar'))
        ->assertSee('name="term_id"', false)
        ->assertSee('name="instructor_id"', false)
        ->assertSee('action="'.route('api.v1.imports.registrar.store').'"', false)
        ->assertSee('د. سارة العتيبي');
});

test('a refusal at upload is shown on the page it came from', function (): void {
    $world = registrarWorld();

    $this->actingAs($world['admin'])
        ->from(route('imports.create.registrar'))
        ->post(route('api.v1.imports.registrar.store'), [
            'file' => registrarFile(['المقر' => 'الرياض (طلاب)'] + REGISTRAR_BLOCK, registrarRows(1)),
            'term_id' => $world['term']->id,
            'instructor_id' => $world['instructor']->id,
        ])
        ->assertRedirect(route('imports.create.registrar'));

    $this->actingAs($world['admin'])->get(route('imports.create.registrar'))
        ->assertOk()
        ->assertSee(__('imports.registrar.campus_unknown', ['value' => 'الرياض (طلاب)']));
});

test('the confirmation panel shows each value as the file wrote it beside what it resolved to', function (): void {
    $world = registrarWorld();
    postRegistrar($world, registrarFile(rows: registrarRows(2)))->assertCreated();
    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.registrar.confirm_title'))
        ->assertSee('المجمعة (طلاب)')
        ->assertSee('المجمعة ('.__('sections.gender.male').')')
        ->assertSee('CS102 · برمجة 2')
        ->assertSee('2298 — '.__('imports.registrar.section_new'))
        ->assertSee('الدبلوم المتوسط')
        ->assertSee('د. سارة العتيبي')
        ->assertSee(__('imports.registrar.confirm_and_check'))
        // A new section: the programme choice is offered.
        ->assertSee('name="options[program_id]"', false)
        // An unambiguous course: no course choice.
        ->assertDontSee('name="options[course_id]"', false);
});

test('an ambiguous course is a choice on the panel, with a warning, not a refusal', function (): void {
    $world = registrarWorld();
    $twin = Course::factory()->create(['organization_id' => $world['course']->organization_id, 'code' => 'IT102', 'name_ar' => 'برمجة 2']);
    postRegistrar($world, registrarFile(rows: registrarRows(1)))->assertCreated();
    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.registrar.course_ambiguous', ['value' => 'برمجة 2']))
        ->assertSee('name="options[course_id]"', false)
        ->assertSee('<option value="'.$twin->id.'"', false)
        ->assertSee(__('imports.registrar.skip_rows'));
});

test('the imports list offers the registrar import to whoever may run it', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('imports.index'))
        ->assertOk()
        ->assertSee('href="'.route('imports.create.registrar').'"', false);
});
