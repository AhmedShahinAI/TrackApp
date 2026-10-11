<?php

declare(strict_types=1);

namespace App\Modules\Imports\Notifications;

use App\Models\ImportJob;
use Illuminate\Notifications\Notification;

/**
 * T238 — a stored record that a queued import step finished (FR-072).
 *
 * ## Database only, by design
 *
 * In this feature the operator learns the outcome from the wizard's own poller and report view; no
 * email is sent and there is no SMTP dependency (contracts/imports.md, "Notification"). The row is
 * stored anyway so the notification-centre feature that follows inherits every import's history from
 * day one, instead of starting empty the day it ships.
 *
 * ## Facts, not sentences
 *
 * The payload carries the stage, the file and the counts — never a rendered message. The centre will
 * render it in the reader's language at the time they read it, which is the same reason the import
 * rows store reason keys (ImportReasons).
 */
final class ImportFinished extends Notification
{
    /**
     * @param  'validated'|'completed'|'failed'  $stage
     */
    public function __construct(
        private readonly ImportJob $job,
        private readonly string $stage,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'import_job_id' => $this->job->id,
            'stage' => $this->stage,
            'mode' => $this->job->mode,
            'original_filename' => $this->job->original_filename,
            'status' => $this->job->status,
            'total_rows' => $this->job->total_rows,
            'processed' => $this->job->processed,
            'imported' => $this->job->imported,
            'duplicates' => $this->job->duplicates,
            'errors' => $this->job->errors,
        ];
    }
}
