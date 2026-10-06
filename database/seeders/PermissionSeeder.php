<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * T045 + T302 + T473 - the platform's permission vocabulary (FR-019).
 *
 * 47 permissions, not the 48 data-model.md Group 8 states. The arithmetic there counts
 * `section.create` as added by Amendment 4, but it is already in the base set and Amendment 4 only
 * regrants it to two more roles; the single genuinely new permission is `section.approve`.
 * 35 base + 7 (Amendment 1) + 4 (Amendment 2) + 1 (Amendment 4) = 47.
 *
 * `program.create` IS seeded. tasks.md T045 excludes it as blocked by C-04, but C-04 was accepted on
 * 2026-09-27 (ui-conflicts.md lines 9 and 420) and programs are now specified as FR-089 to FR-095;
 * the C-12 row that still calls it blocked predates that decision.
 *
 * Every name is `<entity>.<action>`. Adding a role needs no code change (FR-020) because a role is
 * only a named subset of this list.
 */
class PermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const PERMISSIONS = [
        // Accounts
        'user.view', 'user.create', 'user.edit', 'user.deactivate', 'user.reset_password',
        'user.assign_role', 'user.assign_branch',

        // Terms
        'term.view', 'term.create', 'term.edit', 'term.set_current', 'term.change_status',
        'term.edit_closed',

        // Academic structure
        'branch.view', 'branch.create', 'branch.edit',
        'course.view', 'course.create', 'course.edit',

        // Programs (Amendment 1)
        'program.view', 'program.create', 'program.edit',

        // Sections
        'section.view', 'section.create', 'section.edit', 'section.change_instructor',
        'section.access_override',

        // Section approval (Amendment 4)
        'section.approve',

        // Roster
        'student.view', 'student.create', 'student.edit',
        'enrollment.create', 'enrollment.withdraw',

        // Imports
        'import.central.run', 'import.section.run', 'import.registrar.run', 'import.course.run',
        'import.user.run', 'import.view',

        // Audit
        'audit.view', 'audit.export',

        // Student support (Amendment 2)
        'support.view', 'support.respond', 'support.assign', 'support.close',

        // Cross-cutting
        'dashboard.view',
        'scope.bypass_all',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
