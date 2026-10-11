<?php

declare(strict_types=1);

use App\Models\ImportJob;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| T341 — allow and deny for POST /imports/courses and POST /imports/users (FR-107)
|--------------------------------------------------------------------------
|
| Each deny holds the OTHER bulk permission: course import and staff import are separate on purpose,
| and a route checking the wrong one would pass a test that only denied an empty-handed caller.
|
*/

/** @param  list<string>  $permissions */
function aBulkCaller(array $permissions): App\Models\User
{
    $user = aUser('INSTRUCTOR');
    $role = Role::findOrCreate('BULK_PROBE_'.md5(implode(',', $permissions)), 'web');
    $role->syncPermissions([...$permissions, 'dashboard.view', 'import.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->syncRoles([$role->name]);

    return $user->fresh();
}

test('api.v1.imports.courses.store — allow and deny', function (): void {
    postBulk(aBulkCaller(['import.course.run']), 'courses', courseFile([]))->assertCreated();
    postBulk(aBulkCaller(['import.user.run', 'import.central.run']), 'courses', courseFile([]))->assertForbidden();
});

test('api.v1.imports.users.store — allow and deny', function (): void {
    postBulk(aBulkCaller(['import.user.run']), 'users', staffFile([]))->assertCreated();
    postBulk(aBulkCaller(['import.course.run', 'import.central.run']), 'users', staffFile([]))->assertForbidden();
});

test('an instructor is refused both, and the attempt is recorded (spec scenario 6)', function (string $mode): void {
    $instructor = aUser('INSTRUCTOR');
    DB::table('audit_logs')->delete();

    postBulk($instructor, $mode, $mode === 'courses' ? courseFile([]) : staffFile([]))->assertForbidden();

    expect(DB::table('audit_logs')->where('action', 'access.denied')->where('user_id', $instructor->id)->count())->toBe(1)
        ->and(ImportJob::query()->withoutGlobalScopes()->count())->toBe(0);
})->with(['courses', 'users']);

test('a coordinator is refused both; a Super Admin may run both', function (string $mode): void {
    $file = fn () => $mode === 'courses' ? courseFile([]) : staffFile([]);

    postBulk(aUser('BRANCH_COORDINATOR'), $mode, $file())->assertForbidden();
    postBulk(aUser('SUPER_ADMIN'), $mode, $file())->assertCreated();
})->with(['courses', 'users']);

test('unauthenticated callers are refused', function (string $mode): void {
    $this->post(route('api.v1.imports.'.$mode.'.store'), [], ['Accept' => 'application/json'])->assertUnauthorized();
})->with(['courses', 'users']);

test('imports.create.courses and imports.create.users (the screens) — allow', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->get(route('imports.create.courses'))->assertOk()->assertSee(route('imports.courses.template'), false);
    $this->actingAs($admin)->get(route('imports.create.users'))->assertOk()->assertSee(route('imports.users.template'), false);
});

test('imports.create.courses — deny', function (): void {
    $this->actingAs(aUser('INSTRUCTOR'))->get(route('imports.create.courses'))->assertForbidden();
});

test('imports.create.users — deny', function (): void {
    $this->actingAs(aUser('INSTRUCTOR'))->get(route('imports.create.users'))->assertForbidden();
});

test('a later step of a bulk job needs its own permission and ownership', function (): void {
    $owner = aUser('SUPER_ADMIN');
    postBulk($owner, 'courses', courseFile([]))->assertCreated();
    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    // Another Super Admin may follow it, but not run it (ImportJobPolicy::run).
    $this->actingAs(aUser('SUPER_ADMIN'))->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
    ])->assertForbidden();
});
