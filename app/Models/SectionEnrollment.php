<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\SectionEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * T039 - a student's place in a section.
 *
 * `status` is TRACK's own state, changed by a person with a written reason. `academic_status` is the
 * registrar's standing, arriving in an import. They are separate columns and neither may overwrite
 * the other (FR-110, FR-111) - an import that could clear a TRACK withdrawal would erase the reason
 * and the audit trail FR-047 requires.
 */
#[Fillable([
    'student_id', 'section_id', 'status', 'enrolled_at', 'withdrawn_at', 'withdraw_reason',
    'academic_status', 'roster_sequence',
])]
class SectionEnrollment extends Model
{
    /** @use HasFactory<SectionEnrollmentFactory> */
    use Auditable, HasFactory;

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * The action key distinguishes an enrollment from a withdrawal, which the generic `.updated` key
     * would not (contracts/audit.md lists `enrollment.created` and `enrollment.withdrawn`).
     */
    public function auditEntityKey(): string
    {
        return 'enrollment';
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return [
            'student_id', 'section_id', 'status', 'enrolled_at', 'withdrawn_at', 'withdraw_reason',
            'academic_status', 'roster_sequence',
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'roster_sequence' => 'integer',
        ];
    }
}
