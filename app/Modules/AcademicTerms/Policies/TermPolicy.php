<?php

declare(strict_types=1);

namespace App\Modules\AcademicTerms\Policies;

use App\Models\User;

/**
 * T195 — academic terms.
 *
 * One method per action, each mapping to its named permission (FR-019, constitution VIII).
 *
 * The policy answers only the *permission* question. Whether the caller may see the record at all
 * is the global scopes' job, and answering it here would produce 403 where the constitution
 * requires 404 — a 403 confirms the record exists, which is how a coordinator enumerates another
 * branch's ids (research.md R-01).
 */
final class TermPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('term.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('term.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('term.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('term.edit');
    }

    /**
     * Its own permission: correcting a term name and declaring the term closed are different authorities.
     */
    public function changeStatus(User $actor): bool
    {
        return $actor->can('term.change_status');
    }

    public function setCurrent(User $actor): bool
    {
        return $actor->can('term.set_current');
    }

    /**
     * Reopening a closed term. Closing is how an institution says these records are final.
     */
    public function reopen(User $actor): bool
    {
        return $actor->can('term.edit_closed');
    }
}
