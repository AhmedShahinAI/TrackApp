<?php

declare(strict_types=1);

use App\Modules\Support\Services\ReferenceGenerator;

/*
|--------------------------------------------------------------------------
| T385 — a thousand references, and none can be derived from another (SC-027, research.md R-11)
|--------------------------------------------------------------------------
|
| The reference is the only credential for the status page, so the properties that matter are the
| ones an attacker holding one reference would exploit:
|
|   • no sequence — the next reference is not this one plus one;
|   • no time correlation — the order references were made in says nothing about their values;
|   • no growing shared prefix — consecutive references do not share a lengthening stem, as a
|     time-ordered scheme's would;
|   • none of I, L, O, U.
|
| Statistical bounds are set far from what a random source produces, so the test does not flake, and
| far below what any ordered scheme produces, so it cannot pass one.
|
*/

/** @return list<string> */
function thousandReferences(): array
{
    $generator = app(ReferenceGenerator::class);
    $references = [];

    for ($i = 0; $i < 1000; $i++) {
        $references[] = $generator->generate();
    }

    return $references;
}

/** The ten random characters as one integer, in base 32 — 50 bits fits a PHP int. */
function referenceValue(string $reference): int
{
    $value = 0;

    foreach (str_split(substr($reference, 0, ReferenceGenerator::RANDOM_LENGTH)) as $char) {
        $value = $value * 32 + (int) strpos(ReferenceGenerator::ALPHABET, $char);
    }

    return $value;
}

test('a thousand references are distinct, well-formed, and free of I, L, O and U', function (): void {
    $references = thousandReferences();

    expect(array_unique($references))->toHaveCount(1000);

    foreach ($references as $reference) {
        expect(strpbrk($reference, 'ILOU'))->toBeFalse()
            ->and(ReferenceGenerator::normalise($reference))->toBe($reference);
    }
});

test('no reference is its predecessor plus a small step', function (): void {
    $values = array_map(referenceValue(...), thousandReferences());

    $close = 0;

    for ($i = 1; $i < count($values); $i++) {
        // Two random 50-bit values within a million of each other: about one chance in five hundred
        // million. A counter, or anything time-ordered, does it on every pair.
        if (abs($values[$i] - $values[$i - 1]) < 1_000_000) {
            $close++;
        }
    }

    expect($close)->toBe(0);
});

test('the order references were made in does not predict their values', function (): void {
    $values = array_map(referenceValue(...), thousandReferences());

    // Spearman's rank correlation between creation order and value. Random: within ±0.1 with
    // overwhelming probability (the standard error at n=1000 is about 0.03). Time-ordered: 1.
    $ranks = $values;
    asort($ranks);
    $rankOf = array_flip(array_keys($ranks));

    $n = count($values);
    $sumSquared = 0;

    foreach (array_keys($values) as $order) {
        $sumSquared += ($order - $rankOf[$order]) ** 2;
    }

    $rho = 1 - (6 * $sumSquared) / ($n * ($n ** 2 - 1));

    expect(abs($rho))->toBeLessThan(0.1);
});

test('consecutive references share no growing prefix', function (): void {
    $references = thousandReferences();

    $prefixes = [];

    for ($i = 1; $i < count($references); $i++) {
        $length = 0;

        while ($length < ReferenceGenerator::LENGTH && $references[$i][$length] === $references[$i - 1][$length]) {
            $length++;
        }

        $prefixes[] = $length;
    }

    // Random: the first characters match one time in 32, so the mean is about 0.03 and four in a row
    // is a one-in-a-million event. A time-ordered scheme shares most of its characters every time.
    expect(array_sum($prefixes) / count($prefixes))->toBeLessThan(0.2)
        ->and(max($prefixes))->toBeLessThan(4);
});
