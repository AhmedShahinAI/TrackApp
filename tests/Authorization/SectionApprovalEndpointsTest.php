<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T483 — allow and deny for the four section approval routes (constitution VIII)
|--------------------------------------------------------------------------
|
| Each route by name, so EveryRouteAuthorizedTest finds it. Plus the two refusals the contract makes a
| point of: a section outside the caller's scope is a 404 (it is not disclosed), and an instructor has no
| queue and no decision.
|
*/

/** A user holding `dashboard.view` and nothing else; this file's own, so it runs on its own. */
function approvalPermissionless(): App\Models\User
{
    $user = aUser('INSTRUCTOR');
    $role = Spatie\Permission\Models\Role::findOrCreate('NO_PERMISSIONS', 'web');
    $role->syncPermissions(['dashboard.view']);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $user->syncRoles(['NO_PERMISSIONS']);

    return $user->fresh();
}

test('api.v1.sections.approvals — allow for a deciding role, deny for an instructor and a permissionless user', function (): void {
    $world = approvalWorld();

    foreach (['admin', 'coordinator'] as $who) {
        app('auth')->forgetGuards();
        $this->actingAs($world[$who])->getJson(route('api.v1.sections.approvals'))->assertOk();
    }

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->getJson(route('api.v1.sections.approvals'))->assertForbidden();
    app('auth')->forgetGuards();
    $this->actingAs(approvalPermissionless())->getJson(route('api.v1.sections.approvals'))->assertForbidden();
});

test('sections.approvals (the screen) — allow for a coordinator', function (): void {
    $world = approvalWorld();

    $this->actingAs($world['coordinator'], 'web')->get(route('sections.approvals'))->assertOk();
});

test('sections.approvals (the screen) — deny for an instructor', function (): void {
    $world = approvalWorld();

    $this->actingAs($world['instructor'], 'web')->get(route('sections.approvals'))->assertForbidden();
});

test('api.v1.sections.approval — allow for the branch coordinator, deny for an instructor', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->patchJson(route('api.v1.sections.approval', $section), ['decision' => 'approve'])->assertForbidden();

    app('auth')->forgetGuards();
    $this->actingAs($world['coordinator'])->patchJson(route('api.v1.sections.approval', $section), ['decision' => 'approve'])->assertOk();
});

test('api.v1.sections.approval — a section in another branch is a 404, not a 403', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    // The other branch's coordinator holds section.approve but cannot see this section at all.
    app('auth')->forgetGuards();
    $this->actingAs($world['outsider'])->patchJson(route('api.v1.sections.approval', $section), ['decision' => 'approve'])->assertNotFound();

    expect($section->refresh()->status)->toBe('pending');
});

test('api.v1.sections.resubmit — allow for the creator, deny for anyone else', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);
    decideSection($world['coordinator'], $section, 'return', 'Wrong programme for this course.')->assertOk();

    app('auth')->forgetGuards();
    $this->actingAs($world['admin'])->patchJson(route('api.v1.sections.resubmit', $section), [])->assertForbidden();
    app('auth')->forgetGuards();
    // Binding runs before `can:`, so a caller who cannot see the section is a 404: scope before policy, and
    // its existence is never disclosed.
    $this->actingAs(approvalPermissionless())->patchJson(route('api.v1.sections.resubmit', $section), [])->assertNotFound();

    app('auth')->forgetGuards();
    $world['instructor']->forgetScopeCache();
    $this->actingAs($world['instructor'])->patchJson(route('api.v1.sections.resubmit', $section), [])->assertOk();
});

test('api.v1.sections.approval-history — allow for someone who sees the section, 404 for someone who does not', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    app('auth')->forgetGuards();
    $this->actingAs($world['coordinator'])->getJson(route('api.v1.sections.approval-history', $section))->assertOk()
        ->assertJsonPath('data.0.to_status', 'pending');

    app('auth')->forgetGuards();
    $this->actingAs($world['outsider'])->getJson(route('api.v1.sections.approval-history', $section))->assertNotFound();

    app('auth')->forgetGuards();
    // Binding runs before `can:`, so a caller who cannot see the section is a 404: scope before policy, and
    // its existence is never disclosed.
    $this->actingAs(approvalPermissionless())->getJson(route('api.v1.sections.approval-history', $section))->assertNotFound();
});
