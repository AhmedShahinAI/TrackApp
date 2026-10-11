<?php

declare(strict_types=1);

namespace App\Modules\Shared\Rules;

use App\Modules\Settings\Settings;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * T183 — a university id, as the institution defines one (FR-044).
 *
 * ## One rule, two callers
 *
 * Manual entry on the enroll dialog and the import row validator both use this class. They are the
 * same question asked in two places, and the import is the one that matters: a pattern that
 * disagreed between the form and the file would accept a student by hand and reject the same
 * student in a roster of four hundred, with nothing in the error to say why.
 *
 * ## The pattern is configuration, not code
 *
 * `university_id_pattern` is a setting (constitution IV). Majma'ah uses nine digits today; a campus
 * that renumbers, or a second institution, changes a row rather than a regex in a release. The
 * default here matches the seeded value so a database without settings still refuses nonsense
 * rather than accepting everything.
 *
 * ## The message names the format
 *
 * "صيغة غير صحيحة" tells a registrar nothing while they are looking at a spreadsheet cell. The
 * message carries the expected shape, derived from the pattern itself, so it stays true when the
 * setting changes.
 */
final class UniversityId implements ValidationRule
{
    /**
     * The fallback when settings are unavailable.
     *
     * Deliberately the same as the seeded value. A database with no settings row is a broken
     * deployment, and the honest behaviour there is the institution's actual rule — not `.*`, which
     * would quietly accept a name in an id column.
     */
    private const DEFAULT_PATTERN = '^\d{9}$';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->matches($value)) {
            $fail(__('validation.university_id', ['format' => $this->describe()]))->translate();
        }
    }

    /**
     * Does this value look like a university id?
     *
     * Split out from validate() so the logic can be tested without a booted application — the Unit
     * suite deliberately has none, and `__()` needs the translator. The message is asserted in the
     * Feature suite, where an application exists.
     */
    public function matches(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            return false;
        }

        return preg_match(self::compile($this->pattern()), self::normalise((string) $value)) === 1;
    }

    /**
     * Turn a settings-held pattern into a regex that is safe to run.
     *
     * ## The delimiter is the hole
     *
     * The pattern comes from a settings row an administrator can edit, and it was interpolated
     * straight between two slashes. A perfectly reasonable institutional rule such as `^4/\d{8}$`
     * then closes the regex early, the remainder is read as modifiers, and PCRE raises a warning
     * that Laravel promotes to an ErrorException — a 500 on every enrollment and on every row of
     * every import, from a value typed into a settings screen.
     *
     * So the compiled pattern is tested against the empty string first. `preg_match` returns false
     * for anything that will not compile, which covers the broken delimiter, an unbalanced group,
     * an invalid escape and a bad quantifier alike, without having to enumerate them.
     *
     * ## Anchored, always
     *
     * Wrapped in `^(?:...)$`. An administrator who writes `\d{9}` without anchors means "nine
     * digits", not "nine digits somewhere inside anything" — and unanchored, `xx441234567yy` was
     * accepted as a university id. The non-capturing group is what makes this safe against a
     * pattern containing a top-level alternation, where a bare `^...$` would anchor only the first
     * and the last branch.
     *
     * The /u modifier is deliberately absent: normalise() has already folded Arabic-Indic digits to
     * Western ones, so `\d` means what the pattern's author meant.
     */
    private static function compile(string $pattern): string
    {
        $candidate = '/^(?:'.$pattern.')$/';

        try {
            if (@preg_match($candidate, '') !== false) {
                return $candidate;
            }
        } catch (Throwable) {
            // Falls through to the default below. A pattern that cannot compile is a broken
            // setting, and the honest behaviour is the institution's documented rule rather than
            // an exception on a registrar's screen.
        }

        Log::warning('Ignoring an unusable university id pattern from settings.', [
            'pattern' => $pattern,
            'setting' => Settings::UNIVERSITY_ID_PATTERN,
        ]);

        return '/^(?:'.self::DEFAULT_PATTERN.')$/';
    }

    /**
     * Arabic-Indic digits become Western ones, and surrounding space is dropped.
     *
     * ## Why normalise rather than reject
     *
     * `\d` under PCRE's /u modifier matches ٤٤١٢٣٤٥٦٧ as readily as 441234567 — measured, not
     * assumed. Left alone, a registrar typing on an Arabic keyboard would create a *second* student
     * record for a person who already exists under Western digits, which is precisely the duplicate
     * FR-043 exists to prevent, and it would pass every validation on the way in.
     *
     * Rejecting the Arabic form instead would be defensible and worse: the digits are correct, the
     * person is right, and the registrar has no way to know which numerals the field wants. So both
     * forms are accepted and both land on the same stored id.
     *
     * The /u modifier is dropped once normalised, so `\d` means what the pattern's author meant.
     */
    public static function normalise(string $value): string
    {
        $western = strtr(trim($value), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',

            // Extended Arabic-Indic, used in Persian and Urdu keyboards.
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        return $western;
    }

    /**
     * The configured pattern, or the institution's own rule when settings are unreachable.
     */
    public function pattern(): string
    {
        try {
            $configured = Settings::string(Settings::UNIVERSITY_ID_PATTERN);
        } catch (Throwable) {
            return self::DEFAULT_PATTERN;
        }

        return $configured !== '' ? $configured : self::DEFAULT_PATTERN;
    }

    /**
     * A human description of the pattern, for the message.
     *
     * Only the common shape is translated into prose — `^\d{9}$` becomes "9 أرقام". Anything else
     * falls back to showing the pattern, which is less friendly and more honest than inventing a
     * description of a rule this method does not understand.
     *
     * The import row validator names the same format in its row reasons (T227), but stores the parts
     * — digits() or the pattern — rather than this sentence, because a staged row is read later and
     * in whichever language its reader uses.
     */
    private function describe(): string
    {
        $digits = $this->digits();

        return $digits !== null
            ? __('validation.university_id_digits', ['count' => $digits])
            : $this->pattern();
    }

    /**
     * The digit count when the pattern is the common "exactly N digits" shape; otherwise null.
     */
    public function digits(): ?int
    {
        return preg_match('/^\^\\\\d\{(\d+)\}\$$/', $this->pattern(), $m) === 1 ? (int) $m[1] : null;
    }
}
