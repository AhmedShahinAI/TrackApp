<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use Auditable, HasFactory;

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

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
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
