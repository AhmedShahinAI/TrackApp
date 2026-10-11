<?php

declare(strict_types=1);

use App\Models\ImportJob;

/*
|--------------------------------------------------------------------------
| T348 — the course and staff wizard steps, rendered
|--------------------------------------------------------------------------
*/

test('each upload step states its columns, offers its template, and asks for no term', function (string $mode): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('imports.create.'.$mode))
        ->assertOk()
        ->assertSee(__('imports.modes.'.($mode === 'courses' ? 'course' : 'user')))
        ->assertSee(__('imports.bulk.'.$mode.'.columns'))
        ->assertSee('href="'.route('imports.'.$mode.'.template').'"', false)
        ->assertSee('action="'.route('api.v1.imports.'.$mode.'.store').'"', false)
        ->assertDontSee('name="term_id"', false);
})->with(['courses', 'users']);

test('the staff upload says the accounts are unusable until reset (FR-105)', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('imports.create.users'))
        ->assertOk()
        ->assertSee(__('imports.bulk.users.unusable'));
});

test('a course job\'s mapping step offers the programme choices, and not the roster\'s', function (): void {
    $admin = aUser('SUPER_ADMIN');
    postBulk($admin, 'courses', courseFile([['HR201', 'مبادئ', 'Principles', 'HR']]))->assertCreated();

    $this->actingAs($admin)->get(route('imports.show', ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail()))
        ->assertOk()
        ->assertSee('name="options[create_missing_programs]"', false)
        ->assertSee('name="options[associate_inactive_programs]"', false)
        ->assertSee('name="options[new_program_unit]"', false)
        ->assertDontSee('name="options[create_missing_courses]"', false);
});

test('a staff job\'s mapping step offers no choices at all', function (): void {
    $admin = aUser('SUPER_ADMIN');
    postBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 't@mu.edu.sa', 'INSTRUCTOR', '']]))->assertCreated();

    $this->actingAs($admin)->get(route('imports.show', ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail()))
        ->assertOk()
        ->assertDontSee('name="options[', false);
});

test('the previews count what each mode creates, and show each mode\'s own columns', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aProgram(['code' => 'HR']);

    $courses = checkBulk($admin, 'courses', courseFile([['HR201', 'مبادئ الموارد', 'Principles', 'HR']]));
    $this->actingAs($admin)->get(route('imports.show', $courses))
        ->assertOk()
        ->assertSee(__('imports.preview.new_courses'))
        ->assertSee(__('imports.preview.new_programs'))
        ->assertSee('<bdi dir="ltr">HR201</bdi>', false)
        ->assertDontSee(__('imports.preview.new_students'));

    $staff = checkBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 'teach@mu.edu.sa', 'INSTRUCTOR', '']]));
    $this->actingAs($admin)->get(route('imports.show', $staff))
        ->assertOk()
        ->assertSee(__('imports.preview.new_accounts'))
        ->assertSee(__('imports.preview.accounts_unusable'))
        ->assertSee('<bdi dir="ltr">teach@mu.edu.sa</bdi>', false);
});

test('the entry points: the programmes page imports courses, the users page imports staff', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->get(route('programs.index'))->assertOk()
        ->assertSee('href="'.route('imports.create.courses').'"', false);

    $this->actingAs($admin)->get(route('users.index'))->assertOk()
        ->assertSee('href="'.route('imports.create.users').'"', false);
});
