<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Requests;

use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\InSetting;
use App\Modules\Shared\Rules\UniversityId;
use App\Modules\Support\Services\Challenge\ChallengeService;
use App\Modules\Support\Services\FormStamp;
use App\Modules\Support\Services\SupportLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * T389 — POST /support, steps 2 to 4 of the contractual order of evaluation
 * (contracts/support-public.md):
 *
 *   1. rate limits                          — SupportSubmissionThrottle, before this runs
 *   2. challenge, honeypot, elapsed time    — {@see validateResolved()}, before any field rule
 *   3. **format** of `university_id`        — the one specific message (FR-117)
 *   4. the other fields                     — ordinary per-field messages
 *      → the identifier is counted          — only now, once 2–4 have passed
 *   5. verification                         — the action (T390), after this has passed
 *
 * ## Why step 2 runs before the rules
 *
 * Laravel validates a form request's rules first and calls nothing of ours until they pass. Step 2
 * must come before step 3, so this overrides validateResolved(): the guards run, then the parent
 * validates. A script that has not solved a challenge therefore learns nothing — not even whether
 * its identifier is well-formed.
 *
 * ## One refusal for the three guards
 *
 * An unsolved, replayed or expired challenge, a filled honeypot and a form returned too fast all fail
 * with the same message under the same key. Telling a script *which* guard caught it is telling it
 * what to fix. A person whose challenge expired reads "the security check did not complete, try
 * again", which is true for them too.
 *
 * ## The identifier is counted here, once the fields are valid
 *
 * `SupportLimits::consumeIdentifier` counts the University ID after the guards **and** the field rules
 * have passed (research.md R-09; Phase 19 review, moved by the Phase 20 review). Only a request that
 * solved a challenge spends a student's limit — and a real student correcting a missing category or an
 * over-long description does not spend three hourly attempts on typos. Still before verification, and
 * counted the same way whatever the roster says, so the count is no oracle. A malformed identifier is
 * not counted at all, which also keeps scripts from filling the store with keys for identifiers that
 * cannot exist.
 */
final class SubmitSupportRequestRequest extends FormRequest
{
    public const HONEYPOT = '_honeypot';

    public const ISSUED_AT = '_issued_at';

    /** The seeded value, held when the setting is unusable. */
    private const DEFAULT_MIN_ELAPSED_SECONDS = 3;

    /** Above this a minimum age stops a person, not a script: no one reads a form for ten minutes. */
    private const MAX_MIN_ELAPSED_SECONDS = 600;

    public function authorize(): bool
    {
        // A public form: there is no one to authorize. The limits, the challenge and verification are
        // the controls (FR-124).
        return true;
    }

    public function validateResolved(): void
    {
        $this->guardAgainstScripts();

        parent::validateResolved();

        app(SupportLimits::class)->consumeIdentifier($this->input('university_id'));
    }

    /**
     * A browser submits each line break of a textarea as CRLF, while `maxlength` and the live counter
     * count it as one character (Phase 20 review). Left alone, a description the counter accepted
     * failed `max` by one per line. Normalised to LF before the rules run, so the server counts what
     * the student was shown — and what is stored is one convention, not two.
     */
    protected function prepareForValidation(): void
    {
        foreach (['description', 'contact'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => str_replace(["\r\n", "\r"], "\n", $value)]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // bail: a format failure is the one specific message, and it stands alone (FR-117).
            'university_id' => ['bail', 'required', 'string', 'max:50', new UniversityId],
            'category' => ['required', 'string', new InSetting(Settings::SUPPORT_CATEGORIES)],
            'description' => ['required', 'string', 'max:'.Settings::int(Settings::SUPPORT_DESCRIPTION_MAX)],
            'contact' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * `description` arrives trimmed by the global middleware, so a description of spaces is empty
     * and `required` refuses it (T377).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'university_id' => __('support.form.university_id'),
            'category' => __('support.form.category'),
            'description' => __('support.form.description'),
            'contact' => __('support.form.contact'),
        ];
    }

    /**
     * The `support_min_elapsed_seconds` setting, range-checked (Phase 20 review).
     *
     * A cast alone read `-1`, `''` or `'three'` as a minimum the guard could never fail — switched off
     * without a word — and a huge value refused every real student. Out of range or not an integer, the
     * documented default holds and the bad row is logged, as ConstantResponseTime does for its floor.
     */
    private function minimumElapsedSeconds(): int
    {
        try {
            $seconds = Settings::get(Settings::SUPPORT_MIN_ELAPSED_SECONDS);
        } catch (Throwable) {
            return self::DEFAULT_MIN_ELAPSED_SECONDS;
        }

        if (! is_int($seconds) || $seconds < 1 || $seconds > self::MAX_MIN_ELAPSED_SECONDS) {
            Log::warning('Ignoring an unusable support minimum form age.', ['setting' => Settings::SUPPORT_MIN_ELAPSED_SECONDS]);

            return self::DEFAULT_MIN_ELAPSED_SECONDS;
        }

        return $seconds;
    }

    private function guardAgainstScripts(): void
    {
        $honeypot = $this->input(self::HONEYPOT);
        $age = app(FormStamp::class)->age($this->input(self::ISSUED_AT));

        // ConvertEmptyStringsToNull has run: an untouched honeypot is null, and anything else —
        // text, an array, a zero — is a filled one.
        $refused = $honeypot !== null
            || $age === null
            || $age < $this->minimumElapsedSeconds();

        // The challenge is consumed whatever the other two say, and checked last so it always is: a
        // bot caught by the honeypot must not keep a live challenge to reuse.
        $solved = app(ChallengeService::class)->verify($this->input('challenge'), $this->input('challenge_solution'));

        if ($refused || ! $solved) {
            throw ValidationException::withMessages(['challenge' => __('support.failures.challenge')])
                ->redirectTo(route('support.create'));
        }
    }
}
