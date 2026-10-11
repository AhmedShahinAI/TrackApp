<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * T222 — one line of the downloadable error report (FR-071, PRD 11.4).
 *
 * Derived, never authored: ImportErrorReportExport rebuilds a job's lines from `import_rows` each
 * time the report is requested, in the requester's language. The flat shape is what PRD 11.4
 * specifies and what a later query can rely on; the source of truth stays the staging row.
 *
 * @property int $row_number
 * @property string|null $column
 * @property string|null $value
 * @property string $reason
 */
#[Fillable(['import_job_id', 'row_number', 'column', 'value', 'reason'])]
class ImportError extends Model
{
    public $timestamps = false;

    /** @return BelongsTo<ImportJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'import_job_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
        ];
    }
}
