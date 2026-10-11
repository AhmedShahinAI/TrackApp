<?php

declare(strict_types=1);

use App\Modules\Support\Services\Challenge\ProofOfWork;

/*
|--------------------------------------------------------------------------
| T370 — the server's half of the puzzle matches the browser's (research.md R-08)
|--------------------------------------------------------------------------
|
| The shared vector below was produced by resources/js/pow/puzzle.js, and tests/js/pow.test.mjs holds
| the same numbers. If either side changes the encoding, the separator, the bit order or the nonce
| spelling, one of the two files fails — which is the only way a solver and a verifier written in
| two languages stay one rule.
|
*/

const SHARED_CHALLENGE = 'TRACK-shared_vector-0001';

test('the browser solver\'s answer is accepted, at its own difficulty and at every lower one', function (): void {
    // puzzle.js search(SHARED_CHALLENGE, 12) === 6232, whose hash has 16 leading zero bits.
    foreach ([1, 8, 12, 16] as $difficulty) {
        expect(ProofOfWork::satisfies(SHARED_CHALLENGE, $difficulty, '6232'))->toBeTrue("difficulty $difficulty");
    }

    expect(ProofOfWork::satisfies(SHARED_CHALLENGE, 17, '6232'))->toBeFalse()
        // search() returns the first nonce that works, so the one before it does not.
        ->and(ProofOfWork::satisfies(SHARED_CHALLENGE, 12, '6231'))->toBeFalse()
        ->and(ProofOfWork::satisfies(SHARED_CHALLENGE, 8, '84'))->toBeTrue();
});

test('any nonce meeting the rule is accepted, not only the smallest', function (): void {
    // Found by walking past the first answer: the verifier must not care that a smaller one exists.
    $nonce = 6233;

    while (ProofOfWork::leadingZeroBits(hash('sha256', SHARED_CHALLENGE.':'.$nonce, true)) < 8) {
        $nonce++;
    }

    expect(ProofOfWork::satisfies(SHARED_CHALLENGE, 8, (string) $nonce))->toBeTrue();
});

test('leading zero bits are counted across byte boundaries', function (string $hex, int $bits): void {
    expect(ProofOfWork::leadingZeroBits((string) hex2bin($hex)))->toBe($bits);
})->with([
    ['80', 0], ['01', 7], ['0010', 11], ['0000ff', 16], ['08', 4], ['40', 1], [str_repeat('00', 32), 256],
]);

test('only the canonical decimal spelling of a nonce counts', function (string $nonce, bool $canonical): void {
    expect(ProofOfWork::isCanonicalNonce($nonce))->toBe($canonical);
})->with([
    ['0', true], ['7', true], ['9007199254740991', true],
    ['007', false], ['-1', false], ['+7', false], ['7.0', false], ['1e3', false], [' 7', false],
    ['', false], ['9007199254740992', false], ['99999999999999999', false],
]);

test('a puzzle outside the issued shape is never satisfied', function (): void {
    expect(ProofOfWork::satisfies('short', 1, '0'))->toBeFalse()
        ->and(ProofOfWork::satisfies(SHARED_CHALLENGE, 0, '6232'))->toBeFalse()
        ->and(ProofOfWork::satisfies(SHARED_CHALLENGE, 33, '6232'))->toBeFalse()
        ->and(ProofOfWork::satisfies('has space in it!!!', 1, '0'))->toBeFalse();
});
