<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

use App\Models\SupportRequest;
use App\Modules\Shared\Rules\UniversityId;
use RuntimeException;

/**
 * T369 — the reference a student keeps to read their request back (research.md R-11, FR-122).
 *
 * Ten characters of Crockford base32 from `random_int` — a CSPRNG, 50 bits — plus one check
 * character. Stored canonical (`K7Q2M9XVT3X`), shown hyphenated (`K7Q2-M9XV-T3X`).
 *
 * ## Never time-ordered
 *
 * ULID, UUIDv7, Snowflake and an offset auto-increment all encode creation order, and most encode
 * creation time. The reference is the only credential for the status page, so one that can be derived
 * from a neighbour's is a disclosure (FR-122, SC-027). Every character here is independent of the
 * clock and of every other reference.
 *
 * ## The alphabet, and why the check is not Crockford's own
 *
 * Crockford base32 drops I, L, O and U: the first three are read as 1, 1 and 0, and U avoids
 * accidental words. Crockford's *check symbol* is mod 37 and needs five extra symbols, one of them
 * `U` — the letter R-11 excludes. So the check here is **Luhn mod 32** over the same alphabet: it
 * catches every single-character substitution and every swap of two adjacent characters but one —
 * `0` with `Z`, Luhn's known blind spot (mod 10 misses `09`/`90` the same way) — which covers the two
 * mistakes a person copying a reference makes, and it needs no symbol outside the 32.
 *
 * A typo then fails {@see normalise()} before any query runs, rather than becoming a lookup that
 * returns the generic "not found" (R-11).
 */
final class ReferenceGenerator
{
    /** Crockford base32: 0–9 and A–Z without I, L, O, U. */
    public const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public const RANDOM_LENGTH = 10;

    /** Canonical length: the random part and the check character. */
    public const LENGTH = self::RANDOM_LENGTH + 1;

    /**
     * At 50 bits a collision will not happen; a loop that could spin forever on a broken random
     * source would be worse than an exception that says so.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * A new reference no stored request holds.
     *
     * The existence check makes a collision a retry rather than an error. Two concurrent requests
     * drawing the same 50 bits between the check and the insert are still stopped by the unique
     * index (T361), which fails that one submission instead of giving two students one reference.
     */
    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $reference = $this->candidate();

            if (! SupportRequest::query()->withoutGlobalScopes()->where('reference', $reference)->exists()) {
                return $reference;
            }
        }

        throw new RuntimeException('Could not draw an unused support reference; check the random source.');
    }

    /**
     * Ten random characters and their check character, with no database involved.
     */
    public function candidate(): string
    {
        $body = '';

        for ($i = 0; $i < self::RANDOM_LENGTH; $i++) {
            $body .= self::ALPHABET[random_int(0, 31)];
        }

        return $body.self::checkCharacter($body);
    }

    /**
     * What a person typed, as the stored form — or null when it cannot be a reference.
     *
     * Case, hyphens and spaces are forgiven, and so are the three confusable letters: Crockford's
     * decoding rule reads I and L as 1 and O as 0. A wrong check character returns null.
     *
     * What an Arabic page puts in the way is forgiven too (Phase 19 review): a reference copied from
     * a right-to-left page carries invisible direction marks (U+200E, U+200F and the other format
     * characters, \p{Cf}), its hyphen may come back as any Unicode dash (\p{Pd}) or its space as a
     * no-break one (\p{Z}), and a student typing on an Arabic keyboard enters ٧ for 7. Each of these
     * made a correct reference read as "not found", which on the status page is the same generic
     * failure as a wrong one — the student could not tell their typing from their request.
     * Arabic-Indic digits fold exactly as UniversityId::normalise folds them.
     */
    public static function normalise(string $typed): ?string
    {
        $stripped = preg_replace('/[\p{Cf}\p{Pd}\p{Z}\s]+/u', '', $typed) ?? '';
        $canonical = strtr(strtoupper(UniversityId::normalise($stripped)), ['I' => '1', 'L' => '1', 'O' => '0']);

        if (strlen($canonical) !== self::LENGTH || strspn($canonical, self::ALPHABET) !== self::LENGTH) {
            return null;
        }

        $body = substr($canonical, 0, self::RANDOM_LENGTH);

        return self::checkCharacter($body) === $canonical[self::RANDOM_LENGTH] ? $canonical : null;
    }

    /**
     * The hyphenated form for display: groups of four from the start.
     */
    public static function format(string $canonical): string
    {
        return implode('-', str_split($canonical, 4));
    }

    /**
     * Luhn mod 32: double every second value from the right, fold each product back into the
     * alphabet, and choose the character that brings the sum to a multiple of 32.
     */
    public static function checkCharacter(string $body): string
    {
        $sum = 0;
        $double = true;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $value = (int) strpos(self::ALPHABET, $body[$i]);

            if ($double) {
                $value *= 2;
                $value = intdiv($value, 32) + ($value % 32);
            }

            $sum += $value;
            $double = ! $double;
        }

        return self::ALPHABET[(32 - ($sum % 32)) % 32];
    }
}
