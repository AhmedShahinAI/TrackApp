<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\UniversityId;
use App\Modules\Support\Http\SupportLimitReached;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * T371 (review) — the two submission limits of research.md R-09, in one place.
 *
 * | Axis       | Key                                       | Setting                       | Counted        |
 * |------------|-------------------------------------------|-------------------------------|----------------|
 * | address    | the originating IP                        | `support_throttle_ip`         | on arrival     |
 * | identifier | HMAC of the normalised University ID      | `support_throttle_identifier` | after the challenge |
 *
 * ## Counted atomically
 *
 * Every count is `RateLimiter::increment`, which is Redis `INCR`, and the refusal compares the count
 * it returned. The first version checked (`tooManyAttempts`) and then counted (`hit`) in two steps,
 * and fifty simultaneous requests all read zero before any of them counted — the limit did not hold
 * under exactly the burst it exists for.
 *
 * ## Why the identifier is counted only after the challenge
 *
 * Counted on arrival, the identifier limit was a lock anyone could put on any student: three POSTs
 * naming a victim's University ID, with no challenge solved at all, and the real student was refused
 * for an hour from any address. So arrival only *checks* it — an identifier already over its limit
 * is refused before anything else runs, as the contract's order of evaluation requires — and
 * {@see consumeIdentifier()} *counts* it once a challenge has been verified and consumed (T391). Each
 * counted attempt then costs a solved proof-of-work. That raises the price of locking a student out
 * from nothing to three solved challenges; it does not remove it, because a limit per identifier is
 * by nature a limit an attacker can spend. R-09 records the trade.
 *
 * The address limit is counted on arrival: it keys on the caller, so spending it locks out only them.
 *
 * ## One Retry-After for both
 *
 * A refusal says when to retry, and the remaining time differs between two counters that started at
 * different moments — so an honest "retry in 13 minutes" from one and "in 41" from the other tells
 * the caller which limit it was, which FR-130 forbids. Every submission refusal therefore carries the
 * longer of the two configured windows: true for the caller whichever limit fired, and identical
 * either way.
 *
 * The identifier is HMAC'd under the application key after UniversityId::normalise — the same
 * normalisation verification uses — so Arabic-Indic and Western digits share one count, and a raw
 * nine-digit identifier, which a plain hash would not hide, never becomes a cache key (R-09).
 */
final class SupportLimits
{
    /**
     * Count the caller's address, and refuse if it is over its limit. On arrival.
     */
    public function hitAddress(Request $request): void
    {
        $limit = Settings::limit(Settings::SUPPORT_THROTTLE_IP);

        $hits = RateLimiter::increment($this->addressKey($request), $limit['decay_minutes'] * 60);

        if ($hits > $limit['max_attempts']) {
            throw new SupportLimitReached($this->retryAfterSeconds());
        }
    }

    /**
     * Refuse an identifier already over its limit, without counting this attempt. On arrival.
     */
    public function checkIdentifier(mixed $universityId): void
    {
        $key = $this->identifierKey($universityId);

        if ($key !== null
            && RateLimiter::attempts($key) >= Settings::limit(Settings::SUPPORT_THROTTLE_IDENTIFIER)['max_attempts']) {
            throw new SupportLimitReached($this->retryAfterSeconds());
        }
    }

    /**
     * Count the identifier, and refuse if this attempt takes it over. After the challenge (T391).
     */
    public function consumeIdentifier(mixed $universityId): void
    {
        $key = $this->identifierKey($universityId);

        if ($key === null) {
            return;
        }

        $limit = Settings::limit(Settings::SUPPORT_THROTTLE_IDENTIFIER);

        if (RateLimiter::increment($key, $limit['decay_minutes'] * 60) > $limit['max_attempts']) {
            throw new SupportLimitReached($this->retryAfterSeconds());
        }
    }

    /**
     * Phase 22 (T426, T430) — status retrieval: two counters, both counted on arrival, both refunded
     * when the lookup finds the request (FR-131; Phase 22 review).
     *
     * | Counter   | Key                                         | Setting                              |
     * |-----------|---------------------------------------------|--------------------------------------|
     * | address   | the caller's address (IPv6 by its /64)      | `support_status_throttle_ip`         |
     * | reference | HMAC of the reference as typed, normalised  | `support_status_throttle_reference`  |
     *
     * The lookup is the guessing surface: someone holding a stolen reference tries University IDs against
     * it until one matches. The address counter alone was beaten by a botnet or an IPv6 /64; the
     * reference counter stops the guessing against one reference however many addresses take part. It
     * counts every reference that is typed — ones that exist and ones that do not alike — so its own
     * refusal cannot say whether a reference is real.
     *
     * Counted on arrival with Redis INCR, so a burst cannot slip past the check (the Phase 19 lesson),
     * and refunded on a match: only failures stay counted, so students sharing a campus address or a
     * student checking their own request again and again are not refused for succeeding.
     *
     * One Retry-After for both, the longer window, so the refusal does not say which counter fired.
     */
    public function hitStatus(Request $request): void
    {
        $address = Settings::limit(Settings::SUPPORT_STATUS_THROTTLE_IP);
        $reference = Settings::limit(Settings::SUPPORT_STATUS_THROTTLE_REFERENCE);
        $retryAfter = 60 * max($address['decay_minutes'], $reference['decay_minutes']);

        $over = RateLimiter::increment($this->statusAddressKey($request), $address['decay_minutes'] * 60) > $address['max_attempts'];
        $over = RateLimiter::increment($this->statusReferenceKey($request), $reference['decay_minutes'] * 60) > $reference['max_attempts'] || $over;

        if ($over) {
            throw new SupportLimitReached($retryAfter);
        }
    }

    /**
     * A lookup that found its request gives back what it counted: success is not guessing.
     */
    public function refundStatus(Request $request): void
    {
        $address = Settings::limit(Settings::SUPPORT_STATUS_THROTTLE_IP);
        $reference = Settings::limit(Settings::SUPPORT_STATUS_THROTTLE_REFERENCE);

        RateLimiter::decrement($this->statusAddressKey($request), $address['decay_minutes'] * 60);
        RateLimiter::decrement($this->statusReferenceKey($request), $reference['decay_minutes'] * 60);
    }

    public function statusAddressKey(Request $request): string
    {
        return 'support:status:ip:'.self::addressBucket($request->ip());
    }

    /**
     * Keyed on the reference as the lookup will read it — normalised, so hyphens or case cannot buy
     * fresh attempts — and on the raw text when it is not a reference at all, so malformed input is
     * counted too. HMAC'd: a reference is a credential and must not sit in a cache key.
     */
    public function statusReferenceKey(Request $request): string
    {
        $typed = $request->input('reference');
        $typed = is_string($typed) ? $typed : '';
        $canonical = ReferenceGenerator::normalise($typed) ?? 'raw|'.mb_strtolower(trim($typed));

        return 'support:status:ref:'.hash_hmac('sha256', 'support-status-ref|'.$canonical, (string) config('app.key'));
    }

    /**
     * The address a limit is counted against. An IPv6 address is counted by its /64 (Phase 22 review):
     * one subscriber is routinely given a whole /64, so keying the full 128 bits gave a single caller
     * 2^64 separate budgets. IPv4 is counted as itself.
     */
    public static function addressBucket(?string $ip): string
    {
        if ($ip === null || $ip === '') {
            return 'unknown';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $packed = inet_pton($ip);

            return $packed === false ? $ip : 'v6:'.bin2hex(substr($packed, 0, 8));
        }

        return $ip;
    }

    public function addressKey(Request $request): string
    {
        return 'support:submit:ip:'.self::addressBucket($request->ip());
    }

    /**
     * Null for no identifier: a blank submission fails validation anyway, and one shared bucket for
     * every blank would be a key filled by scripts and useful to no one. The address axis counts it.
     */
    public function identifierKey(mixed $universityId): ?string
    {
        $normalised = is_string($universityId) ? UniversityId::normalise($universityId) : '';

        return $normalised === ''
            ? null
            : 'support:submit:id:'.hash_hmac('sha256', 'support-throttle|'.$normalised, (string) config('app.key'));
    }

    public function retryAfterSeconds(): int
    {
        return 60 * max(
            Settings::limit(Settings::SUPPORT_THROTTLE_IP)['decay_minutes'],
            Settings::limit(Settings::SUPPORT_THROTTLE_IDENTIFIER)['decay_minutes'],
        );
    }
}
