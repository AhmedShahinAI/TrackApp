<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

/**
 * T389 — the `_issued_at` field: when the form was served, signed so the client cannot choose it.
 *
 * The minimum-elapsed-time guard (contracts/support-public.md) refuses a form submitted sooner than a
 * person could read it and type a description. An unsigned timestamp is one a script simply sets to an
 * hour ago, so the field is `<unix seconds>.<HMAC>` under the application key, with its own purpose
 * prefix so the MAC cannot be confused with the challenge's or the throttle's.
 *
 * ## And a maximum (Phase 20 review)
 *
 * Without one, a script fetched the form once, waited three seconds, and replayed that one stamp on
 * every submission for days — the guard stopped it exactly once. A stamp now also expires with the
 * session it was served in (`session.lifetime`): past that the form's CSRF token is dead anyway, so no
 * person's submission is refused by it, and a harvested stamp lives no longer than the session that
 * carried it. Read from config, not a literal, so the two cannot drift apart.
 */
final class FormStamp
{
    public function issue(): string
    {
        $issuedAt = (string) now()->getTimestamp();

        return $issuedAt.'.'.$this->mac($issuedAt);
    }

    /**
     * Seconds since the stamp was issued, or null for anything that is not a live stamp this
     * deployment issued — missing, malformed, forged, dated in the future, or older than a session.
     */
    public function age(mixed $stamp): ?int
    {
        if (! is_string($stamp) || preg_match('/^(\d{1,12})\.([A-Za-z0-9_-]{43})$/', $stamp, $parts) !== 1) {
            return null;
        }

        if (! hash_equals($this->mac($parts[1]), $parts[2])) {
            return null;
        }

        $age = now()->getTimestamp() - (int) $parts[1];

        return $age >= 0 && $age <= $this->maxAgeSeconds() ? $age : null;
    }

    public function maxAgeSeconds(): int
    {
        return max(1, (int) config('session.lifetime', 120)) * 60;
    }

    private function mac(string $issuedAt): string
    {
        $raw = hash_hmac('sha256', 'support-form-issued|'.$issuedAt, (string) config('app.key'), true);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
