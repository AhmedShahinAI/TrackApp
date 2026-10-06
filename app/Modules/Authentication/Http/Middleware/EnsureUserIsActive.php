<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Http\Middleware;

use App\Modules\Audit\Services\Auditor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-011, FR-018 — a deactivated account gets no further requests, on any session driver.
 *
 * "Deactivating an account MUST end all of that person's active sessions" cannot be implemented by
 * deleting session rows alone: that only works under the database driver, and this platform runs
 * Redis sessions in production. Purging the store is the belt; this is the braces, and it is the
 * half that actually holds on every driver.
 *
 * Checked on each request rather than cached on the session, because the point is that the change
 * takes effect immediately (FR-027) — a coordinator deactivated mid-session must not finish the
 * page they were on.
 *
 * The cost is one already-loaded attribute read: the session guard resolves the user anyway, so
 * this adds no query.
 */
final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->is_active) {
            return $next($request);
        }

        // Audited before the session goes, so the entry carries the actor it belongs to.
        app(Auditor::class)->record('access.denied', $user, [], ['reason' => 'account_inactive']);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.deactivated')], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('login')->withErrors(['email' => __('auth.deactivated')]);
    }
}
