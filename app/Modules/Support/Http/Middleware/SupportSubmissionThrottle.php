<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Middleware;

use App\Modules\Support\Services\SupportLimits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * T371 — step 1 of the submission's contractual order of evaluation (contracts/support-public.md):
 * the rate limits, before the challenge, the validation or the roster are touched, so none of those
 * can be used as an oracle by a caller who is over a limit (research.md R-09, FR-126, FR-127).
 *
 * On arrival this counts the **address** and only **checks** the identifier. The identifier is
 * counted after the challenge is verified (`SupportLimits::consumeIdentifier`, called by T391), so a
 * caller with no solved challenge cannot spend a student's limit for them. SupportLimits holds the
 * reasoning, the keys and the atomic counting; this class is only where in the request it happens.
 *
 * ## Where it sits: outside ConstantResponseTime
 *
 * A refusal here returns at once, unpadded. The 429 status already says "refused", so padding it
 * hides nothing — and padding it would let a flood of refused requests each hold a PHP worker for
 * the whole floor, which turns the limiter into the amplifier. T394 registers this before
 * ConstantResponseTime for that reason, and ConstantResponseTime also skips any 429, so a refusal
 * raised later (the identifier count in T391) is equally unpadded and the two cannot be told apart by
 * timing.
 */
final class SupportSubmissionThrottle
{
    public function __construct(private readonly SupportLimits $limits) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->limits->hitAddress($request);
        $this->limits->checkIdentifier($request->input('university_id'));

        return $next($request);
    }
}
