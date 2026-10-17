<?php

declare(strict_types=1);

namespace App\Modules\Imports\Policies;

use App\Models\ImportJob;
use App\Models\Section;
use App\Models\User;
use App\Modules\Imports\Modes\SectionMode;

/**
 * T237 — who may see an import, and who may drive one.
 *
 * ## Seeing is scoped; driving is owned
 *
 * Reading a job — its status, its counts, its error report — needs `import.view`, and *which* jobs a
 * caller can reach is already settled by ImportJob's global scope before this policy runs: an
 * out-of-scope id is a 404 from the route binding (FR-024). A coordinator can therefore follow an
 * import into their own branch's sections, and a Super Admin can follow every one.
 *
 * Driving a job — mapping it, validating it, confirming its commit, resuming it — needs the run
 * permission for the job's mode **and** ownership. contracts/imports.md says "the caller must own the
 * job" for every step, and the reason is accountability: `import.committed` names the person who
 * confirmed the preview, and that must be the person who looked at it. A second administrator who
 * could press Commit on someone else's preview would be confirming a decision they never saw.
 */
final class ImportJobPolicy
{
    /** contracts/imports.md, the mode table. */
    public const RUN_PERMISSIONS = [
        'central' => 'import.central.run',
        'section' => 'import.section.run',
        'registrar' => 'import.registrar.run',
        'course' => 'import.course.run',
        'user' => 'import.user.run',
    ];

    public function viewAny(User $actor): bool
    {
        return $actor->can('import.view');
    }

    public function view(User $actor, ImportJob $job): bool
    {
        return $actor->can('import.view');
    }

    /** The central upload — the only mode this feature's upload endpoint opens. */
    public function create(User $actor): bool
    {
        return $actor->can('import.central.run');
    }

    public function run(User $actor, ImportJob $job): bool
    {
        $permission = self::RUN_PERMISSIONS[$job->mode] ?? null;

        if ($permission === null || ! $actor->can($permission) || (int) $job->user_id !== (int) $actor->getKey()) {
            return false;
        }

        /*
         * SEC-055 (G-22 review). The central and registrar modes resolve sections in ANY branch of
         * the organization and write enrollments into them. That was safe only because the seeded
         * roles give these permissions to the Super Admin alone; a role added later (FR-020: roles are
         * data) holding them without scope.bypass_all would have imported into branches it cannot
         * see. So the modes that cross branches require the bypass itself, not just the permission.
         */
        if (in_array($job->mode, ['central', 'registrar'], true) && ! $actor->can('scope.bypass_all')) {
            return false;
        }

        /*
         * A section job is driven only while its owner may still import into that section.
         *
         * Ownership alone let every later step through after the section was reassigned or the grant
         * withdrawn — the job stays visible to a coordinator who also teaches, through their branch
         * scope — and the refusal happened only inside the queued chunk, after a 202. The section is
         * loaded unscoped because the question is about this section, not the caller's view of it;
         * a section that no longer exists refuses.
         */
        if ($job->mode === SectionMode::NAME) {
            $section = Section::query()->withoutGlobalScopes()->find($job->section_id);

            return $section !== null && $actor->can('importRoster', $section);
        }

        return true;
    }

    public function downloadErrors(User $actor, ImportJob $job): bool
    {
        return $this->view($actor, $job);
    }
}
