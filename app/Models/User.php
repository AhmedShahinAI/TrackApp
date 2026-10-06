<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * T036 - an account.
 *
 * `password` is absent from the fillable list on purpose. It is set through a dedicated action that
 * audits the change, so a mass-assignment path would be a way to change a password without the
 * entry that FR-029 requires.
 *
 * The two id helpers at the bottom are what research.md R-01's global scopes read on every scoped
 * query. They are memoized per instance because a global scope runs on every query the request
 * makes, and re-reading the pivot each time would turn one page load into dozens of identical
 * lookups.
 */
#[Fillable(['organization_id', 'name_ar', 'name_en', 'email', 'locale', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** @var list<int>|null */
    private ?array $memoizedCoordinatorBranchIds = null;

    /** @var list<int>|null */
    private ?array $memoizedAccessibleSectionIds = null;

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsToMany<Branch, $this> */
    public function coordinatorBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'coordinator_branches')->withTimestamps();
    }

    /** @return HasMany<Section, $this> */
    public function taughtSections(): HasMany
    {
        return $this->hasMany(Section::class, 'instructor_id');
    }

    /** @return HasMany<SectionAccessGrant, $this> */
    public function sectionAccessGrants(): HasMany
    {
        return $this->hasMany(SectionAccessGrant::class);
    }

    /**
     * The branches this user coordinates. Empty for an instructor and for a Super Admin - the Super
     * Admin is not scoped by branch at all, and BranchScope is inert for them rather than being fed
     * a list of every branch.
     *
     * @return list<int>
     */
    public function coordinatorBranchIds(): array
    {
        return $this->memoizedCoordinatorBranchIds ??= $this->coordinatorBranches()
            ->pluck('branches.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Sections this user may reach: the ones they teach, plus the ones explicitly shared with them.
     *
     * The union is the whole definition of "accessible" for an instructor (FR-038, FR-039). A
     * shared grant is how a substitute or a co-teacher gets in without being made the instructor of
     * record, which would rewrite the section's instructor history.
     *
     * @return list<int>
     */
    public function accessibleSectionIds(): array
    {
        if ($this->memoizedAccessibleSectionIds !== null) {
            return $this->memoizedAccessibleSectionIds;
        }

        $taught = $this->taughtSections()->pluck('sections.id');
        $granted = $this->sectionAccessGrants()->pluck('section_id');

        return $this->memoizedAccessibleSectionIds = $taught
            ->concat($granted)
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Drop the memoized scope ids. A long-running process - a queue worker, a chunked import - that
     * changes a grant must be able to see its own change.
     */
    public function forgetScopeCache(): void
    {
        $this->memoizedCoordinatorBranchIds = null;
        $this->memoizedAccessibleSectionIds = null;
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return ['organization_id', 'name_ar', 'name_en', 'email', 'locale', 'is_active', 'deleted_at'];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }
}
