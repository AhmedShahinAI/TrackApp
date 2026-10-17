<?php

declare(strict_types=1);

namespace App\Modules\Sections\Actions;

use App\Models\Section;
use App\Models\SectionApprovalHistory;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Sections\Enums\SectionStatus;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T489 — approve, return or reject a section (FR-177 … FR-182).
 *
 * The caller has already passed the policy (who may decide). This enforces the state machine (what may be
 * decided): 409 on any transition SectionStatus does not permit, notably any decision on an `active`
 * section or a `rejected` one, which is terminal. A return or a reject carries its reason; the form request
 * asks for it, and the model refuses to save without it.
 *
 * Re-read under a lock, so two reviewers deciding at once cannot both win: the second sees the first's
 * result and is refused by the state machine.
 *
 * Saved quietly, with one named entry: the model's trait would otherwise add a generic `section.updated`
 * beside `section.approved`, and T484 asks for exactly one entry per decision.
 *
 * @extends TransactionalAction<Section>
 */
final class DecideSectionApproval extends TransactionalAction
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Called as execute($section, $reviewer, $decision, ?$reason).
     */
    protected function handle(mixed ...$arguments): Section
    {
        /** @var Section $section */
        /** @var User $reviewer */
        [$section, $reviewer, $decision, $reason] = $arguments + [3 => null];

        /** @var Section $locked */
        $locked = Section::query()->withoutGlobalScopes()->whereKey($section->getKey())->lockForUpdate()->firstOrFail();

        $from = SectionStatus::from((string) $locked->status);
        $to = SectionStatus::forDecision((string) $decision);

        if (! $from->canBecome($to)) {
            throw new ConflictHttpException(__('sections.approval.errors.transition', [
                'from' => __($from->labelKey()),
                'to' => __($to->labelKey()),
            ]));
        }

        $reason = $to === SectionStatus::Active ? null : trim((string) $reason);

        /*
         * FR-179, FR-180, at the action layer (Phase 27 review). The save below is quiet, to keep the
         * trait's generic entry out, and quiet skips the model's saving guard too. So a caller that
         * reaches this action without the form request (a job, a command) is held to the same rule.
         */
        if ($reason !== null && mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['reason' => __('sections.approval.errors.reason_required')]);
        }

        $locked->forceFill([
            'status' => $to->value,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_reason' => $reason,
        ])->saveQuietly();

        SectionApprovalHistory::query()->create([
            'section_id' => $locked->getKey(),
            'from_status' => $from->value,
            'to_status' => $to->value,
            'actor_id' => $reviewer->getKey(),
            'reason' => $reason,
        ]);

        $this->auditor->record(
            $to->auditAction(),
            $locked,
            ['status' => $from->value],
            array_filter(['status' => $to->value, 'reason' => $reason], fn ($value) => $value !== null),
        );

        return $locked;
    }
}
