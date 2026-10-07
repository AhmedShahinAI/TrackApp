<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Scopes;

use App\Models\User;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * T145 — the instructor's section constraint (FR-022, FR-026).
 *
 * Constrains to `accessibleSectionIds()`: the union of sections the caller teaches and sections
 * named in `section_access_grants`. The grant half is what makes FR-026 work — a shared section is
 * reachable through exactly the same path as a taught one, so no endpoint needs to know the
 * difference. Only the `shared_access` label does.
 *
 * Inert under the same two conditions as {@see BranchScope}: no authenticated actor, and
 * `scope.bypass_all`.
 *
 * ## How the two scopes divide a caller between them
 *
 * Both are registered on the same models, and Eloquent ANDs global scopes together. Applying both
 * literally would give an instructor `branch_id IN () AND id IN (their sections)` — an empty result
 * for every instructor in the college. So each scope stands aside when its own claim is empty and
 * the other's is not:
 *
 * | caller                         | branches | sections | governed by  |
 * |--------------------------------|----------|----------|--------------|
 * | coordinator with branches      | A, B     | —        | BranchScope  |
 * | coordinator with none assigned | —        | —        | BranchScope → sees nothing |
 * | instructor                     | —        | S1, S2   | SectionScope |
 * | `scope.bypass_all` holder      | n/a      | n/a      | neither      |
 *
 * **The unspecified case is a caller holding both** — a coordinator who also teaches. Both scopes
 * stay active and AND together, so they see only the sections they teach *within* their branches.
 * Neither the spec nor FR-022 says what should happen there, and this is the direction that fails
 * safely: it shows too little, never another branch's data. It is flagged for the Product Owner
 * rather than decided here, because "coordinator who also teaches" is a staffing question, not an
 * engineering one.
 *
 * @implements Scope<Model>
 */
final class SectionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! $model instanceof ConstrainableToSections) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User || $user->can('scope.bypass_all')) {
            return;
        }

        $sectionIds = $user->accessibleSectionIds();

        // A branch-based caller is governed by BranchScope; see the table above.
        if ($sectionIds === [] && $user->coordinatorBranchIds() !== []) {
            return;
        }

        $model->constrainToSections($builder, $sectionIds);
    }
}
