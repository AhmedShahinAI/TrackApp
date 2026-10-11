<?php

declare(strict_types=1);

namespace App\Modules\Students\Actions;

use App\Models\SectionEnrollment;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T194 — withdrawing a student from a section (FR-047).
 *
 * ## The reason is the point
 *
 * A withdrawal without a reason is a number that dropped, and nobody a year later can say why. The
 * reason is mandatory at every layer that can refuse it — the form, this action, and the screen's
 * textarea — because the one place it will be skipped is the one where it was optional.
 *
 * ## Nothing is deleted
 *
 * The enrollment's status changes and the student's record is untouched (constitution V). A student
 * withdrawn from one section is still enrolled in the others, still appears once in the roster
 * lists, and still owns every record that names them. Deleting the row instead would take the
 * reason with it, which is the one thing this action exists to write down.
 *
 * ## 409 on an already-withdrawn enrollment
 *
 * Not 422: the request is well formed and the enrollment exists. What is wrong is its state, and a
 * second withdrawal would overwrite the first reason with a later one — losing the explanation that
 * was actually given at the time.
 *
 * @extends TransactionalAction<SectionEnrollment>
 */
final class WithdrawEnrollment extends TransactionalAction
{
    protected function handle(mixed ...$arguments): SectionEnrollment
    {
        /** @var SectionEnrollment $enrollment */
        $enrollment = $arguments[0];

        /** @var string $reason */
        $reason = (string) $arguments[1];

        if ($enrollment->status === 'withdrawn') {
            throw new ConflictHttpException(__('students.errors.already_withdrawn'));
        }

        if (trim($reason) === '') {
            throw new ConflictHttpException(__('students.errors.reason_required'));
        }

        $before = $enrollment->only(['status', 'withdrawn_at', 'withdraw_reason']);

        $enrollment->forceFill([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
            'withdraw_reason' => trim($reason),
        ])->saveQuietly();

        // The contract names `enrollment.withdrawn`; letting the trait fire would add
        // `enrollment.updated` beside it for one occurrence.
        app(Auditor::class)->record('enrollment.withdrawn', $enrollment, $before, [
            'status' => 'withdrawn',
            'withdrawn_at' => $enrollment->withdrawn_at?->toDateTimeString(),
            'withdraw_reason' => trim($reason),
        ]);

        return $enrollment->refresh();
    }
}
