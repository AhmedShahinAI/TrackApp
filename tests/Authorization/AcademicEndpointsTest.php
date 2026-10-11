<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| T180 — allow and deny for every academic endpoint (constitution VIII)
|--------------------------------------------------------------------------
|
| contracts/academic.md defines eighteen. They are added here as they are implemented, because
| EveryRouteAuthorizedTest fails the build for a route no authorization test names — which is what
| keeps this file honest rather than aspirational.
|
| Each endpoint gets both halves. A test that only drives the happy path passes just as well
| against a route with no permission check at all.
|
*/

/**
 * A user holding exactly these permissions and nothing else.
 *
 * @param  list<string>  $permissions
 */
function aHolderOf(array $permissions, string $roleName = 'ACADEMIC_PROBE'): App\Models\User
{
    $user = aUser('INSTRUCTOR');

    $role = Role::findOrCreate($roleName, 'web');
    $role->syncPermissions([...$permissions, 'dashboard.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles([$roleName]);

    return $user->fresh();
}

/**
 * @return array<string, mixed>
 */
function validTermPayload(): array
{
    return [
        'name_ar' => 'الفصل الأول 1449',
        'name_en' => 'First term 1449',
        'academic_year' => '2027/2028',
        'semester' => 'first',
        'start_date' => '2027-08-22',
        'end_date' => '2027-12-16',
    ];
}

test('api.v1.terms.index — allow and deny', function (): void {
    test()->actingAs(aHolderOf(['term.view']))->getJson(route('api.v1.terms.index'))->assertOk();
    test()->actingAs(aHolderOf([]))->getJson(route('api.v1.terms.index'))->assertForbidden();
});

test('api.v1.terms.store — allow and deny', function (): void {
    test()->actingAs(aHolderOf(['term.create']))
        ->postJson(route('api.v1.terms.store'), validTermPayload())
        ->assertCreated();

    // term.view is not term.create: reading the calendar is not setting it.
    test()->actingAs(aHolderOf(['term.view']))
        ->postJson(route('api.v1.terms.store'), validTermPayload())
        ->assertForbidden();
});

test('api.v1.terms.update — allow and deny', function (): void {
    $term = AcademicTerm::factory()->create();

    test()->actingAs(aHolderOf(['term.edit']))
        ->putJson(route('api.v1.terms.update', $term), ['name_ar' => 'اسم معدّل'])
        ->assertOk();

    test()->actingAs(aHolderOf(['term.view']))
        ->putJson(route('api.v1.terms.update', $term), ['name_ar' => 'محاولة'])
        ->assertForbidden();
});

test('api.v1.terms.status — allow and deny', function (): void {
    $term = AcademicTerm::factory()->create(['status' => 'draft']);

    test()->actingAs(aHolderOf(['term.change_status']))
        ->patchJson(route('api.v1.terms.status', $term), ['status' => 'active'])
        ->assertOk();

    /*
     * term.edit is not term.change_status. Correcting a typo in a term's name and declaring the
     * term closed are different authorities, which is why they are different endpoints.
     */
    test()->actingAs(aHolderOf(['term.edit']))
        ->patchJson(route('api.v1.terms.status', $term), ['status' => 'draft'])
        ->assertForbidden();
});

test('api.v1.terms.status — reopening a closed term needs term.edit_closed on top', function (): void {
    $term = AcademicTerm::factory()->create(['status' => 'closed']);

    // The route's own permission is not enough for this one transition.
    test()->actingAs(aHolderOf(['term.change_status']))
        ->patchJson(route('api.v1.terms.status', $term), ['status' => 'active', 'reason' => 'محاولة'])
        ->assertForbidden();

    test()->actingAs(aHolderOf(['term.change_status', 'term.edit_closed']))
        ->patchJson(route('api.v1.terms.status', $term), ['status' => 'active', 'reason' => 'تصحيح درجة'])
        ->assertOk();
});

test('api.v1.terms.set-current — allow and deny', function (): void {
    $term = AcademicTerm::factory()->create(['is_current' => false]);

    test()->actingAs(aHolderOf(['term.set_current']))
        ->patchJson(route('api.v1.terms.set-current', $term))
        ->assertOk();

    test()->actingAs(aHolderOf(['term.change_status']))
        ->patchJson(route('api.v1.terms.set-current', $term))
        ->assertForbidden();
});

test('none of the term endpoints answer an unauthenticated caller', function (): void {
    $term = AcademicTerm::factory()->create();

    test()->getJson(route('api.v1.terms.index'))->assertUnauthorized();
    test()->postJson(route('api.v1.terms.store'), validTermPayload())->assertUnauthorized();
    test()->putJson(route('api.v1.terms.update', $term), [])->assertUnauthorized();
    test()->patchJson(route('api.v1.terms.status', $term), ['status' => 'active'])->assertUnauthorized();
    test()->patchJson(route('api.v1.terms.set-current', $term))->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| Branches and courses
|--------------------------------------------------------------------------
*/

test('api.v1.branches.index — allow and deny', function (): void {
    test()->actingAs(aHolderOf(['branch.view', 'scope.bypass_all']))
        ->getJson(route('api.v1.branches.index'))->assertOk();

    test()->actingAs(aHolderOf([]))->getJson(route('api.v1.branches.index'))->assertForbidden();
});

test('api.v1.branches.index — a coordinator sees only their own campuses', function (): void {
    $organisation = anOrganisation();

    $mine = App\Models\Branch::factory()->create(['organization_id' => $organisation->id]);
    $theirs = App\Models\Branch::factory()->create(['organization_id' => $organisation->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$mine->id]);
    $coordinator->forgetScopeCache();

    /*
     * Branch deliberately carries no global scope — a scope on it would be consulted while
     * resolving coordinatorBranchIds(), which reads the branches table, and the scope would call the
     * method that calls the scope. So the constraint lives in the controller, and this is what holds
     * it there.
     */
    $ids = collect((array) test()->actingAs($coordinator)
        ->getJson(route('api.v1.branches.index'))->assertOk()->json('data'))->pluck('id');

    expect($ids)->toContain($mine->id)
        ->and($ids)->not->toContain($theirs->id);
});

test('api.v1.branches.store — allow and deny', function (): void {
    $payload = ['campus_name_ar' => 'حوطة سدير', 'campus_name_en' => 'Hawtat Sudair', 'gender' => 'male'];

    test()->actingAs(aHolderOf(['branch.create']))
        ->postJson(route('api.v1.branches.store'), $payload)
        ->assertCreated();

    test()->actingAs(aHolderOf(['branch.view']))
        ->postJson(route('api.v1.branches.store'), $payload)
        ->assertForbidden();
});

test('api.v1.branches.update — allow and deny', function (): void {
    $branch = App\Models\Branch::factory()->create(['organization_id' => anOrganisation()->id]);

    test()->actingAs(aHolderOf(['branch.edit', 'scope.bypass_all']))
        ->putJson(route('api.v1.branches.update', $branch), ['campus_name_ar' => 'اسم معدّل'])
        ->assertOk();

    test()->actingAs(aHolderOf(['branch.view', 'scope.bypass_all']))
        ->putJson(route('api.v1.branches.update', $branch), ['campus_name_ar' => 'محاولة'])
        ->assertForbidden();
});

test('deactivating a branch records branch.deactivated, exactly once', function (): void {
    $branch = App\Models\Branch::factory()->create([
        'organization_id' => anOrganisation()->id,
        'is_active' => true,
    ]);

    Illuminate\Support\Facades\DB::table('audit_logs')->delete();

    test()->actingAs(aHolderOf(['branch.edit', 'scope.bypass_all']))
        ->putJson(route('api.v1.branches.update', $branch), ['is_active' => false])
        ->assertOk();

    $actions = Illuminate\Support\Facades\DB::table('audit_logs')
        ->where('entity_type', 'Branch')->pluck('action')->all();

    /*
     * The contract: "branch.updated, or branch.deactivated when is_active flips to false". Letting
     * the Auditable trait also fire would write both — true but useless, because the question asked
     * later is when the campus went out of service, and two entries make the search ambiguous.
     */
    expect($actions)->toBe(['branch.deactivated']);

    expect($branch->fresh()->is_active)->toBeFalse()
        ->and($branch->fresh()->exists)->toBeTrue();
});

test('an ordinary edit records branch.updated, not deactivated', function (): void {
    $branch = App\Models\Branch::factory()->create([
        'organization_id' => anOrganisation()->id,
        'is_active' => true,
    ]);

    Illuminate\Support\Facades\DB::table('audit_logs')->delete();

    test()->actingAs(aHolderOf(['branch.edit', 'scope.bypass_all']))
        ->putJson(route('api.v1.branches.update', $branch), ['campus_name_ar' => 'الزلفي'])
        ->assertOk();

    expect(Illuminate\Support\Facades\DB::table('audit_logs')
        ->where('entity_type', 'Branch')->pluck('action')->all())->toBe(['branch.updated']);
});

test('api.v1.courses.index — allow and deny', function (): void {
    test()->actingAs(aHolderOf(['course.view']))->getJson(route('api.v1.courses.index'))->assertOk();
    test()->actingAs(aHolderOf([]))->getJson(route('api.v1.courses.index'))->assertForbidden();
});

test('api.v1.courses.store — allow and deny', function (): void {
    $payload = ['code' => 'PHYS101', 'name_ar' => 'فيزياء عامة', 'name_en' => 'General physics'];

    test()->actingAs(aHolderOf(['course.create']))
        ->postJson(route('api.v1.courses.store'), $payload)
        ->assertCreated();

    test()->actingAs(aHolderOf(['course.view']))
        ->postJson(route('api.v1.courses.store'), $payload)
        ->assertForbidden();
});

test('a duplicate course code is 422 with a message naming the conflict', function (): void {
    $payload = ['code' => 'MATH101', 'name_ar' => 'رياضيات', 'name_en' => 'Mathematics'];

    test()->actingAs(aHolderOf(['course.create']))
        ->postJson(route('api.v1.courses.store'), $payload)
        ->assertCreated();

    $response = test()->actingAs(aHolderOf(['course.create']))
        ->postJson(route('api.v1.courses.store'), $payload)
        ->assertStatus(422);

    app()->setLocale('ar');
    expect($response->json('errors.code.0'))->toBe(__('courses.errors.code_taken'));
});

test('neither branches nor courses answer an unauthenticated caller', function (): void {
    test()->getJson(route('api.v1.branches.index'))->assertUnauthorized();
    test()->getJson(route('api.v1.courses.index'))->assertUnauthorized();
});
