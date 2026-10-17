<?php

declare(strict_types=1);

use App\Modules\Support\Services\ReferenceGenerator;

/*
|--------------------------------------------------------------------------
| T369 — the reference's shape and its check character (research.md R-11)
|--------------------------------------------------------------------------
|
| No database: candidate(), normalise() and the check are pure. SC-027's statistical test — 1,000
| references, no sequence, no timestamp correlation — is T385's, in Phase 20.
|
*/

test('a candidate is ten Crockford characters and a valid check character', function (): void {
    $generator = new ReferenceGenerator;

    for ($i = 0; $i < 200; $i++) {
        $reference = $generator->candidate();

        expect($reference)->toMatch('/^[0-9A-HJKMNP-TV-Z]{11}$/')
            ->and(ReferenceGenerator::normalise($reference))->toBe($reference);
    }
});

test('the four excluded letters never appear', function (): void {
    expect(ReferenceGenerator::ALPHABET)->toHaveLength(32)
        ->and(strpbrk(ReferenceGenerator::ALPHABET, 'ILOU'))->toBeFalse();
});

test('the one transposition the check misses is exactly 0 and Z', function (): void {
    // Pinned so the docblock's claim is a measured fact: every other adjacent pair is caught.
    $missed = [];
    $alphabet = str_split(ReferenceGenerator::ALPHABET);

    foreach ($alphabet as $a) {
        foreach ($alphabet as $b) {
            if ($a >= $b) {
                continue;
            }

            foreach ([0, 1] as $offset) {
                $body = str_repeat('7', $offset).$a.$b.str_repeat('7', 8 - $offset);
                $swapped = str_repeat('7', $offset).$b.$a.str_repeat('7', 8 - $offset);

                if (ReferenceGenerator::checkCharacter($body) === ReferenceGenerator::checkCharacter($swapped)) {
                    $missed[$a.$b] = true;
                }
            }
        }
    }

    expect(array_keys($missed))->toBe(['0Z']);
});

test('every single-character substitution is caught by the check', function (): void {
    $reference = (new ReferenceGenerator)->candidate();

    for ($position = 0; $position < ReferenceGenerator::LENGTH; $position++) {
        foreach (str_split(ReferenceGenerator::ALPHABET) as $replacement) {
            if ($replacement === $reference[$position]) {
                continue;
            }

            $typo = substr_replace($reference, $replacement, $position, 1);

            expect(ReferenceGenerator::normalise($typo))->toBeNull("$reference → $typo");
        }
    }
});

test('swapping two adjacent different characters is caught, except the 0/Z blind spot', function (): void {
    $generator = new ReferenceGenerator;

    for ($sample = 0; $sample < 50; $sample++) {
        $reference = $generator->candidate();

        for ($i = 0; $i < ReferenceGenerator::LENGTH - 1; $i++) {
            $pair = $reference[$i].$reference[$i + 1];

            // Identical characters swap to themselves; 0 and Z (values 0 and 31) are the one pair
            // Luhn mod 32 cannot tell apart, as 09/90 is for mod 10. Documented on the generator.
            if ($reference[$i] === $reference[$i + 1] || $pair === '0Z' || $pair === 'Z0') {
                continue;
            }

            $swapped = $reference;
            [$swapped[$i], $swapped[$i + 1]] = [$reference[$i + 1], $reference[$i]];

            expect(ReferenceGenerator::normalise($swapped))->toBeNull("$reference → $swapped");
        }
    }
});

test('what a person types is forgiven: case, hyphens, spaces and the confusable letters', function (): void {
    $reference = '0123456789'.ReferenceGenerator::checkCharacter('0123456789');
    $shown = ReferenceGenerator::format($reference);

    expect($shown)->toBe(substr($reference, 0, 4).'-'.substr($reference, 4, 4).'-'.substr($reference, 8))
        ->and(ReferenceGenerator::normalise($shown))->toBe($reference)
        ->and(ReferenceGenerator::normalise(strtolower($shown)))->toBe($reference)
        ->and(ReferenceGenerator::normalise(' '.str_replace('-', ' ', $shown).' '))->toBe($reference)
        // Crockford's decoding: O reads as 0, I and L as 1.
        ->and(ReferenceGenerator::normalise('O'.substr($reference, 1)))->toBe($reference)
        ->and(ReferenceGenerator::normalise('0I'.substr($reference, 2)))->toBe($reference)
        ->and(ReferenceGenerator::normalise('0l'.substr($reference, 2)))->toBe($reference);
});

test('anything else is not a reference', function (string $typed): void {
    expect(ReferenceGenerator::normalise($typed))->toBeNull();
})->with(['', 'K7Q2-M9XV-T3', 'K7Q2M9XVT3XX', 'U123456789A', '٠١٢٣٤٥٦٧٨٩A', '0123456789*']);

test('what an Arabic page puts in the way is forgiven: bidi marks, Unicode dashes, Arabic-Indic digits', function (): void {
    $reference = '0123456789'.ReferenceGenerator::checkCharacter('0123456789');
    $shown = ReferenceGenerator::format($reference);

    $arabicDigits = strtr($shown, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    $persianDigits = strtr($shown, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳']);

    foreach ([
        "\u{200F}{$shown}\u{200E}",                // copied out of an RTL paragraph
        "\u{2066}{$shown}\u{2069}",                // isolate marks around an LTR run
        str_replace('-', "\u{2010}", $shown),       // Unicode hyphen
        str_replace('-', "\u{2013}", $shown),       // en dash
        str_replace('-', "\u{00A0}", $shown),       // no-break space
        $arabicDigits,
        $persianDigits,
    ] as $typed) {
        expect(ReferenceGenerator::normalise($typed))->toBe($reference, bin2hex($typed));
    }
});
