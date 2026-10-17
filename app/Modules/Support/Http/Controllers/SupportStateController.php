<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Models\SupportRequest;
use App\Models\User;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use App\Modules\Support\Actions\ReassignSupportRequest;
use App\Modules\Support\Actions\TransitionSupportRequest;
use App\Modules\Support\Enums\SupportRequestStatus;
use App\Modules\Support\Http\SupportRequestPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * T417 — PATCH …/{reference}/status and PATCH …/{reference}/assignee (contracts/support-staff.md).
 *
 * The routes carry the permissions — `support.respond` for status, `support.assign` for the assignee.
 * Which transitions are legal, and that `closed` additionally needs `support.close`, is
 * TransitionSupportRequest's to decide: one place holds the state machine.
 */
final class SupportStateController
{
    use RespondsToBoth;

    public function status(Request $request, SupportRequest $supportRequest, TransitionSupportRequest $transition): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(SupportRequestStatus::class)],
            // Required for `rejected`, and written for the student, who reads it (FR-149).
            'reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:500'],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $updated = $transition->execute(
            $supportRequest,
            SupportRequestStatus::from($validated['status']),
            $validated['reason'] ?? null,
            $actor,
        );

        return $this->respond($request, ['data' => SupportRequestPresenter::detail($updated->load(SupportRequestPresenter::relations()))]);
    }

    public function assignee(Request $request, SupportRequest $supportRequest, ReassignSupportRequest $reassign): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['present', 'nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $assignee = $validated['assigned_to'] === null ? null : User::query()->findOrFail($validated['assigned_to']);

        $updated = $reassign->execute($supportRequest, $assignee);

        return $this->respond($request, ['data' => SupportRequestPresenter::detail($updated->load(SupportRequestPresenter::relations()))]);
    }
}
