<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * T037 - a campus.
 *
 * `gender` lives here and only here (FR-050). Every question about whether a cohort is male or
 * female resolves to this column, so no two code paths can hold different answers.
 */
#[Fillable(['organization_id', 'campus_name_ar', 'campus_name_en', 'gender', 'is_active'])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coordinator_branches')->withTimestamps();
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return ['organization_id', 'campus_name_ar', 'campus_name_en', 'gender', 'is_active', 'deleted_at'];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
