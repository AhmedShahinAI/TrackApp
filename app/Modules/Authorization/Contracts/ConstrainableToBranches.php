<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * A model that knows how to narrow itself to a set of branches.
 *
 * The contract exists so {@see \App\Modules\Authorization\Scopes\BranchScope} can ask the question
 * in the type system rather than in a docblock. The first version asserted the shape with
 * `@var object{constrainToBranches: callable}`, which PHPStan correctly refused: the parameter is a
 * Model, and claiming otherwise in a comment is how a scope ends up calling a method that a model
 * added to the trait list last week does not actually have.
 */
interface ConstrainableToBranches
{
    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $branchIds
     */
    public function constrainToBranches(Builder $query, array $branchIds): void;
}
