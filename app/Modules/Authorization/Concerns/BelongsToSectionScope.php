<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Concerns;

use App\Modules\Authorization\Scopes\SectionScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * T146 — makes a model section-scoped (FR-022, FR-026).
 *
 * The counterpart to {@see BelongsToBranchScope}. A model whose link to a section is not a
 * `section_id` column overrides {@see constrainToSections()}.
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait BelongsToSectionScope
{
    public static function bootBelongsToSectionScope(): void
    {
        static::addGlobalScope(new SectionScope);
    }

    /**
     * The default: a `section_id` on this table.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $sectionIds
     */
    public function constrainToSections(Builder $query, array $sectionIds): void
    {
        $query->whereIn($this->qualifyColumn('section_id'), $sectionIds);
    }
}
