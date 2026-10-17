<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers\Public;

use App\Models\SupportRequest;
use App\Models\SupportResponse;
use App\Modules\Shared\Rules\UniversityId;
use App\Modules\Shared\Support\Format;
use App\Modules\Support\Http\Concerns\HonoursRequestedLocale;
use App\Modules\Support\Http\SupportRequestPresenter;
use App\Modules\Support\Services\ReferenceGenerator;
use App\Modules\Support\Services\SupportLimits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * T429 — a student finds out what happened to their request (Amendment 2, US11; contracts/support-public.md
 * `POST /support/status`).
 *
 * ## One query, two keys, one failure
 *
 * The reference **and** the University ID, matched in one statement (FR-132). Looking the reference up
 * first and then comparing the ID would answer "this reference exists" to anyone holding one, by timing
 * if nothing else. Here a reference that does not exist, a reference with someone else's ID, a reference
 * whose request was anonymised (no student, so no ID matches — R-12), and a reference typed with a wrong
 * check character all reach the same `null` and the same response, byte for byte (FR-135, SC-029). The
 * response time is padded (ConstantResponseTime), so the stopwatch agrees.
 *
 * ## What the student sees, and nothing more (FR-134)
 *
 * Their request's reference, category, date, state, the rejection reason where there is one, and each
 * response with its date. Not the assignee, not who wrote a response, not the originating IP, not their
 * own identifier echoed back, and nothing of any other request.
 *
 * ## Not audited (T430)
 *
 * A read of the caller's own request that changes nothing is not a sensitive action, and an entry per
 * poll would carry the identifier into a five-year log — becoming a disclosure surface itself. The limit
 * (SupportStatusThrottle) is the control here.
 */
final class SupportStatusController
{
    use HonoursRequestedLocale;

    /**
     * Matches nothing: no stored reference is empty. A reference that fails its check character is
     * looked up as this, so the malformed case runs the same statement as the others (Phase 22 review)
     * instead of returning before touching the database — a difference the response-time floor would
     * have to absorb.
     */
    private const NO_REFERENCE = '';

    /**
     * GET /support/status — the form.
     */
    public function create(Request $request): View
    {
        $this->honourRequestedLocale($request);

        return view('support.public.status-form');
    }

    /**
     * POST /support/status.
     */
    public function lookup(Request $request, SupportLimits $limits): JsonResponse|Response|RedirectResponse
    {
        // Both are required, and saying so names no record; anything past "present" is the lookup's.
        $validator = Validator::make($request->all(), [
            'reference' => ['required', 'string', 'max:40'],
            'university_id' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }

            /*
             * Back to the form WITHOUT the input (Phase 22 review). Laravel's default flashes it, and the
             * form's inputs read old(): a student who left the reference empty saw their University ID
             * printed back into the page — on what may be a shared computer — and carried in the session.
             */
            return redirect()->route('support.status.form')->withErrors($validator);
        }

        $found = $this->find((string) $request->input('reference'), (string) $request->input('university_id'));

        if ($found === null) {
            return $this->notFound($request);
        }

        // Found: success is not guessing, so it gives back what the limits counted (SupportLimits).
        $limits->refundStatus($request);

        $result = $this->present($found);

        if ($request->expectsJson()) {
            return response()->json(['data' => $result])->header('Cache-Control', 'no-store');
        }

        return response()->view('support.public.status-result', ['result' => $result])->header('Cache-Control', 'no-store');
    }

    private function find(string $typedReference, string $typedUniversityId): ?SupportRequest
    {
        $reference = ReferenceGenerator::normalise($typedReference) ?? self::NO_REFERENCE;
        $universityId = UniversityId::normalise($typedUniversityId);

        return SupportRequest::query()
            ->withoutGlobalScopes()
            ->where('reference', $reference)
            ->whereHas('student', fn (Builder $student) => $student->withoutGlobalScopes()->where('university_id', $universityId))
            ->first();
    }

    /**
     * The one answer to every failure. It carries nothing the visitor sent, so it cannot differ between
     * a reference that does not exist and one with the wrong ID.
     */
    private function notFound(Request $request): JsonResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('support.failures.status')], 422);
        }

        return response()->view('support.public.status-form', ['failed' => true], 422);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SupportRequest $request): array
    {
        $responses = $request->responses()->get(['body', 'created_at']);

        return [
            'reference' => $request->display_reference,
            'category' => SupportRequestPresenter::categoryLabel($request->category),
            'submitted_at' => Format::date($request->created_at),
            'status' => ['value' => $request->status->value, 'label' => __($request->status->labelKey())],
            'rejection_reason' => $request->rejection_reason,
            'responses' => $responses->map(fn (SupportResponse $response): array => [
                'body' => $response->body,
                'at' => Format::dateTime($response->created_at),
            ])->all(),
        ];
    }
}
