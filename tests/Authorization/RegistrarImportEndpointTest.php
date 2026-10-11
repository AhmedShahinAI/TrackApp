<?php

declare(strict_types=1);

use App\Models\ImportJob;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| T327 — allow and deny for POST /imports/registrar (contracts/imports.md)
|--------------------------------------------------------------------------
|
| The deny case holds the NEIGHBOURING permission, `import.central.run`: the central import and the
| registrar import are separate permissions on purpose, and a route that checked the wrong one would
| pass a deny test that only tried an empty-handed caller.
|
*/

/**
 * A registrar world's caller holding exactly these permissions.
 *
 * @param  list<string>  $permissions
 */
function aRegistrarCaller(array $permissions): App\Models\User
{
    $user = aUser('INSTRUCTOR');
    $role = Role::findOrCreate('REGISTRAR_PROBE_'.md5(implode(',', $permissions)), 'web');
    $role->syncPermissions([...$permissions, 'dashboard.view', 'import.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->syncRoles([$role->name]);

    return $user->fresh();
}

test('api.v1.imports.registrar.store — allow and deny', function (): void {
    $world = registrarWorld();

    postRegistrar(['admin' => aRegistrarCaller(['import.registrar.run'])] + $world, registrarFile(rows: registrarRows(1)))
        ->assertCreated();

    postRegistrar(['admin' => aRegistrarCaller(['import.central.run', 'import.section.run'])] + $world, registrarFile(rows: registrarRows(1)))
        ->assertForbidden();

    expect(ImportJob::query()->withoutGlobalScopes()->count())->toBe(1);
});

test('api.v1.imports.registrar.store — the seeded roles: only a Super Admin', function (string $role, int $status): void {
    $world = registrarWorld();

    postRegistrar(['admin' => aUser($role)] + $world, registrarFile(rows: registrarRows(1)))->assertStatus($status);
})->with([
    'super admin' => ['SUPER_ADMIN', 201],
    'coordinator' => ['BRANCH_COORDINATOR', 403],
    'instructor' => ['INSTRUCTOR', 403],
]);

test('api.v1.imports.registrar.store — unauthenticated is refused', function (): void {
    $this->post(route('api.v1.imports.registrar.store'), [], ['Accept' => 'application/json'])->assertUnauthorized();
});

test('the term and the instructor are required, and the instructor must be an active instructor of the college', function (): void {
    $world = registrarWorld();
    $coordinator = aUser('BRANCH_COORDINATOR');
    $foreign = aUser('INSTRUCTOR', ['organization_id' => App\Models\Organization::factory()->create()->id]);
    $inactive = aUser('INSTRUCTOR', ['is_active' => false]);

    postRegistrar($world, registrarFile(rows: registrarRows(1)), ['term_id' => null, 'instructor_id' => null])
        ->assertUnprocessable()->assertJsonValidationErrors(['term_id', 'instructor_id']);

    foreach ([$coordinator, $foreign, $inactive] as $notAnInstructor) {
        postRegistrar($world, registrarFile(rows: registrarRows(1)), ['instructor_id' => $notAnInstructor->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['instructor_id']);
    }
});

test('imports.create.registrar (the screen) — allow', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('imports.create.registrar'))->assertOk();
});

test('imports.create.registrar (the screen) — deny', function (): void {
    $this->actingAs(aUser('BRANCH_COORDINATOR'))->get(route('imports.create.registrar'))->assertForbidden();
});

test('every later step of a registrar job needs import.registrar.run and ownership', function (): void {
    $world = registrarWorld();
    postRegistrar($world, registrarFile(rows: registrarRows(1)))->assertCreated();
    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    // Another Super Admin may follow it, but not run it (ImportJobPolicy::run).
    $this->actingAs(aUser('SUPER_ADMIN'))->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
    ])->assertForbidden();
});
