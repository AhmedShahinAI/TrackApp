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
/**
 * Declared for static analysis. Larastan reads attribute types from @property or the $casts
 * property, not from the casts() method this model uses — so without these, every
 * `$user->last_login_at->format(...)` reads as a method call on a string.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name_ar
 * @property string $name_en
 * @property string $email
 * @property string $password
 * @property string $locale
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $first_login_at
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $taught_sections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Branch> $coordinatorBranches
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

    /** @var list<int>|null */
    private ?array $memoizedGrantedSectionIds = null;

    /**
     * A soft delete is recorded as a deactivation (T167).
     *
     * Constitution V forbids a hard delete, so `delete()` sets `deleted_at` and the row stays.
     * contracts/audit.md names the event accordingly and lists no `.deleted` key at all, so the
     * trait's default would emit one outside the contract's closed list of 38 — a key no filter
     * offers, no label covers, and which tells a reader the row is gone when it is not.
     */
    public function auditActionFor(string $event): string
    {
        return $this->auditEntityKey().'.'.($event === 'deleted' ? 'deactivated' : $event);
    }

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

        /*
         * withoutGlobalScopes(), and this is not an optimisation.
         *
         * SectionScope asks this method which sections the user may see. Asking it through a
         * *scoped* query means the scope calls this method, which runs a scoped query, which calls
         * the scope — a recursion that ends as a 512 MB memory exhaustion inside Spatie's
         * permission registrar, several frames from anything that looks related.
         *
         * The rule generalises: a query that DEFINES a caller's scope must never be subject to it.
         */
        $taught = $this->taughtSections()->withoutGlobalScopes()->pluck('sections.id');
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
        $this->memoizedGrantedSectionIds = null;
    }

    /**
     * The sections this user reaches **only** through a grant (FR-026).
     *
     * Separate from accessibleSectionIds() because the two answer different questions: that one
     * decides visibility, this one decides the label. A section the user both teaches and holds a
     * stale grant for is not "shared" — it is theirs — so the taught set is subtracted here rather
     * than at the call site, where every caller would have to remember to do it.
     *
     * @return list<int>
     */
    public function grantedSectionIds(): array
    {
        if ($this->memoizedGrantedSectionIds !== null) {
            return $this->memoizedGrantedSectionIds;
        }

        // Unscoped for the same reason as accessibleSectionIds() above.
        $taught = $this->taughtSections()->withoutGlobalScopes()->pluck('sections.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return $this->memoizedGrantedSectionIds = $this->sectionAccessGrants()
            ->pluck('section_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->reject(static fn (int $id): bool => in_array($id, $taught, true))
            ->unique()
            ->sort()
            ->values()
            ->all();
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
