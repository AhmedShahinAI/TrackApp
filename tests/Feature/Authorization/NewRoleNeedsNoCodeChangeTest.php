<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| T141 — a fourth role is data, not code (FR-020)
|--------------------------------------------------------------------------
|
| The claim in RoleSeeder's docblock is that "a fourth role is a row in this file and nothing else:
| no policy, no screen, no migration." This test is what makes that claim falsifiable. It creates a
| role the application has never heard of, assigns it a hand-picked permission set, and drives the
| real endpoints with it.
|
| If any of this fails, something in the codebase is branching on a role *name* rather than on a
| permission, and the branch has to be found and removed — not worked around here.
|
*/

beforeEach(function (): void {
    (new PermissionSeeder)->run();
    (new RoleSeeder)->run();
    (new SettingSeeder)->run();
});

test('a role invented at runtime gets exactly the access its permissions describe', function (): void {
    // An auditor: may read the audit log and sections, may change nothing.
    $auditor = Role::findOrCreate('COLLEGE_AUDITOR', 'web');
    $auditor->syncPermissions(['dashboard.view', 'section.view', 'audit.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = aUser('INSTRUCTOR');
    $user->syncRoles(['COLLEGE_AUDITOR']);

    Section::factory()->count(3)->create();

    // Granted by permission, with no code anywhere naming COLLEGE_AUDITOR.
    $this->actingAs($user->fresh())->getJson('/api/v1/audit-logs')->assertOk();
    $this->actingAs($user->fresh())->get('/home')->assertOk();

    // Refused by the absence of a permission, equally without naming the role.
    $this->actingAs($user->fresh())->getJson('/api/v1/users')->assertForbidden();
});

test('a new role carrying scope.bypass_all sees the whole college', function (): void {
    $registrar = Role::findOrCreate('REGISTRAR', 'web');
    $registrar->syncPermissions(['dashboard.view', 'section.view', 'scope.bypass_all']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Section::factory()->count(4)->create(['branch_id' => Branch::factory()]);

    $user = aUser('INSTRUCTOR');
    $user->syncRoles(['REGISTRAR']);

    /*
     * The unscoped view is a permission, not a role name. This is the assertion that keeps it that
     * way: the global scopes check for `scope.bypass_all` and must not check for 'SUPER_ADMIN'.
     */
    $this->actingAs($user->fresh())->getJson('/api/v1/sections')->assertJsonCount(4, 'data');
});

test('no role name appears in a conditional anywhere in the application', function (): void {
    $offenders = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        /*
         * Comments stripped before scanning.
         *
         * The first version read the raw file and flagged BranchScope, whose docblock explains that
         * the Super Admin check is a permission rather than a role-name test — and quotes the shape
         * it must not drift back into. Prose describing the rule is not a violation of it, and a
         * test that cannot tell code from commentary teaches people to stop writing commentary.
         */
        $source = '';

        foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $source .= is_array($token) ? $token[1] : $token;
        }

        /*
         * hasRole('X') and a comparison against a role name are the two shapes that reintroduce
         * role-branching. Seeders and the permission map are allowed to name roles — they are the
         * data. Application code is not.
         */
        if (preg_match('/hasRole\s*\(|->role\s*===|\$role\s*===\s*[\'"](SUPER_ADMIN|BRANCH_COORDINATOR|INSTRUCTOR)/', $source)) {
            $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([]);
});
