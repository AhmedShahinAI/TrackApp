<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Middleware;

use App\Modules\Support\Services\SupportLimits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * T430 — the status lookup's own limits (FR-131): per address and per reference, counted on arrival before
 * anything is looked up, and refunded by the controller when the lookup succeeds (SupportLimits::hitStatus).
 *
 * Outside ConstantResponseTime for the reason the submission's limit is: a 429 says "refused" whatever its
 * timing, and padding refusals would let a flood of them hold the PHP workers (Phase 19 review).
 */
final class SupportStatusThrottle
{
    public function __construct(private readonly SupportLimits $limits) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->limits->hitStatus($request);

        return $next($request);
    }
}
