<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuthenticateSession, pinned to the session guard.
 *
 * FR-015 needs AuthenticateSession: it stores the password hash on the session and rejects any
 * session carrying a stale one, which is how `logoutOtherDevices` ends other sessions without a
 * per-user session index. Laravel ships it unregistered.
 *
 * The problem is that it resolves `Auth::guard()` — the **default** — and offers no way to name one.
 * Anything that has called `Auth::shouldUse('sanctum')`, which `auth:sanctum` on the /api/v1 stack
 * does, leaves the default resolving Sanctum's RequestGuard, which has no `viaRemember()`. The
 * result is a BadMethodCallException on a browser route.
 *
 * A separate middleware that pinned the guard first did not work: AuthenticateSession appears in
 * Laravel's own middleware priority list and is hoisted ahead of anything not in it, whatever order
 * the route group lists. Subclassing is what actually controls the ordering, because the priority
 * list matches on the exact class name and this one is not in it.
 */
final class AuthenticateWebSession extends AuthenticateSession
{
    public function handle($request, Closure $next): Response
    {
        // Pinned before the parent reads the default. One line, and it removes the ambiguity rather
        // than trusting that nothing reassigns the default earlier in the request.
        Auth::shouldUse('web');

        return parent::handle($request, $next);
    }
}
