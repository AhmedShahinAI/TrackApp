<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * T300 (Amendment 1, FR-089 to FR-095) - a specialisation programme.
 *
 * Deactivation is the only removal path (FR-092): sections reference a programme with a restricting
 * foreign key, so deleting one would either fail or orphan a section's identity. `is_active` hides
 * it from the pickers while leaving every existing section's record intact (constitution V).
 */
#[Fillable(['organization_id', 'code', 'name_ar', 'name_en', 'unit', 'is_active'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use Auditable, HasFactory, SoftDeletes;

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
