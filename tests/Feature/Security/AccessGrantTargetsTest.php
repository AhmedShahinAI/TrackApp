<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Section;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| SEC-050 … SEC-052 — whoever is handed access must be someone who may hold it (security plan)
|--------------------------------------------------------------------------
|
| G-08: changing a section's instructor accepted any user id that existed.
| G-14: creating a section let an instructor name any active account as its instructor.
| G-15: the role, branch and section-grant endpoints did not check the target's organization.
|
| Each hands someone access to students. The checks below are the target's, not the caller's: the
| caller's permission is already proven by `can:`.
|
*/

require_once __DIR__.'/../../Support/SectionApproval.php';

function anotherOrganisation(): Organization
{
    return Organization::factory()->create(['type' => 'college']);
}

// ---- G-08: PATCH /sections/{id}/instructor ----

test('a section cannot be handed to a deactivated instructor (G-08)', function (): void {
    $world = approvalWorld();
    $section = Section::factory()->create(['branch_id' => $world['branch']->id, 'term_id' => $world['term']->id]);
    $inactive = aUser('INSTRUCTOR', ['is_active' => false]);

    $this->actingAs($world['admin'])->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $inactive->id])
        ->assertUnprocessable()->assertJsonValidationErrors('instructor_id');

    expect($section->fresh()->instructor_id)->not->toBe($inactive->id);
});

test('a section cannot be handed to someone who does not teach (G-08)', function (): void {
    $world = approvalWorld();
    $section = Section::factory()->create(['branch_id' => $world['branch']->id, 'term_id' => $world['term']->id]);

    $this->actingAs($world['admin'])->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $world['coordinator']->id])
        ->assertUnprocessable()->assertJsonValidationErrors('instructor_id');
});

test('a section cannot be handed to another organization\'s instructor (G-08)', function (): void {
    $world = approvalWorld();
    $section = Section::factory()->create(['branch_id' => $world['branch']->id, 'term_id' => $world['term']->id]);
    $foreign = aUser('INSTRUCTOR', ['organization_id' => anotherOrganisation()->id]);

    $this->actingAs($world['admin'])->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $foreign->id])
        ->assertUnprocessable()->assertJsonValidationErrors('instructor_id');
});

test('an active instructor of the organization is still accepted (G-08)', function (): void {
    $world = approvalWorld();
    $section = Section::factory()->create(['branch_id' => $world['branch']->id, 'term_id' => $world['term']->id]);
    $next = aUser('INSTRUCTOR');

    $this->actingAs($world['admin'])->patchJson("/api/v1/sections/{$section->id}/instructor", ['instructor_id' => $next->id])
        ->assertOk();

    expect((int) $section->fresh()->instructor_id)->toBe($next->id);
});

// ---- G-14: POST /sections with instructor_id ----

test('an instructor may create a section for a teaching colleague, but not for a coordinator (G-14)', function (): void {
    $world = approvalWorld();

    // Amendment 4: created for someone else, approved by a coordinator before it counts.
    createSectionAs($world['instructor'], $world, ['instructor_id' => aUser('INSTRUCTOR')->id])
        ->assertCreated();

    createSectionAs($world['instructor'], $world, ['instructor_id' => $world['coordinator']->id])
        ->assertUnprocessable()->assertJsonValidationErrors('instructor_id');
});

test('a coordinator may name an instructor, but not a non-teaching account (G-14)', function (): void {
    $world = approvalWorld();

    createSectionAs($world['coordinator'], $world, ['instructor_id' => $world['peer']->id])
        ->assertUnprocessable()->assertJsonValidationErrors('instructor_id');

    createSectionAs($world['coordinator'], $world, ['instructor_id' => $world['instructor']->id])
        ->assertCreated();
});

// ---- G-15: organization checks ----

test('roles cannot be changed on another organization\'s account (G-15)', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $foreign = aUser('INSTRUCTOR', ['organization_id' => anotherOrganisation()->id]);

    $this->actingAs($admin)->putJson("/api/v1/users/{$foreign->id}/roles", ['roles' => ['SUPER_ADMIN']])
        ->assertNotFound();

    expect($foreign->fresh()->hasRole('SUPER_ADMIN'))->toBeFalse();
});

test('branches cannot be assigned on another organization\'s account, nor from another organization (G-15)', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $foreignOrg = anotherOrganisation();
    $foreign = aUser('BRANCH_COORDINATOR', ['organization_id' => $foreignOrg->id]);
    $own = aUser('BRANCH_COORDINATOR');
    $foreignBranch = Branch::factory()->create(['organization_id' => $foreignOrg->id]);

    $this->actingAs($admin)->putJson("/api/v1/users/{$foreign->id}/branches", ['branch_ids' => []])
        ->assertNotFound();

    $this->actingAs($admin)->putJson("/api/v1/users/{$own->id}/branches", ['branch_ids' => [$foreignBranch->id]])
        ->assertUnprocessable()->assertJsonValidationErrors('branch_ids.0');
});

test('section access cannot be granted to another organization\'s account (G-15)', function (): void {
    $world = approvalWorld();
    $section = Section::factory()->create(['branch_id' => $world['branch']->id, 'term_id' => $world['term']->id]);
    $foreign = aUser('INSTRUCTOR', ['organization_id' => anotherOrganisation()->id]);

    $this->actingAs($world['admin'])->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $foreign->id])
        ->assertUnprocessable()->assertJsonValidationErrors('user_id');
});

test('a new account cannot be given another organization\'s branch (G-15)', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $foreignBranch = Branch::factory()->create(['organization_id' => anotherOrganisation()->id]);

    $this->actingAs($admin)->postJson('/api/v1/users', [
        'name_ar' => 'منسق', 'name_en' => 'Coordinator', 'email' => 'cross@mu.edu.sa',
        'password' => 'Track2026-Own', 'role' => 'BRANCH_COORDINATOR', 'branch_ids' => [$foreignBranch->id],
    ])->assertUnprocessable()->assertJsonValidationErrors('branch_ids.0');

    expect(User::query()->where('email', 'cross@mu.edu.sa')->exists())->toBeFalse();
});

// ---- SEC-055: the review of every scope bypass ----

test('a role holding a cross-branch import permission without the bypass cannot drive the import (SEC-055)', function (): void {
    $clerk = aUser('INSTRUCTOR');  // first: it seeds the permissions the role below is given

    $role = Spatie\Permission\Models\Role::findOrCreate('REGISTRAR_CLERK', 'web');
    $role->givePermissionTo(['import.registrar.run', 'import.central.run', 'import.view']);
    $clerk->syncRoles([$role]);

    $term = App\Models\AcademicTerm::query()->first() ?? App\Models\AcademicTerm::factory()->create();

    foreach (['registrar', 'central'] as $mode) {
        $job = App\Models\ImportJob::query()->create([
            'user_id' => $clerk->id, 'mode' => $mode, 'term_id' => $term->id,
            'original_filename' => 'x.xlsx', 'file_path' => 'imports/x.xlsx', 'file_hash' => hash('sha256', $mode), 'status' => 'mapped',
        ]);

        expect($clerk->fresh()->can('run', $job))->toBeFalse();
    }

    expect(aUser('SUPER_ADMIN')->can('scope.bypass_all'))->toBeTrue();
});
