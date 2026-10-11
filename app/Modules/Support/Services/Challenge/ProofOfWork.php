<?php

declare(strict_types=1);

namespace App\Modules\Support\Services\Challenge;

/**
 * T370 — the puzzle, as the server checks it. The exact rule from research.md R-08, which
 * resources/js/pow/puzzle.js solves:
 *
 *   • `challenge` matches `[A-Za-z0-9_-]{16,256}`;
 *   • `difficulty` is an integer from 1 to 32, taken from the stored challenge, never the request;
 *   • `nonce` is canonical decimal — no sign, no leading zero, no exponent — from 0 to 2^53 − 1;
 *   • the answer holds when SHA-256 of the UTF-8 bytes of `challenge ":" nonce` starts with at least
 *     `difficulty` zero bits, counted from the most significant bit across byte boundaries.
 *
 * **Any** nonce meeting the rule is accepted. Requiring the smallest would make the server repeat the
 * client's whole search; checking an answer costs one hash.
 *
 * Pure: no store, no settings, no clock. That is what lets tests/Unit hold it to the same vectors as
 * the JavaScript solver.
 */
final class ProofOfWork
{
    public const DIFFICULTY_MIN = 1;

    public const DIFFICULTY_MAX = 32;

    public const CHALLENGE_PATTERN = '/^[A-Za-z0-9_-]{16,256}$/';

    /** JavaScript's Number.MAX_SAFE_INTEGER — the largest nonce the browser solver can produce. */
    public const NONCE_MAX = 9007199254740991;

    public static function satisfies(string $challenge, int $difficulty, string $nonce): bool
    {
        if (! self::isValidPuzzle($challenge, $difficulty) || ! self::isCanonicalNonce($nonce)) {
            return false;
        }

        return self::leadingZeroBits(hash('sha256', $challenge.':'.$nonce, true)) >= $difficulty;
    }

    public static function isValidPuzzle(string $challenge, int $difficulty): bool
    {
        return $difficulty >= self::DIFFICULTY_MIN
            && $difficulty <= self::DIFFICULTY_MAX
            && preg_match(self::CHALLENGE_PATTERN, $challenge) === 1;
    }

    /**
     * Canonical decimal within range. Canonical matters: `007` and `7` hash differently, and accepting
     * both would let one solution be submitted under several spellings.
     */
    public static function isCanonicalNonce(string $nonce): bool
    {
        if (preg_match('/^(0|[1-9][0-9]{0,15})$/', $nonce) !== 1) {
            return false;
        }

        return (int) $nonce <= self::NONCE_MAX;
    }

    public static function leadingZeroBits(string $bytes): int
    {
        $bits = 0;

        foreach (str_split($bytes) as $byte) {
            $value = ord($byte);

            if ($value === 0) {
                $bits += 8;

                continue;
            }

            // Shift until the top bit is set. Integer arithmetic, not log2: a float logarithm of an
            // exact power of two can land a hair below the integer and floor to the wrong count.
            while (($value & 0x80) === 0) {
                $bits++;
                $value <<= 1;
            }

            return $bits;
        }

        return $bits;
    }
}
