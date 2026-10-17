<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use App\Modules\Authorization\Concerns\BelongsToBranchScope;
use App\Modules\Authorization\Concerns\BelongsToSectionScope;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
use App\Modules\Sections\Enums\SectionStatus;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

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

    /**
     * withTrashed (Phase 27 review): a creator whose account was later deleted still created it. The
     * queue scope and the policy both ask this relation whether the creator could approve sections;
     * without the deleted rows, the queue listed such a section for coordinators while the policy saw
     * no creator and refused, and the row sat undecidable in every queue.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
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
     * T487 — the approval decision trail, oldest first. Separate from instructorHistory, which records
     * teaching, not approval.
     *
     * @return HasMany<SectionApprovalHistory, $this>
     */
    public function approvalHistory(): HasMany
    {
        return $this->hasMany(SectionApprovalHistory::class)->oldest('created_at')->oldest('id');
    }

    /**
     * T487 — FR-174: only an approved section takes part in anything downstream.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), SectionStatus::Active->value);
    }

    /**
     * T487 — the sections waiting on a decision this user may make (FR-177, FR-178, FR-184).
     *
     * Asymmetric, as data-model.md Group 8 tabulates:
     *
     *   - a Super Admin decides every pending section except their own (none exist: theirs are active);
     *   - a coordinator decides only sections whose creator could **not** approve sections themselves,
     *     which is an instructor's. A creator holding `section.approve` (a coordinator, or one who also
     *     teaches) is a peer, not a second pair of eyes, so their requests are the Super Admin's.
     *     Their own are excluded too.
     *
     * "Could approve" is the creator's permission, never a role name (FR-020). The branch half is the
     * global BranchScope, already on every Section query; this adds only the rule the scope cannot know.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAwaitingDecisionBy(Builder $query, User $user): Builder
    {
        $query->where($this->qualifyColumn('status'), SectionStatus::Pending->value)
            ->where($this->qualifyColumn('created_by'), '!=', $user->getKey());

        if ($user->can('scope.bypass_all')) {
            return $query;
        }

        return $query->whereDoesntHave('creator', fn (Builder $creator) => $creator->permission('section.approve'));
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
            'review_reason', 'created_by',
        ];
    }

    /**
     * T488 — `section.created` records the creator's role beside the resulting status
     * (data-model.md Group 8 "Audit actions"). It is added only where the creator appears, which is
     * creation. The role is read now, because a later role change must not rewrite why this section
     * needed approving.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditPayload(array $attributes): array
    {
        $payload = array_intersect_key($attributes, array_flip($this->auditedAttributes()));

        if ($this->wasRecentlyCreated && ($payload['created_by'] ?? null) !== null) {
            $payload['creator_role'] = User::query()->find($payload['created_by'])?->getRoleNames()->first();
        }

        return $payload;
    }

    protected static function booted(): void
    {
        // FR-179, FR-180 — the guard that holds when a later code path forgets the form request.
        static::saving(function (Section $section): void {
            if (in_array($section->status, [SectionStatus::Returned->value, SectionStatus::Rejected->value], true)
                && $section->isDirty('status')
                && trim((string) $section->review_reason) === '') {
                throw new LogicException('A section is returned or rejected only with a reason.');
            }
        });
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
