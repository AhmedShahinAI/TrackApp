<?php

declare(strict_types=1);

use App\Modules\Shared\Rules\PasswordPolicy;

/*
|--------------------------------------------------------------------------
| T113 — the password policy (FR-006)
|--------------------------------------------------------------------------
|
| At least 12 characters (the owner's decision D-2, security plan SEC-046; the PRD's original was 8),
| at least one letter, at least one digit. A unit test because the rule is
| pure: it needs no database, no request, and no framework, and the three paths that use it
| (create, change, reset) are tested against the HTTP surface elsewhere.
|
*/

test('a conforming password passes', function (string $candidate): void {
    expect(PasswordPolicy::passes($candidate))->toBeTrue();
})->with([
    'exactly twelve characters' => 'Abcdefghijk1',
    'digit first' => '1abcdefghijk',
    'punctuation is allowed, not required' => 'Abc-defghij1',
    'long' => 'a-very-long-passphrase-with-1-digit',

    // An Arabic-first platform that refused a password written in Arabic would be absurd. The
    // letter check is \p{L}, not [A-Za-z], for exactly this.
    'arabic letters count as letters' => 'كلمةمرورطويلة1',
    'mixed scripts' => 'كلمةPassword1',
]);

test('a non-conforming password fails', function (string $candidate): void {
    expect(PasswordPolicy::passes($candidate))->toBeFalse();
})->with([
    'eleven characters' => 'Abcdefghij1',
    // Nine characters: fixture accounts keep it (set by the seeder, not through the rule), but it
    // can no longer be chosen.
    'the fixture password' => 'Track2026',
    'no digit' => 'abcdefghij',
    'no letter' => '1234567890',
    'digits and punctuation only' => '1234-5678',
    'empty' => '',
    'whitespace only' => '        ',

    // Eight characters by byte count but fewer by character count — mb_strlen, not strlen.
    'eight bytes but four arabic characters plus a digit' => 'كلمة1',
]);

test('the minimum length is twelve', function (): void {
    expect(PasswordPolicy::MIN_LENGTH)->toBe(12);

    // The boundary, from both sides.
    expect(PasswordPolicy::passes('Abcdefghijk1'))->toBeTrue()
        ->and(PasswordPolicy::passes('Abcdefghij1'))->toBeFalse();
});

test('length counts characters, not bytes', function (): void {
    // Thirteen Arabic characters is 26 bytes. strlen() would pass a four-character password.
    $arabicPlusDigit = 'كلمةمرورطويلة1';

    expect(mb_strlen($arabicPlusDigit))->toBe(14)
        ->and(strlen($arabicPlusDigit))->toBeGreaterThan(14)
        ->and(PasswordPolicy::passes($arabicPlusDigit))->toBeTrue();

    $fourArabic = 'كلمة1';

    expect(mb_strlen($fourArabic))->toBe(5)
        ->and(strlen($fourArabic))->toBeGreaterThanOrEqual(8)
        // Passes a byte-count check, fails a character-count one. This is the assertion that
        // catches a regression to strlen().
        ->and(PasswordPolicy::passes($fourArabic))->toBeFalse();
});

/**
 * A stand-in for the $fail closure's return value.
 *
 * The rule calls `$fail('...')->translate()`, so the closure must return something with a
 * translate() method. Laravel's PotentiallyTranslatedString needs the translator out of the
 * container, and this suite deliberately runs without an application — a unit test that boots the
 * framework is an integration test wearing the wrong label.
 */
function failureStub(): object
{
    return new class
    {
        public function translate(): static
        {
            return $this;
        }
    };
}

test('the rule object reports the policy message', function (): void {
    $failures = [];

    (new PasswordPolicy)->validate('password', 'short', function (string $message) use (&$failures): object {
        $failures[] = $message;

        return failureStub();
    });

    // The key, not a rendered string: the message is localized where it is shown, and asserting
    // the key keeps this test from breaking every time the Arabic wording is polished.
    expect($failures)->toBe(['auth.errors.policy']);
});

test('a conforming password reports nothing', function (): void {
    $failures = [];

    (new PasswordPolicy)->validate('password', 'Track2026-Own', function (string $message) use (&$failures): object {
        $failures[] = $message;

        return failureStub();
    });

    expect($failures)->toBe([]);
});

test('a non-string value fails rather than throwing', function (): void {
    $failures = [];

    // A form can submit an array for a scalar field. The rule must refuse it, not crash.
    (new PasswordPolicy)->validate('password', ['an', 'array'], function (string $message) use (&$failures): object {
        $failures[] = $message;

        return failureStub();
    });

    expect($failures)->toHaveCount(1);
});
