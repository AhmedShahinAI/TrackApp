<?php

declare(strict_types=1);

namespace App\Modules\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * T115 — the password policy, as one rule object (FR-006).
 *
 * At least 12 characters, at least one letter, at least one digit.
 *
 * 12, not the PRD's original 8: the owner's decision D-2 (security plan SEC-046, 2026-10-17). With no
 * second factor in v1, length is what the password has to carry. Existing passwords are unaffected
 * until their next change; the rule applies wherever a password is set.
 *
 * One object rather than three copies of a regex, because three paths enforce it — create, change,
 * and reset — and the failure mode of duplication is silent: the reset path accepts a password the
 * create path would have refused, and nobody notices until an audit.
 *
 * "At least one letter" accepts Arabic letters as well as Latin. The design system's own reference
 * check is `/[A-Za-z\u0600-\u06FF]/`, and refusing a password because it is written in the user's
 * own script would be absurd on an Arabic-first platform.
 */
final class PasswordPolicy implements ValidationRule
{
    public const MIN_LENGTH = 12;

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::passes($value)) {
            $fail('auth.errors.policy')->translate();
        }
    }

    /**
     * The policy itself, callable without the validator — the screen-side hint and any console
     * command that sets a password both need the same answer (FR-006: "enforced both at the screen
     * and on the server").
     */
    public static function passes(string $value): bool
    {
        if (mb_strlen($value) < self::MIN_LENGTH) {
            return false;
        }

        // Unicode letter, so Arabic counts. \p{L} rather than an explicit range, which would miss
        // Arabic presentation forms and the extended blocks.
        if (preg_match('/\p{L}/u', $value) !== 1) {
            return false;
        }

        return preg_match('/\d/', $value) === 1;
    }
}
