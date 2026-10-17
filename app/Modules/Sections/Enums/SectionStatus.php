<?php

declare(strict_types=1);

namespace App\Modules\Sections\Enums;

use InvalidArgumentException;

/**
 * Amendment 4 (data-model.md Group 8 "State machine") — a section's approval state.
 *
 * The column stays a string on the model, because a great deal of earlier code compares it as one.
 * This enum is where the workflow's rules live, so the decision action, the policy and the screens ask
 * one place rather than each keeping a copy of the table.
 */
enum SectionStatus: string
{
    case Pending = 'pending';
    case Returned = 'returned';
    case Rejected = 'rejected';
    case Active = 'active';

    /**
     * The transitions a reviewer's decision may make (FR-180). `rejected` is terminal, and nothing is
     * decided on an `active` section: it is edited or reassigned, which is not an approval decision.
     * `returned` → `pending` is the creator's resubmission, which is not a decision either, and lives in
     * {@see canResubmit()}.
     *
     * @return list<self>
     */
    public function decisions(): array
    {
        return match ($this) {
            self::Pending => [self::Active, self::Returned, self::Rejected],
            self::Returned => [self::Rejected],
            self::Rejected, self::Active => [],
        };
    }

    public function canBecome(self $to): bool
    {
        return in_array($to, $this->decisions(), true);
    }

    public function canResubmit(): bool
    {
        return $this === self::Returned;
    }

    /**
     * The decision a reviewer names, to the state it produces (contracts/academic.md
     * `PATCH /sections/{id}/approval`).
     */
    public static function forDecision(string $decision): self
    {
        return match ($decision) {
            'approve' => self::Active,
            'return' => self::Returned,
            'reject' => self::Rejected,
            default => throw new InvalidArgumentException("Unknown section approval decision [{$decision}]."),
        };
    }

    /** The audit key a decision writes (FR-182). */
    public function auditAction(): string
    {
        return match ($this) {
            self::Active => 'section.approved',
            self::Returned => 'section.returned',
            self::Rejected => 'section.rejected',
            self::Pending => 'section.resubmitted',
        };
    }

    /** `sections.approval.status.*`: the section vocabulary, never a bare "approved" (FR-185). */
    public function labelKey(): string
    {
        return 'sections.approval.status.'.$this->value;
    }
}
