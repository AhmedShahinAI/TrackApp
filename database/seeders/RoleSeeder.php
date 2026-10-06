<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * T046 + T473 - the three seeded roles (FR-020).
 *
 * A fourth role is a row in this file and nothing else: no policy, no screen, no migration. That is
 * the whole point of permissions being named and roles being subsets of them.
 *
 * `scope.bypass_all` is what makes the Super Admin unscoped, rather than any code branching on the
 * role name. The global scopes in research.md R-01 check for that permission, so a future role that
 * needs college-wide sight gets it by holding the permission.
 */
class RoleSeeder extends Seeder
{
    /**
     * The coordinator's set. Read-only across the academic structure plus the audit log, with two
     * exceptions added by Amendment 4: they may create a section (which then needs approval) and
     * approve an instructor's section in their own branches.
     *
     * @var list<string>
     */
    private const BRANCH_COORDINATOR = [
        'dashboard.view',
        'term.view', 'branch.view', 'course.view', 'program.view', 'section.view', 'student.view',
        'import.view', 'audit.view',
        'section.create', 'section.approve',
        'support.view', 'support.respond',
    ];

    /**
     * The instructor's set. Write access is confined to their own roster: they add a student, enroll
     * one, withdraw one with a reason, and import a section list. They hold none of the four support
     * permissions (FR-142) and cannot approve anything.
     *
     * @var list<string>
     */
    private const INSTRUCTOR = [
        'dashboard.view',
        'section.view', 'student.view', 'student.create',
        'enrollment.create', 'enrollment.withdraw',
        'section.create',
        'import.section.run', 'import.view',
    ];

    public function run(): void
    {
        $superAdmin = Role::findOrCreate('SUPER_ADMIN', 'web');
        $coordinator = Role::findOrCreate('BRANCH_COORDINATOR', 'web');
        $instructor = Role::findOrCreate('INSTRUCTOR', 'web');

        $superAdmin->syncPermissions(PermissionSeeder::PERMISSIONS);
        $coordinator->syncPermissions(self::BRANCH_COORDINATOR);
        $instructor->syncPermissions(self::INSTRUCTOR);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
