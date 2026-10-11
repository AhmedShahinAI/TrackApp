<?php

declare(strict_types=1);

namespace App\Modules\Support\Services\Challenge;

use App\Modules\Settings\Settings;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * T370 — issue a single-use proof-of-work challenge, and verify-and-consume an answer to one
 * (research.md R-08, FR-128, FR-129).
 *
 * ## Fails closed
 *
 * Every path that cannot reach a definite "yes" is a "no". The store unreachable, the setting
 * unreadable, the challenge unknown, expired, already used, forged, or answered wrongly — each
 * returns false from {@see verify()}, and {@see issue()} throws {@see ChallengeUnavailable} rather
 * than hand out a challenge it could not record. A protection that disappears when Redis does is
 * worse than an outage the student can see (R-08).
 *
 * ## Signed, then stored
 *
 * The challenge string is a random id followed by an HMAC of it under the application key. The
 * signature is checked **before** the store is touched, so a flood of invented challenges costs one
 * hash each and never reaches Redis. The store is still the authority: a correctly signed challenge
 * that is not stored — expired, consumed, or never issued by this deployment's store — is refused.
 *
 * ## Single use
 *
 * The record is removed on the first verification attempt, right or wrong. Removal is the race
 * arbiter: two concurrent submissions of one answer can both read the record, but only one `forget`
 * reports that it deleted it, and only that one may pass.
 *
 * The difficulty is stored with the challenge and read back from the store at verification — never
 * from the request, so a client cannot answer an easier puzzle than the one it was set.
 *
 * The store is the default cache store: Redis in production (.env.example), an array in tests.
 */
final class ChallengeService
{
    /** contracts/support-public.md: `expires_in: 600`, ten minutes. */
    public const TTL_SECONDS = 600;

    private const KEY_PREFIX = 'support:challenge:';

    /** 18 random bytes and 18 bytes of MAC, each 24 base64url characters: 48 in all. */
    private const PART_BYTES = 18;

    private const PART_LENGTH = 24;

    public function issue(): IssuedChallenge
    {
        $difficulty = $this->difficulty();

        $id = $this->base64url(random_bytes(self::PART_BYTES));
        $challenge = $id.$this->signature($id);

        try {
            $stored = $this->store()->add(self::KEY_PREFIX.$challenge, ['difficulty' => $difficulty], self::TTL_SECONDS);
        } catch (Throwable $e) {
            Log::warning('Support challenge store unavailable; refusing to issue.', ['exception' => $e::class]);

            throw new ChallengeUnavailable('The challenge store is unavailable.', previous: $e);
        }

        // `add` is false when the key exists — 144 random bits make that a store fault, not a clash.
        if (! $stored) {
            throw new ChallengeUnavailable('The challenge could not be recorded.');
        }

        return new IssuedChallenge($challenge, $difficulty, self::TTL_SECONDS);
    }

    public function verify(mixed $challenge, mixed $nonce): bool
    {
        if (! is_string($challenge) || ! is_string($nonce) || ! $this->isSigned($challenge)) {
            return false;
        }

        try {
            $record = $this->store()->get(self::KEY_PREFIX.$challenge);

            if (! is_array($record) || ! is_int($record['difficulty'] ?? null)) {
                return false;
            }

            // Consumed before the answer is checked, and whatever the answer: one challenge, one try.
            if (! $this->store()->forget(self::KEY_PREFIX.$challenge)) {
                return false;
            }
        } catch (Throwable $e) {
            Log::warning('Support challenge store unavailable; refusing verification.', ['exception' => $e::class]);

            return false;
        }

        return ProofOfWork::satisfies($challenge, $record['difficulty'], $nonce);
    }

    private function difficulty(): int
    {
        try {
            $difficulty = Settings::get(Settings::SUPPORT_CHALLENGE_DIFFICULTY);
        } catch (Throwable $e) {
            throw new ChallengeUnavailable('The challenge difficulty could not be read.', previous: $e);
        }

        // Out of range is a broken setting. Clamping it would quietly issue a puzzle nobody chose —
        // a difficulty of 1 is no protection — so it refuses, and the form fails closed.
        if (! is_int($difficulty) || $difficulty < ProofOfWork::DIFFICULTY_MIN || $difficulty > ProofOfWork::DIFFICULTY_MAX) {
            Log::warning('Ignoring an unusable support challenge difficulty.', ['setting' => Settings::SUPPORT_CHALLENGE_DIFFICULTY]);

            throw new ChallengeUnavailable('The challenge difficulty setting is out of range.');
        }

        return $difficulty;
    }

    private function isSigned(string $challenge): bool
    {
        if (strlen($challenge) !== 2 * self::PART_LENGTH || preg_match(ProofOfWork::CHALLENGE_PATTERN, $challenge) !== 1) {
            return false;
        }

        $id = substr($challenge, 0, self::PART_LENGTH);

        return hash_equals($this->signature($id), substr($challenge, self::PART_LENGTH));
    }

    private function signature(string $id): string
    {
        $key = (string) config('app.key');

        return $this->base64url(substr(hash_hmac('sha256', 'support-challenge|'.$id, $key, true), 0, self::PART_BYTES));
    }

    private function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function store(): Repository
    {
        return Cache::store();
    }
}
