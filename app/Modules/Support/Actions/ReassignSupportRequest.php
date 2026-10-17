<?php

declare(strict_types=1);

namespace App\Modules\Support\Actions;

use App\Models\SupportRequest;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Validation\ValidationException;

/**
 * T417 — give a support request to someone, or to no one (FR-152). Super Admin only, by the route's
 * `support.assign`.
 *
 * Refused (422) when the assignee could not open the request: they hold no `support.view`, or they are
 * a coordinator whose branches do not include the student's. Either way the request would sit assigned
 * to someone it is invisible to — stranded, with the queue showing it as handled. The contract names
 * the permission; the branch half is the same harm from the other side, so it is refused the same way.
 *
 * Audited as `support.reassigned` with the previous and new assignee.
 *
 * @extends TransactionalAction<SupportRequest>
 */
final class ReassignSupportRequest extends TransactionalAction
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Called as execute($request, $assignee).
     */
    protected function handle(mixed ...$arguments): SupportRequest
    {
        /** @var SupportRequest $request */
        /** @var User|null $assignee */
        [$request, $assignee] = $arguments;

        if ($assignee !== null && ! self::canOpen($assignee, $request)) {
            throw ValidationException::withMessages(['assigned_to' => __('support.staff.errors.assignee_cannot_view')]);
        }

        /** @var SupportRequest $locked */
        $locked = SupportRequest::query()->withoutGlobalScopes()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
        $previous = $locked->assigned_to;
        $next = $assignee?->getKey();

        if ($previous === $next) {
            return $locked;
        }

        $locked->forceFill(['assigned_to' => $next])->save();

        $this->auditor->record('support.reassigned', $locked, ['assigned_to' => $previous], ['assigned_to' => $next]);

        return $locked;
    }

    /**
     * Whether this user, signed in, would reach this request: active, holding the permission, and
     * either college-wide or a coordinator of a branch the student is actively enrolled in
     * (SupportRequest::studentBranchIds, the scope's own definition).
     *
     * @param  list<int>|null  $studentBranchIds  computed once by a caller checking many users
     */
    public static function canOpen(User $user, SupportRequest $request, ?array $studentBranchIds = null): bool
    {
        if (! $user->is_active || ! $user->can('support.view')) {
            return false;
        }

        if ($user->can('scope.bypass_all')) {
            return true;
        }

        $branches = $user->relationLoaded('coordinatorBranches')
            ? $user->coordinatorBranches->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()
            : $user->coordinatorBranchIds();

        return array_intersect($branches, $studentBranchIds ?? $request->studentBranchIds()) !== [];
    }
}
