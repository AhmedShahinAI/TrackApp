<?php

declare(strict_types=1);

namespace App\Modules\Support\Policies;

use App\Models\User;

/**
 * T373 — who may do what to a support request (data-model.md Group 7 "Permissions").
 *
 * | Action  | Permission        | SUPER_ADMIN | BRANCH_COORDINATOR | INSTRUCTOR |
 * |---------|-------------------|-------------|--------------------|------------|
 * | view    | `support.view`    | ✅          | ✅                 | ✗          |
 * | respond | `support.respond` | ✅          | ✅                 | ✗          |
 * | assign  | `support.assign`  | ✅          | ✗                  | ✗          |
 * | close   | `support.close`   | ✅          | ✗                  | ✗          |
 *
 * Permissions, never role names (FR-020): a fourth role gets support access by holding these.
 *
 * **Which** request a holder may reach is not decided here. The branch scope on SupportRequest
 * makes an out-of-branch request unreachable — a 404 from the binding, not a 403 from this policy —
 * which is the platform's rule for not confirming that another branch's record exists.
 *
 * Moving a request between states (start, resolve, reopen, reject) is a response-holder's act, so it
 * is `respond`; closing is the record-keeping act and has its own permission. Which transitions are
 * legal is the transition action's job (FR-148), not this policy's.
 */
final class SupportRequestPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('support.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('support.view');
    }

    public function respond(User $actor): bool
    {
        return $actor->can('support.respond');
    }

    public function transition(User $actor): bool
    {
        return $actor->can('support.respond');
    }

    public function assign(User $actor): bool
    {
        return $actor->can('support.assign');
    }

    public function close(User $actor): bool
    {
        return $actor->can('support.close');
    }
}
