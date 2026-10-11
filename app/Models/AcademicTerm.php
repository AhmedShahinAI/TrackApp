<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Carbon\CarbonInterface;
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
 *
 * The two dates are declared here as well as cast below. A cast is invisible to static analysis, so
 * without these a reader — human or analyser — sees `string` and `$term->start_date->isoFormat()`
 * looks like a bug when it is correct. They are documentation of the cast, not a second source.
 *
 * @property CarbonInterface|null $start_date
 * @property CarbonInterface|null $end_date
 * @property bool $is_current
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

    /**
     * T167 — the contract names this entity `term`, not `academic_term`.
     *
     * contracts/audit.md lists 38 action keys and a Pest test treats that list as closed. The
     * trait's default is snake(class_basename), which would emit `academic_term.created` — a key the
     * contract does not contain, and therefore an action no audit screen has a label or a filter
     * for.
     */
    public function auditEntityKey(): string
    {
        return 'term';
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
