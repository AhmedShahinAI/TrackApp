<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\ImportJob;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Allow and deny for every endpoint US2 adds (constitution VIII)
|--------------------------------------------------------------------------
|
| ## The deny case is harder here than it looks
|
| For a route carrying `{section}`, `{student}` or `{import}`, the obvious deny — sign in as someone
| without the permission and ask for a record — returns **404**, not 403. That is correct: the
| scoped route binding resolves before `can:` runs, so a record the caller cannot see is absent
| before the permission is ever consulted (research.md R-01).
|
| A 404 proves nothing about the permission. So each deny below first makes the record genuinely
| visible to the actor — they teach the section, the student is enrolled in it, the import is
| theirs — and only then asserts 403. What is left when visibility is removed from the question is
| the permission, which is what these tests exist to pin.
|
| The role used is seeded inside the test with `dashboard.view` and nothing else, so it is a caller
| who can sign in and reach a landing page while holding none of the permissions below.
|
*/

/**
 * A user holding `dashboard.view` and nothing else.
 */
function aPermissionlessUser(): User
{
    $user = aUser('INSTRUCTOR');

    $role = Role::findOrCreate('NO_PERMISSIONS', 'web');
    $role->syncPermissions(['dashboard.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles(['NO_PERMISSIONS']);

    return $user->fresh();
}

test('api.v1.sections.index — allow and deny', function (): void {
    Section::factory()->count(2)->create();

    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.sections.index'))->assertOk();
    $this->actingAs(aPermissionlessUser())->getJson(route('api.v1.sections.index'))->assertForbidden();
});

test('api.v1.sections.export — allow and deny', function (): void {
    Section::factory()->create();

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('api.v1.sections.export'))->assertOk();
    $this->actingAs(aPermissionlessUser())->get(route('api.v1.sections.export'))->assertForbidden();
});

test('api.v1.sections.show — allow, and 403 on a section the denied caller can see', function (): void {
    $denied = aPermissionlessUser();
    $section = Section::factory()->create(['instructor_id' => $denied->id]);

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->getJson(route('api.v1.sections.show', $section))
        ->assertOk();

    // They teach it, so the scope admits it; the refusal that remains is the permission's.
    $this->actingAs($denied->fresh())
        ->getJson(route('api.v1.sections.show', $section))
        ->assertForbidden();
});

test('api.v1.students.index — allow and deny', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.students.index'))->assertOk();
    $this->actingAs(aPermissionlessUser())->getJson(route('api.v1.students.index'))->assertForbidden();
});

test('api.v1.students.show — allow, and 403 on a student the denied caller can see', function (): void {
    $denied = aPermissionlessUser();
    $section = Section::factory()->create(['instructor_id' => $denied->id]);
    $student = Student::factory()->create();

    SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => $student->id,
        'status' => 'active',
    ]);

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->getJson(route('api.v1.students.show', $student))
        ->assertOk();

    $this->actingAs($denied->fresh())
        ->getJson(route('api.v1.students.show', $student))
        ->assertForbidden();
});

test('api.v1.audit-logs.index — allow and deny', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.audit-logs.index'))->assertOk();
    $this->actingAs(aPermissionlessUser())->getJson(route('api.v1.audit-logs.index'))->assertForbidden();
});

test('api.v1.audit-logs.export — allow and deny, on its own permission', function (): void {
    // A separate permission from the list: reading the trail and taking a copy of it are different
    // authorities, and a coordinator holds only the first.
    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('api.v1.audit-logs.export'))->assertOk();
    $this->actingAs(aUser('BRANCH_COORDINATOR'))->get(route('api.v1.audit-logs.export'))->assertForbidden();
});

test('api.v1.imports.show — allow, and 403 on an import the denied caller owns', function (): void {
    $denied = aPermissionlessUser();

    $job = ImportJob::query()->create([
        'user_id' => $denied->id,
        'mode' => 'central',
        'original_filename' => 'roster.xlsx',
        'file_path' => 'imports/roster.xlsx',
        'file_hash' => str_repeat('b', 64),
        'status' => 'completed',
    ]);

    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.imports.show', $job))->assertOk();
    $this->actingAs($denied->fresh())->getJson(route('api.v1.imports.show', $job))->assertForbidden();
});

test('api.v1.users.roles.update — allow and deny', function (): void {
    $target = aUser('INSTRUCTOR');

    $this->actingAs(aPermissionlessUser())
        ->putJson(route('api.v1.users.roles.update', $target), ['roles' => ['INSTRUCTOR']])
        ->assertForbidden();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->putJson(route('api.v1.users.roles.update', $target), ['roles' => ['BRANCH_COORDINATOR']])
        ->assertOk();
});

test('api.v1.users.branches.update — allow and deny', function (): void {
    $organisation = anOrganisation();
    $branch = Branch::factory()->create(['organization_id' => $organisation->id]);
    $target = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);

    $this->actingAs(aPermissionlessUser())
        ->putJson(route('api.v1.users.branches.update', $target), ['branch_ids' => [$branch->id]])
        ->assertForbidden();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->putJson(route('api.v1.users.branches.update', $target), ['branch_ids' => [$branch->id]])
        ->assertOk();
});

test('api.v1.sections.access-grants.store — allow and deny', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $denied = aPermissionlessUser();
    $section = Section::factory()->create(['instructor_id' => $denied->id]);

    $this->actingAs($denied->fresh())
        ->postJson(route('api.v1.sections.access-grants.store', $section), ['user_id' => $instructor->id])
        ->assertForbidden();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->postJson(route('api.v1.sections.access-grants.store', $section), ['user_id' => $instructor->id])
        ->assertCreated();
});
