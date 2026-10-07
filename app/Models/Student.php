<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use App\Modules\Authorization\Concerns\BelongsToBranchScope;
use App\Modules\Authorization\Concerns\BelongsToSectionScope;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * T039 - a student. Soft-deleted only (constitution V): a hard delete would take the enrollment
 * history and every initiative record attached to it, which is the historical record the platform
 * exists to keep.
 */
#[Fillable(['university_id', 'name_ar', 'name_en'])]
class Student extends Model implements ConstrainableToBranches, ConstrainableToSections
{
    /** @use HasFactory<StudentFactory> */
    use Auditable, BelongsToBranchScope, BelongsToSectionScope, HasFactory, SoftDeletes;

    /**
     * T146 — a student has no branch and no section of their own.
     *
     * They are reachable only through an enrollment, which is the point: a student is visible to a
     * coordinator because they sit in one of that coordinator's sections, not because the student
     * record carries a campus. `whereExists` rather than a join, so a student enrolled in several
     * in-scope sections still appears exactly once (FR-043) with no `distinct()` to remember.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $branchIds
     */
    public function constrainToBranches(Builder $query, array $branchIds): void
    {
        $this->constrainThroughEnrollments(
            $query,
            Section::query()->withoutGlobalScopes()->whereIn('branch_id', $branchIds)->select('id'),
        );
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $sectionIds
     */
    public function constrainToSections(Builder $query, array $sectionIds): void
    {
        $this->constrainThroughEnrollments($query, $sectionIds);
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>|Builder<covariant \Illuminate\Database\Eloquent\Model>  $sectionIds
     */
    private function constrainThroughEnrollments(Builder $query, array|Builder $sectionIds): void
    {
        /*
         * whereHas, not a hand-built whereExists with selectRaw('1'). The first version used raw
         * SQL and tripped the constitution's no-raw-SQL floor, which is the correct outcome: the
         * relation already knows the join, and spelling it out by hand is how a column rename
         * becomes a silent scope failure rather than a failing test.
         *
         * withoutGlobalScopes on the inner query for the usual reason — SectionEnrollment is itself
         * scoped, and applying the caller's constraint twice narrows the result in a way that is
         * invisible from here.
         */
        $query->whereHas('enrollments', function (Builder $enrollments) use ($sectionIds): void {
            $enrollments->withoutGlobalScopes()->whereIn('section_id', $sectionIds);
        });
    }

    /** @return HasMany<SectionEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(SectionEnrollment::class);
    }

    /** @return BelongsToMany<Section, $this> */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_enrollments')
            ->withPivot(['status', 'academic_status', 'roster_sequence', 'enrolled_at'])
            ->withTimestamps();
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return ['university_id', 'name_ar', 'name_en', 'deleted_at'];
    }
}
