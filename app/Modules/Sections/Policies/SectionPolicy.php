<?php

declare(strict_types=1);

namespace App\Modules\Sections\Policies;

use App\Models\Section;
use App\Models\User;

/**
 * T195 — sections.
 *
 * One method per action, each mapping to its named permission (FR-019, constitution VIII).
 *
 * The policy answers only the *permission* question. Whether the caller may see the record at all
 * is the global scopes' job, and answering it here would produce 403 where the constitution
 * requires 404 — a 403 confirms the record exists, which is how a coordinator enumerates another
 * branch's ids (research.md R-01).
 */
final class SectionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('section.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('section.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('section.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('section.edit');
    }

    /**
     * Reassignment moves who may open the section from now on; it is not an edit to a field.
     */
    public function changeInstructor(User $actor): bool
    {
        return $actor->can('section.change_instructor');
    }

    public function grantAccess(User $actor): bool
    {
        return $actor->can('section.access_override');
    }

    public function approve(User $actor): bool
    {
        return $actor->can('section.approve');
    }

    /**
     * T254 — loading a student list into this section (FR-054).
     *
     * The one method here that reads the model, and deliberately. The scope decides whether the
     * section can be reached at all — an instructor's foreign section is already a 404 from the
     * binding — but "can see it" is wider than "may import into it": a coordinator sees their whole
     * branch. FR-054 limits the import to a section the requester teaches or holds a grant for. A
     * Super Admin may act on any section, as the reference's own condition allows.
     *
     * Asked again by every commit chunk (CommitImportChunkJob), so an instructor who loses the
     * section mid-import stops writing to it.
     */
    public function importRoster(User $actor, Section $section): bool
    {
        if (! $actor->can('import.section.run')) {
            return false;
        }

        return $actor->can('scope.bypass_all')
            || (int) $section->instructor_id === (int) $actor->getKey()
            || in_array((int) $section->getKey(), $actor->grantedSectionIds(), true);
    }
}
