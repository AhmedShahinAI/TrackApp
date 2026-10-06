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
