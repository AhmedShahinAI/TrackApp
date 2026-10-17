<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use App\Modules\Authorization\Concerns\BelongsToBranchScope;
use App\Modules\Authorization\Concerns\BelongsToSectionScope;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
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
/**
 * @property \Illuminate\Support\Carbon|null $enrolled_at
 * @property \Illuminate\Support\Carbon|null $withdrawn_at
 */
#[Fillable([
    'student_id', 'section_id', 'status', 'enrolled_at', 'withdrawn_at', 'withdraw_reason',
    'academic_status', 'roster_sequence',
])]
class SectionEnrollment extends Model implements ConstrainableToBranches, ConstrainableToSections
{
    /** @use HasFactory<SectionEnrollmentFactory> */
    use Auditable, BelongsToBranchScope, BelongsToSectionScope, HasFactory;

    /**
     * T146 — an enrollment reaches its branch through its section.
     *
     * The subquery runs `withoutGlobalScopes()` deliberately. Sections are themselves scoped, so
     * leaving them on would apply the caller's constraint twice — harmless for a coordinator, but
     * for any future caller whose two claims differ it would silently narrow the result in a way
     * nobody could read off this method.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $branchIds
     */
    public function constrainToBranches(Builder $query, array $branchIds): void
    {
        $query->whereIn(
            $this->qualifyColumn('section_id'),
            Section::query()->withoutGlobalScopes()->whereIn('branch_id', $branchIds)->select('id'),
        );
    }

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
     * Amendment 2 (Phase 23 review) — an enrollment the student holds NOW: active, not withdrawn by the
     * registrar (`academic_status`), in an active section (not pending, returned or rejected).
     *
     * The one definition the support channel's visibility rests on. It was written out three times —
     * the branch scope, the reassignment check, the queue's branch column — and they drifted: the column
     * named branches that granted no visibility. Each now calls this.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCurrentlyHeld(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status'), 'active')
            ->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('academic_status'))
                ->orWhere($this->qualifyColumn('academic_status'), '!=', 'withdrawn'))
            ->whereIn($this->qualifyColumn('section_id'), Section::query()->withoutGlobalScopes()->where('status', 'active')->select('id'));
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
