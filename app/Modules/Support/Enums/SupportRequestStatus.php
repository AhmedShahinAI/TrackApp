<?php

declare(strict_types=1);

namespace App\Modules\Support\Enums;

/**
 * T363 — the five states of a support request (data-model.md "State machine").
 *
 * The one fixed vocabulary in the support feature, mirrored by the `status` database enum. A
 * category is a setting the Super Admin extends; a status is a workflow contract that the queue
 * filters and five years of audit history read, so it is code and schema rather than configuration.
 *
 * Which transitions are permitted is the job of the transition action (Phase 21, FR-148), not of
 * this enum: a state list and a state machine change for different reasons.
 */
enum SupportRequestStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';

    /**
     * Still waiting on staff. Resolved counts as open: it can be reopened, and it is not final until
     * someone holding `support.close` closes it.
     */
    public function isOpen(): bool
    {
        return $this !== self::Closed && $this !== self::Rejected;
    }

    /**
     * The `support.statuses.*` translation key (T376).
     */
    public function labelKey(): string
    {
        return 'support.statuses.'.$this->value;
    }
}
