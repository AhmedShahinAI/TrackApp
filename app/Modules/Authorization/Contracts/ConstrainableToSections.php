<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * The section-scoped counterpart to {@see ConstrainableToBranches}.
 */
interface ConstrainableToSections
{
    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $sectionIds
     */
    public function constrainToSections(Builder $query, array $sectionIds): void;
}
