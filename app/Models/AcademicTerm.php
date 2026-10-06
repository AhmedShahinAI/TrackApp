<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\AcademicTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * T037 - an academic term.
 *
 * `is_current` carries no database-level uniqueness, because MySQL has no partial unique index.
 * FR-036's "exactly one current term" is held by the SetCurrentTerm action inside a transaction; the
 * scope below is only a convenience for reading it.
 */
#[Fillable([
    'name_ar', 'name_en', 'academic_year', 'semester', 'start_date', 'end_date', 'status', 'is_current',
])]
class AcademicTerm extends Model
{
    /** @use HasFactory<AcademicTermFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'term_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return [
            'name_ar', 'name_en', 'academic_year', 'semester', 'start_date', 'end_date',
            'status', 'is_current', 'deleted_at',
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }
}
