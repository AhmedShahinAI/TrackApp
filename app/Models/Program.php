<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T300 (Amendment 1, FR-089 to FR-095) - a specialisation programme.
 *
 * Deactivation is the only removal path (FR-092): sections reference a programme with a restricting
 * foreign key, so deleting one would either fail or orphan a section's identity. `is_active` hides
 * it from the pickers while leaving every existing section's record intact (constitution V).
 *
 * A soft-deleted programme keeps its code. FR-089 makes the code unique within the institution, not
 * among live rows, so the (organization_id, code) key deliberately leaves `deleted_at` out: the
 * sections still pointing at the programme name it by that code, and handing it to a new programme
 * would make old and new indistinguishable in every report and file. Reusing a code means restoring.
 */
#[Fillable(['organization_id', 'code', 'name_ar', 'name_en', 'unit', 'is_active'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use Auditable, HasFactory, SoftDeletes {
        SoftDeletes::forceDelete as purge;
    }

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

    /**
     * T294 — a programme's code is stored uppercase and trimmed, whoever writes it: the screen, an
     * import, a seeder. The unique key is (organization_id, code), and on a case-sensitive collation
     * `hr` and `HR` would otherwise both pass it — two rows for one programme, which every file that
     * names the programme by code would then resolve ambiguously.
     *
     * Null stays null, so a missing code fails the column's NOT NULL instead of becoming '' and
     * taking the organization's one empty-code slot. The mutator only covers Eloquent writes; the
     * programs migration adds an engine-level check so a query-builder `update()`, `insert()` or
     * `upsert()` (a Phase 15 bulk import, say) cannot store a lowercase code either.
     *
     * @return Attribute<string, string|null>
     */
    protected function code(): Attribute
    {
        return Attribute::make(set: static fn (mixed $value): ?string => $value === null
            ? null
            : mb_strtoupper(trim((string) $value)));
    }

    /**
     * FR-092 - a programme holding sections is never permanently removed, and the platform says so.
     *
     * The restricting foreign key would refuse it anyway, but as a raw QueryException (a 500). This
     * refuses first, in the reader's language. A programme with no sections may still be purged.
     */
    public function forceDelete(): bool
    {
        if ($this->sections()->exists()) {
            throw new ConflictHttpException(__('programs.errors.has_sections'));
        }

        // The SoftDeletes implementation, aliased as purge: the parent class's version only soft-deletes.
        return (bool) $this->purge();
    }

    /** @return BelongsToMany<Course, $this> */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return ['organization_id', 'code', 'name_ar', 'name_en', 'unit', 'is_active', 'deleted_at'];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
