<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * T038 - one row per period a person taught a section (FR-048).
 *
 * Not Auditable. The table *is* the history: auditing it would record the same fact twice, once here
 * and once in audit_logs, and the two could then disagree. The instructor change itself is audited
 * as `section.instructor_changed` by the action that writes both.
 */
#[Fillable(['section_id', 'instructor_id', 'assigned_at', 'unassigned_at', 'changed_by'])]
class SectionInstructorHistory extends Model
{
    protected $table = 'section_instructor_history';

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return BelongsTo<User, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }
}
