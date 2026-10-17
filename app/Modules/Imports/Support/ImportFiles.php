<?php

declare(strict_types=1);

namespace App\Modules\Imports\Support;

use App\Models\ImportJob;
use App\Modules\Audit\Services\Auditor;
use Illuminate\Support\Facades\Storage;

/**
 * SEC-062, SEC-063 (G-05) — an uploaded roster file is deleted once nothing needs it.
 *
 * The file is read only while the import is being validated (ValidateImportJob re-reads it when the
 * mapping changes); committing, resuming and the error report all work from `import_rows`. So the
 * file of a completed import, and of one that finished or failed long ago, is personal data kept
 * for no purpose — PDPL's retention principle, and the reason it was found on disk 10,440 times.
 *
 * Every deletion is audited as a system entry with the job it belonged to, and never with the
 * file's contents.
 */
final class ImportFiles
{
    public const DISK = 'private';

    /** Delete the job's stored file, if it is still there. True when a file was deleted. */
    public static function discard(ImportJob $job, string $reason): bool
    {
        $disk = Storage::disk(self::DISK);

        if ($job->file_path === '' || ! $disk->exists($job->file_path)) {
            return false;
        }

        $disk->delete($job->file_path);

        app(Auditor::class)->record('import.file_discarded', $job, [], [
            'reason' => $reason,
            'channel' => 'system',
        ]);

        return true;
    }
}
