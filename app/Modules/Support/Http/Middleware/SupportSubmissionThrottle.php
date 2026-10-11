<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Middleware;

use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\UniversityId;
use App\Modules\Support\Http\SupportLimitReached;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * T371 — two independent limits on submitting a support request, both of which must pass
 * (research.md R-09, FR-126, FR-127, FR-130).
 *
 * | Axis       | Key                                    | Setting                       |
 * |------------|----------------------------------------|-------------------------------|
 * | address    | the originating IP                     | `support_throttle_ip`         |
 * | identifier | the submitted University ID, **hashed** | `support_throttle_identifier` |
 *
 * ## Why two, and why independent
 *
 * An IP limit alone lets a distributed attacker hammer one student's identifier from many addresses.
 * An identifier limit alone lets one address walk thousands of identifiers, one submission each. A
 * single combined limiter — the natural reading of "rate limit the form" — satisfies a test that
 * varies only one of the two and leaves the other attack open. So each axis has its own key, its
 * own count and its own window, and either one refuses on its own.
 *
 * ## Why the identifier is hashed, and keyed
 *
 * A raw University ID must not become a cache key an operator can read (R-09). A plain SHA-256 is
 * not enough: the identifier space is nine digits, and every hash of it can be computed in minutes.
 * An HMAC under the application key cannot be reversed without that key. The identifier is
 * normalised first, as everywhere else (UniversityId::normalise), so Arabic-Indic and Western digits
 * count against the same limit.
 *
 * ## The refusal says neither which limit nor how close
 *
 * One message, one status, for either axis (FR-130). Saying "too many requests for this University
 * ID" confirms the identifier is being processed — an enumeration leak through the error path. The
 * retry time is the later of the exceeded limits, which is true for the caller whichever one it was.
 *
 * Runs first in the submission's middleware (contracts/support-public.md, order of evaluation step
 * 1): a request refused here never reaches the challenge, the validation or the roster, so none of
 * those can be used as an oracle by someone who is over the limit.
 */
final class SupportSubmissionThrottle
{
    public function handle(Request $request, Closure $next): Response
    {
        $limits = $this->limits($request);

        $retryAfter = 0;

        foreach ($limits as [$key, $limit]) {
            if (RateLimiter::tooManyAttempts($key, $limit['max_attempts'])) {
                $retryAfter = max($retryAfter, RateLimiter::availableIn($key));
            }
        }

        if ($retryAfter > 0) {
            throw new SupportLimitReached($retryAfter);
        }

        foreach ($limits as [$key, $limit]) {
            RateLimiter::hit($key, $limit['decay_minutes'] * 60);
        }

        return $next($request);
    }

    /**
     * @return list<array{0: string, 1: array{max_attempts: int, decay_minutes: int}}>
     */
    private function limits(Request $request): array
    {
        $limits = [
            ['support:submit:ip:'.($request->ip() ?? 'unknown'), Settings::limit(Settings::SUPPORT_THROTTLE_IP)],
        ];

        $identifier = $request->input('university_id');
        $normalised = is_string($identifier) ? UniversityId::normalise($identifier) : '';

        // No identifier, no identifier axis: the request fails validation anyway, and a shared bucket
        // for every blank submission would let one script lock out nobody in particular while
        // filling a key. The address axis still counts it.
        if ($normalised !== '') {
            $limits[] = [
                'support:submit:id:'.hash_hmac('sha256', $normalised, (string) config('app.key')),
                Settings::limit(Settings::SUPPORT_THROTTLE_IDENTIFIER),
            ];
        }

        return $limits;
    }
}
