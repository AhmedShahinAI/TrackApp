<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| T160 — allow and deny for the three audit endpoints (constitution VIII)
|--------------------------------------------------------------------------
|
| `GET /audit-logs`, `GET /audit-logs/export` and `GET /{entity}/{id}/activity`.
|
| The activity endpoint needs two permissions, not one, and the pair is the point: `audit.view`
| alone would let someone read the history of a record they are not allowed to open, and the
| entity's `*.view` alone would turn every detail page into an audit log. Both denials are asserted
| separately, because an `&&` written as an `||` passes any test that only checks the happy path and
| one denial.
|
*/

/**
 * A role holding exactly the permissions named, and a user wearing it.
 *
 * @param  list<string>  $permissions
 */
function aUserHolding(array $permissions, string $roleName = 'PROBE'): App\Models\User
{
    $user = aUser('INSTRUCTOR');

    $role = Role::findOrCreate($roleName, 'web');
    $role->syncPermissions([...$permissions, 'dashboard.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles([$roleName]);

    return $user->fresh();
}

test('api.v1.audit-logs.index — allow and deny', function (): void {
    test()->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.audit-logs.index'))->assertOk();
    test()->actingAs(aUserHolding([]))->getJson(route('api.v1.audit-logs.index'))->assertForbidden();
});

test('api.v1.audit-logs.export — allow and deny, on its own permission', function (): void {
    test()->actingAs(aUser('SUPER_ADMIN'))->get(route('api.v1.audit-logs.export'))->assertOk();

    /*
     * Holding audit.view is not enough. Reading the trail and walking out with a copy of it are
     * different authorities — which is why a coordinator has the first and not the second.
     */
    test()->actingAs(aUserHolding(['audit.view']))
        ->get(route('api.v1.audit-logs.export'))
        ->assertForbidden();
});

test('api.v1.entity-activity — allow for each of the four entities', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $term = AcademicTerm::query()->first() ?? AcademicTerm::factory()->create();
    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);
    $section = Section::factory()->create();
    $student = Student::factory()->create();

    foreach ([
        ['terms', $term->id],
        ['branches', $branch->id],
        ['sections', $section->id],
        ['students', $student->id],
    ] as [$entity, $id]) {
        test()->actingAs($admin)
            ->getJson(route('api.v1.entity-activity', ['entity' => $entity, 'id' => $id]))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);
    }
});

test('api.v1.entity-activity — refused without audit.view, even with the entity permission', function (): void {
    $section = Section::factory()->create();

    // section.view but no audit.view: they may open the record and not read its history.
    test()->actingAs(aUserHolding(['section.view', 'scope.bypass_all']))
        ->getJson(route('api.v1.entity-activity', ['entity' => 'sections', 'id' => $section->id]))
        ->assertForbidden();
});

test('api.v1.entity-activity — refused without the entity permission, even with audit.view', function (): void {
    $student = Student::factory()->create();

    // audit.view but no student.view: they may read the audit log and not this record's history.
    test()->actingAs(aUserHolding(['audit.view', 'scope.bypass_all']))
        ->getJson(route('api.v1.entity-activity', ['entity' => 'students', 'id' => $student->id]))
        ->assertForbidden();
});

test('api.v1.entity-activity — an out-of-scope id is 404, not 403', function (): void {
    $organisation = anOrganisation();

    $mine = Branch::factory()->create(['organization_id' => $organisation->id]);
    $theirs = Branch::factory()->create(['organization_id' => $organisation->id]);

    $foreign = Section::factory()->create(['branch_id' => $theirs->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$mine->id]);
    $coordinator->forgetScopeCache();

    /*
     * FR-033 — "the activity view cannot be used to probe for records the caller cannot otherwise
     * reach". Without the scoped lookup this endpoint would be a cheap existence oracle for every
     * id in the college: 404 for absent, 200-with-empty-list for present-but-invisible.
     */
    test()->actingAs($coordinator)
        ->getJson(route('api.v1.entity-activity', ['entity' => 'sections', 'id' => $foreign->id]))
        ->assertNotFound();
});

test('api.v1.entity-activity — an unknown entity name is 404, not a hint', function (): void {
    test()->actingAs(aUser('SUPER_ADMIN'))
        ->getJson('/api/v1/initiatives/1/activity')
        ->assertNotFound();
});

test('api.v1.entity-activity — returns only that entity\'s entries', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $one = Section::factory()->create();
    $other = Section::factory()->create();

    DB::table('audit_logs')->delete();

    foreach ([[$one->id, 2], [$other->id, 3]] as [$id, $times]) {
        for ($i = 0; $i < $times; $i++) {
            DB::table('audit_logs')->insert([
                'user_id' => $admin->id, 'action' => 'section.updated',
                'entity_type' => 'Section', 'entity_id' => $id,
                'ip_address' => '10.0.0.1', 'created_at' => now(),
            ]);
        }
    }

    test()->actingAs($admin)
        ->getJson(route('api.v1.entity-activity', ['entity' => 'sections', 'id' => $one->id]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

test('an unauthenticated request reaches none of the three', function (): void {
    $section = Section::factory()->create();

    test()->getJson(route('api.v1.audit-logs.index'))->assertUnauthorized();
    test()->getJson(route('api.v1.audit-logs.export'))->assertUnauthorized();
    test()->getJson(route('api.v1.entity-activity', ['entity' => 'sections', 'id' => $section->id]))
        ->assertUnauthorized();
});
