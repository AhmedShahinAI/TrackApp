<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Concerns;

use App\Modules\Authorization\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * T146 — makes a model branch-scoped (FR-022, FR-024).
 *
 * Using the trait is the whole declaration. A model that reaches its branch through a column other
 * than its own `branch_id` overrides {@see constrainToBranches()}; everything else inherits the
 * direct constraint below.
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait BelongsToBranchScope
{
    public static function bootBelongsToBranchScope(): void
    {
        static::addGlobalScope(new BranchScope);
    }

    /**
     * The default: a `branch_id` on this table.
     *
     * Qualified with the table name because these queries are joined often enough that an
     * unqualified `branch_id` becomes ambiguous the first time someone adds a join — and the error
     * surfaces as a 500 in an unrelated endpoint.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $branchIds
     */
    public function constrainToBranches(Builder $query, array $branchIds): void
    {
        $query->whereIn($this->qualifyColumn('branch_id'), $branchIds);
    }
}
