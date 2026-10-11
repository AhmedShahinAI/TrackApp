<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * T222 — one source row, staged (research.md R-03).
 *
 * ## Why nothing here is audited
 *
 * A staging row is not a record of anything that happened to the college's data — it is the
 * pipeline's scratch space, written fifty thousand at a time by a queued job. Auditing it would bury
 * the log under entries nobody can act on, and the moments that do matter (upload, commit, resume,
 * report download) are audited by name on the job.
 *
 * ## Why it carries no scope
 *
 * It is only ever reached through its job, and the job is scoped. A second scope here would be a
 * second place for the two to disagree.
 *
 * ## `reasons`
 *
 * A list of `{column, value, reason_key, params}`, stored as keys rather than sentences. The same
 * row is read in Arabic by one operator and in English by another (FR-062), so the language is
 * chosen when it is read — see ImportReasons::localise().
 *
 * @property int $id
 * @property int $import_job_id
 * @property int $row_number
 * @property string $status
 * @property array<string, string> $raw
 * @property array<string, mixed>|null $resolved
 * @property list<array{column: ?string, value: ?string, reason_key: string, params: array<string, mixed>}>|null $reasons
 */
#[Fillable(['import_job_id', 'row_number', 'raw', 'resolved', 'status', 'reasons'])]
class ImportRow extends Model
{
    /** Valid, warning and error are validation outcomes; committed and skipped are commit outcomes. */
    public const STATUSES = ['valid', 'warning', 'error', 'committed', 'skipped'];

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
            'raw' => 'array',
            'resolved' => 'array',
            'reasons' => 'array',
        ];
    }
}
