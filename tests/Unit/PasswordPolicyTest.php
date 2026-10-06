<?php

declare(strict_types=1);

use App\Modules\Shared\Rules\PasswordPolicy;

/*
|--------------------------------------------------------------------------
| T113 — the password policy (FR-006)
|--------------------------------------------------------------------------
|
| At least 8 characters, at least one letter, at least one digit. A unit test because the rule is
| pure: it needs no database, no request, and no framework, and the three paths that use it
| (create, change, reset) are tested against the HTTP surface elsewhere.
|
*/

test('a conforming password passes', function (string $candidate): void {
    expect(PasswordPolicy::passes($candidate))->toBeTrue();
})->with([
    'the seeded fixture password' => 'Track2026',
    'exactly eight characters' => 'Abcdefg1',
    'digit first' => '1abcdefg',
    'punctuation is allowed, not required' => 'Abc-def1',
    'long' => 'a-very-long-passphrase-with-1-digit',

    // An Arabic-first platform that refused a password written in Arabic would be absurd. The
    // letter check is \p{L}, not [A-Za-z], for exactly this.
    'arabic letters count as letters' => 'كلمةمرور1',
    'mixed scripts' => 'كلمةPass1',
]);

test('a non-conforming password fails', function (string $candidate): void {
    expect(PasswordPolicy::passes($candidate))->toBeFalse();
})->with([
    'seven characters' => 'Abcdef1',
    'no digit' => 'abcdefghij',
    'no letter' => '1234567890',
    'digits and punctuation only' => '1234-5678',
    'empty' => '',
    'whitespace only' => '        ',

    // Eight characters by byte count but fewer by character count — mb_strlen, not strlen.
    'eight bytes but four arabic characters plus a digit' => 'كلمة1',
]);

test('the minimum length is eight', function (): void {
    expect(PasswordPolicy::MIN_LENGTH)->toBe(8);

    // The boundary, from both sides.
    expect(PasswordPolicy::passes('Abcdefg1'))->toBeTrue()
        ->and(PasswordPolicy::passes('Abcdef1'))->toBeFalse();
});

test('length counts characters, not bytes', function (): void {
    // Eight Arabic characters is 16 bytes. strlen() would pass a four-character password.
    $eightArabicPlusDigit = 'كلمةمرور1';

    expect(mb_strlen($eightArabicPlusDigit))->toBe(9)
        ->and(strlen($eightArabicPlusDigit))->toBeGreaterThan(9)
        ->and(PasswordPolicy::passes($eightArabicPlusDigit))->toBeTrue();

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

    (new PasswordPolicy)->validate('password', 'Track2026', function (string $message) use (&$failures): object {
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
