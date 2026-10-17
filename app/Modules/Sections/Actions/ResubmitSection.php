<?php

declare(strict_types=1);

namespace App\Modules\Sections\Actions;

use App\Models\Section;
use App\Models\SectionApprovalHistory;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Sections\Enums\SectionStatus;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * T490 — the creator sends a returned section back for a decision (FR-179, FR-183).
 *
 * Only the creator, and only from `returned`. A `rejected` section is terminal, and a new section is the
 * only way forward (FR-180). The creator may fix what the return asked for in the same request. The
 * fields go through the same form request as create, so the same uniqueness and setting rules hold.
 *
 * The previous review stays on the history row it was written to. The section's own review columns are
 * cleared, so a resubmitted section shows no stale reason as though it were the current one.
 *
 * @extends TransactionalAction<Section>
 */
final class ResubmitSection extends TransactionalAction
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Called as execute($section, $creator, $changes).
     */
    protected function handle(mixed ...$arguments): Section
    {
        /** @var Section $section */
        /** @var User $creator */
        /** @var array<string, mixed> $changes */
        [$section, $creator, $changes] = $arguments + [2 => []];

        /** @var Section $locked */
        $locked = Section::query()->withoutGlobalScopes()->whereKey($section->getKey())->lockForUpdate()->firstOrFail();

        if ((int) $locked->created_by !== (int) $creator->getKey()) {
            throw new HttpException(403, __('sections.approval.errors.not_creator'));
        }

        $from = SectionStatus::from((string) $locked->status);

        if (! $from->canResubmit()) {
            throw new ConflictHttpException(__('sections.approval.errors.resubmit_only_returned'));
        }

        /*
         * Only what the creator actually changed. The form request carries every field a resubmission
         * left out with its current value (StoreSectionRequest::prepareForValidation), so the payload
         * is the dirty subset, not the request, and the entry says what was corrected.
         */
        $locked->forceFill($changes);
        $changed = array_keys(array_intersect_key($locked->getDirty(), $changes));
        $before = array_intersect_key($locked->getOriginal(), array_flip($changed));
        $after = array_intersect_key($locked->getAttributes(), array_flip($changed));

        try {
            $locked->forceFill([
                'status' => SectionStatus::Pending->value,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_reason' => null,
            ])->saveQuietly();
        } catch (UniqueConstraintViolationException) {
            // Another section took this number between the form request's check and this write.
            throw ValidationException::withMessages(['section_number' => __('sections.errors.duplicate_key')]);
        }

        SectionApprovalHistory::query()->create([
            'section_id' => $locked->getKey(),
            'from_status' => $from->value,
            'to_status' => SectionStatus::Pending->value,
            'actor_id' => $creator->getKey(),
            'reason' => null,
        ]);

        $this->auditor->record(
            'section.resubmitted',
            $locked,
            ['status' => $from->value] + $before,
            ['status' => SectionStatus::Pending->value] + $after,
        );

        return $locked;
    }
}
