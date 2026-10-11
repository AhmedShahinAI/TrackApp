<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use App\Modules\Authorization\Concerns\BelongsToBranchScope;
use App\Modules\Authorization\Concerns\BelongsToSectionScope;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T038 + T301 - a section.
 *
 * There is no gender attribute and no gender column. The accessor below reads `branch.gender`
 * (FR-050), which means a section can never disagree with its campus about who it serves. Writing
 * the value here would create a second place for the answer to live and a way for the two to drift.
 *
 * `status`, `requires_approval`, and `approval_key` are absent from the fillable list. The approval
 * state is derived from the creator's role by the create action (FR-171, FR-172) and must not be
 * settable from a request field; `approval_key` is a generated column the database owns.
 */
#[Fillable([
    'term_id', 'branch_id', 'course_id', 'program_id', 'section_number', 'instructor_id',
    'degree', 'activity',
])]
class Section extends Model implements ConstrainableToBranches, ConstrainableToSections
{
    /** @use HasFactory<SectionFactory> */
    use Auditable, BelongsToBranchScope, BelongsToSectionScope, HasFactory;

    /**
     * T150 — FR-026: true when the caller reaches this section only through a grant.
     *
     * Computed per caller, not stored. "Shared" is not a property of the section — the same row is
     * an instructor's own section, another instructor's shared one, and neither to a coordinator.
     * Storing it would be storing one viewer's perspective on everyone's behalf.
     *
     * Reads the caller's memoized grant list rather than querying, so rendering a page of sections
     * costs one query for the list and none per row.
     *
     * @return Attribute<bool, never>
     */
    protected function sharedAccess(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $user = \Illuminate\Support\Facades\Auth::user();

                return $user instanceof User
                    && in_array((int) $this->getKey(), $user->grantedSectionIds(), true);
            },
        );
    }

    /**
     * T145 — a section IS the section, so the constraint is on its own key rather than on a
     * `section_id` column it does not have.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $sectionIds
     */
    public function constrainToSections(Builder $query, array $sectionIds): void
    {
        $query->whereIn($this->qualifyColumn($this->getKeyName()), $sectionIds);
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * With trashed: a soft-deleted programme is still the one this section belongs to (FR-090), and
     * the default SoftDeletes scope would load it as null and blank the section's programme column.
     *
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<SectionEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(SectionEnrollment::class);
    }

    /** @return BelongsToMany<Student, $this> */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'section_enrollments')
            ->withPivot(['status', 'academic_status', 'roster_sequence', 'enrolled_at'])
            ->withTimestamps();
    }

    /** @return HasMany<SectionAccessGrant, $this> */
    public function accessGrants(): HasMany
    {
        return $this->hasMany(SectionAccessGrant::class);
    }

    /** @return HasMany<SectionInstructorHistory, $this> */
    public function instructorHistory(): HasMany
    {
        return $this->hasMany(SectionInstructorHistory::class);
    }

    /**
     * Derived, never stored (FR-050).
     *
     * This lazy-loads the branch when it is not already there. Callers listing sections should eager
     * load `branch`; the alternative - denormalising the column onto this table - trades an N+1 for
     * a correctness hazard, which is the wrong way round.
     *
     * @return Attribute<string, never>
     */
    protected function gender(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->branch->gender)->shouldCache();
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return [
            'term_id', 'branch_id', 'course_id', 'program_id', 'section_number', 'instructor_id',
            'degree', 'activity', 'status', 'requires_approval', 'reviewed_by', 'reviewed_at',
            'review_reason',
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }
}
