<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers\Public;

use App\Modules\Settings\Settings;
use App\Modules\Support\Actions\SubmitSupportRequest;
use App\Modules\Support\Http\Concerns\HonoursRequestedLocale;
use App\Modules\Support\Http\Requests\SubmitSupportRequestRequest;
use App\Modules\Support\Services\FormStamp;
use App\Modules\Support\Services\ReferenceGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * T391 + T393 — the public support form, its submission, and the confirmation (Amendment 2, US9).
 *
 * No `auth`, no account, no credential (FR-124). The guest session the `web` group starts carries the
 * CSRF token and the one-render flash of the reference; nothing is ever written to it that identifies
 * the student.
 *
 * The order of evaluation is contractual and is spread over three places so each step runs before the
 * next can be reached: the limits in SupportSubmissionThrottle, the guards and field rules in
 * SubmitSupportRequestRequest, and verification here, through the action.
 */
final class SupportRequestController
{
    use HonoursRequestedLocale;

    private const FLASH_REFERENCE = 'support.reference';

    /**
     * GET /support. `?lang=` chooses the language without a sign-in (FR-161) and is remembered on the
     * guest session, as the language switch does.
     */
    public function create(Request $request, FormStamp $stamp): View
    {
        $this->honourRequestedLocale($request);

        return view('support.public.form', [
            'categories' => self::categoryOptions(),
            'descriptionMax' => Settings::int(Settings::SUPPORT_DESCRIPTION_MAX),
            'issuedAt' => $stamp->issue(),
        ]);
    }

    /**
     * POST /support. Steps 1–4 have passed by the time this runs; this is step 5.
     */
    public function store(SubmitSupportRequestRequest $request, SubmitSupportRequest $submit): RedirectResponse|JsonResponse|Response
    {
        /** @var array{university_id: string, category: string, description: string, contact?: string|null} $fields */
        $fields = $request->validated();

        $created = $submit->execute($fields, (string) ($request->ip() ?? ''));

        if ($created === null) {
            return $this->refused($request);
        }

        // A one-render flash, never the URL: the reference is the student's credential for the status
        // page, and a URL lands in history, proxy logs and anything the student shares (T393).
        $request->session()->flash(self::FLASH_REFERENCE, $created->reference);

        return $request->expectsJson()
            ? response()->json(['redirect' => route('support.submitted')], 201)
            : redirect()->route('support.submitted');
    }

    /**
     * GET /support/submitted. Shown once: a refresh, or arriving here without having just submitted,
     * goes back to the form rather than showing an empty confirmation.
     *
     * `no-store`, as the status result is (T460, F-03): the page carries the reference, and Laravel's
     * default `no-cache` still lets a browser keep it on disk, where the next person at a shared lab
     * machine can read it back.
     */
    public function submitted(Request $request): Response|RedirectResponse
    {
        $reference = $request->session()->get(self::FLASH_REFERENCE);

        if (! is_string($reference) || $reference === '') {
            return redirect()->route('support.create');
        }

        return response()
            ->view('support.public.submitted', ['reference' => ReferenceGenerator::format($reference)])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * The one answer to every verification failure (FR-119, SC-026).
     *
     * It depends on nothing the visitor sent: not the identifier, not the category, not the
     * description. Re-rendering the form with what they typed would make the body vary with the
     * input — harmless across the three failure modes for the same input, but one careless `old()`
     * away from echoing the identifier. So the page is the message and a way back, and the cost is
     * that the student types their description again. T397.
     */
    private function refused(Request $request): JsonResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('support.failures.submission')], 422);
        }

        return response()->view('support.public.refused', [], 422);
    }

    /**
     * The categories in the request's language, from the `support_categories` setting (T376).
     *
     * A label missing for this locale falls back to the other language, then to the machine value:
     * a category the Super Admin added with one label still shows words, never a blank option.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function categoryOptions(): array
    {
        $locale = app()->getLocale();
        $other = $locale === 'ar' ? 'en' : 'ar';

        return array_map(static fn (array $option): array => [
            'value' => $option['value'],
            'label' => ($option[$locale] ?? '') !== '' ? $option[$locale] : (($option[$other] ?? '') !== '' ? $option[$other] : $option['value']),
        ], Settings::options(Settings::SUPPORT_CATEGORIES));
    }
}
