<?php

declare(strict_types=1);

namespace App\Modules\Sections\Policies;

use App\Models\Section;
use App\Models\User;
use App\Modules\Sections\Enums\SectionStatus;
use Illuminate\Auth\Access\Response;

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

    /**
     * T491 — FR-178. Holding `section.approve` is not enough; three refusals, all **403**, because the
     * caller can see the section and simply may not decide it:
     *
     *   - its creator, who would be approving their own work;
     *   - a coordinator, when the creator could approve sections themselves: a fellow coordinator, or
     *     one who also teaches, is a peer, not a second pair of eyes, so those go to the Super Admin.
     *
     * "Could approve" is the creator's own `section.approve`, never a role name. FR-020 makes a fourth
     * role a seeded row and nothing else (NewRoleNeedsNoCodeChangeTest).
     *
     * Without a section (the queue route's `can:`), only the permission is asked.
     */
    public function approve(User $actor, ?Section $section = null): Response
    {
        if (! $actor->can('section.approve')) {
            return Response::deny();
        }

        if ($section === null) {
            return Response::allow();
        }

        if ((int) $section->created_by === (int) $actor->getKey()) {
            return Response::deny(__('sections.approval.errors.own_section'));
        }

        if (! $actor->can('scope.bypass_all') && ($section->creator === null || $section->creator->can('section.approve'))) {
            return Response::deny(__('sections.approval.errors.peer_section'));
        }

        return Response::allow();
    }

    /** T490 — only the creator resubmits, and only while they may still create sections. */
    public function resubmit(User $actor, Section $section): Response
    {
        return $actor->can('section.create') && (int) $section->created_by === (int) $actor->getKey()
            ? Response::allow()
            : Response::deny(__('sections.approval.errors.not_creator'));
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
            || in_array((int) $section->getKey(), $actor->grantedSectionIds(), true)
            // FR-175 (Phase 27 review): the creator attaches the roster to a section still awaiting
            // approval, whether or not they named themselves its instructor. Once it is approved,
            // teaching or a grant decides, as for every other section.
            || ((int) $section->created_by === (int) $actor->getKey() && $section->status !== SectionStatus::Active->value);
    }
}
