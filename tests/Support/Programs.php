<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Program;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Phase 14 — programmes: fixtures shared by the Programs and Sections suites
|--------------------------------------------------------------------------
*/

/**
 * A user holding exactly these permissions and nothing else, in the default organization.
 *
 * @param  list<string>  $permissions
 */
function aProgramCaller(array $permissions): User
{
    $user = aUser('INSTRUCTOR');

    $role = Role::findOrCreate('PROGRAM_PROBE_'.md5(implode(',', $permissions)), 'web');
    $role->syncPermissions([...$permissions, 'dashboard.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles([$role->name]);

    return $user->fresh();
}

/**
 * A programme of the default organization.
 *
 * @param  array<string, mixed>  $attributes
 */
function aProgram(array $attributes = []): Program
{
    return Program::factory()->create(['organization_id' => anOrganisation()->id] + $attributes);
}

/**
 * Everything a section-create request needs, all in the Super Admin's organization, with a current
 * term so the endpoint reaches validation (TermRequiredFirst answers 409 before it otherwise).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function aValidSectionRequest(array $overrides = []): array
{
    $organization = anOrganisation();

    $term = AcademicTerm::query()->where('is_current', true)->first()
        ?? AcademicTerm::factory()->create(['status' => 'active', 'is_current' => true]);

    return array_merge([
        'term_id' => $term->id,
        'branch_id' => Branch::factory()->create(['organization_id' => $organization->id])->id,
        'course_id' => Course::factory()->create(['organization_id' => $organization->id])->id,
        'program_id' => aProgram()->id,
        'section_number' => (string) fake()->unique()->numberBetween(100, 9999),
        'degree' => 'bachelor',
        'activity' => 'lecture',
    ], $overrides);
}
