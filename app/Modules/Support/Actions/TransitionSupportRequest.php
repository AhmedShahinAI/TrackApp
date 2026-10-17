<?php

declare(strict_types=1);

namespace App\Modules\Support\Actions;

use App\Models\SupportRequest;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use App\Modules\Support\Enums\SupportRequestStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T416 — move a support request from one state to another, by the state machine and nothing else
 * (data-model.md "State machine", FR-147–FR-150).
 *
 *         ┌──────────────────────────────┐
 *         │                              ▼
 *  new ──► in_progress ──► resolved ──► closed
 *   │            │
 *   └────────────┴──────► rejected
 *
 * plus `resolved` → `in_progress` (reopening). Everything else is refused with 409 — notably `new` →
 * `closed` and `new` → `resolved`, so a request is never disposed of without being opened, and
 * anything out of `closed` or `rejected`, which are final.
 *
 * Three further conditions, each its own refusal:
 *   • `resolved` needs at least one response — "resolved" with nothing said to the student is a
 *     request closed in silence (409);
 *   • `closed` needs `support.close`, the record-keeping permission only the Super Admin holds (403);
 *   • `rejected` needs a reason, written for the student, who sees it (FR-149; 422).
 *
 * The row is locked for the read-then-write, so two staff members acting at once cannot both move a
 * request out of the same state — the second sees the first's result and is refused if it no longer
 * applies. One audit entry, `support.status_changed`, with the old and new state and the reason
 * (constitution II: inside the transaction, so a failed entry undoes the change).
 *
 * @extends TransactionalAction<SupportRequest>
 */
final class TransitionSupportRequest extends TransactionalAction
{
    /**
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        'new' => ['in_progress', 'rejected'],
        'in_progress' => ['resolved', 'rejected'],
        'resolved' => ['closed', 'in_progress'],
        'closed' => [],
        'rejected' => [],
    ];

    public function __construct(private readonly Auditor $auditor) {}

    public static function permits(SupportRequestStatus $from, SupportRequestStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value], true);
    }

    /**
     * T413 — the open side effect: `new` → `in_progress`, if and only if the row is still `new` once
     * locked (Phase 21 review).
     *
     * The caller read `new` from a model bound before any lock. Two staff opening the same request at
     * once both read `new`; the first commits the transition, and the second, under an ordinary
     * transition, would find in_progress → in_progress refused and answer a plain page view with 409 —
     * the second person could not open the request at all. Decided under the lock instead: whoever
     * gets there second finds it already open, changes nothing and writes no entry.
     */
    public function openIfNew(SupportRequest $request, User $actor): SupportRequest
    {
        return $this->execute($request, SupportRequestStatus::InProgress, null, $actor, SupportRequestStatus::New);
    }

    /**
     * Called as execute($request, $to, $reason, $actor[, $onlyFrom]). With `$onlyFrom`, a row found in
     * any other state once locked is returned unchanged rather than refused.
     */
    protected function handle(mixed ...$arguments): SupportRequest
    {
        /** @var SupportRequest $request */
        /** @var SupportRequestStatus $to */
        /** @var string|null $reason */
        /** @var User $actor */
        [$request, $to, $reason, $actor] = $arguments;

        /** @var SupportRequestStatus|null $onlyFrom */
        $onlyFrom = $arguments[4] ?? null;

        /** @var SupportRequest $locked */
        $locked = SupportRequest::query()->withoutGlobalScopes()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
        $from = $locked->status;

        if ($onlyFrom !== null && $from !== $onlyFrom) {
            return $locked;
        }

        if (! self::permits($from, $to)) {
            throw new ConflictHttpException(__('support.staff.errors.transition', [
                'from' => __($from->labelKey()),
                'to' => __($to->labelKey()),
            ]));
        }

        if ($to === SupportRequestStatus::Closed && ! $actor->can('support.close')) {
            throw new AuthorizationException(__('support.staff.errors.close_forbidden'));
        }

        if ($to === SupportRequestStatus::Resolved && ! $locked->responses()->exists()) {
            throw new ConflictHttpException(__('support.staff.errors.resolve_needs_response'));
        }

        $reason = $reason === null ? null : trim($reason);

        if ($to === SupportRequestStatus::Rejected && ($reason === null || $reason === '')) {
            throw ValidationException::withMessages(['reason' => __('support.staff.errors.reason_required')]);
        }

        $changes = ['status' => $to->value];

        if ($to === SupportRequestStatus::Rejected) {
            $changes['rejection_reason'] = $reason;
        }

        $locked->forceFill($changes)->save();

        // "With the old and new status, and the reason where given" (contracts/support-staff.md). A
        // reason given with any other transition — "the fix did not hold", on a reopen — is not a
        // column on the request, but it is the history's answer to "why", so it goes in the entry
        // (Phase 21 review: it used to be dropped without a word).
        $audited = $changes;

        if ($to !== SupportRequestStatus::Rejected && $reason !== null && $reason !== '') {
            $audited['reason'] = $reason;
        }

        $this->auditor->record('support.status_changed', $locked, ['status' => $from->value], $audited);

        return $locked;
    }
}
