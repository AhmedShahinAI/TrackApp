<?php

declare(strict_types=1);

namespace App\Modules\Imports\Actions;

use App\Models\ImportJob;
use App\Modules\Imports\Jobs\ValidateImportJob;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T229 — step 3's trigger: move a mapped job to `validating` and queue the work (FR-063).
 *
 * Its own class because two endpoints start it — `POST /imports/{id}/validate`, and the mapping
 * form, which for a browser confirms the mapping and starts checking in one click as the reference
 * does. Two copies of "reset the counters, stamp the queueing, dispatch" would drift the first time
 * one of them gained a step.
 *
 * The dispatch happens after the state change is saved and outside any transaction. Under the sync
 * driver the job runs inside dispatch(), and a job that starts before its own `validating` status is
 * committed reads the old status and returns without doing anything.
 */
final class StartValidation
{
    public function execute(ImportJob $job): ImportJob
    {
        if (! $job->canBeValidated()) {
            throw new ConflictHttpException(__('imports.conflicts.not_mapped'));
        }

        // A compare-and-set, so two concurrent requests cannot both queue a validation.
        $claimed = ImportJob::query()->withoutGlobalScopes()
            ->whereKey($job->id)
            ->where('status', 'mapped')
            ->update([
                'status' => 'validating',
                'queued_at' => now(),
                'heartbeat_at' => null,
                'processed' => 0,
                'errors' => 0,
                'summary' => null,
                'preview_fetched_at' => null,
                'failure_reason' => null,
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            throw new ConflictHttpException(__('imports.conflicts.not_mapped'));
        }

        ValidateImportJob::dispatch($job->id);

        return $job->refresh();
    }
}
