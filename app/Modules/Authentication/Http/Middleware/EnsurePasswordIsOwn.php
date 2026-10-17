<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEC-047 — the owner's decision D-3 (security plan G-04): a password someone else chose is replaced
 * by its owner before they do anything else.
 *
 * A Super Admin creates every account and resets forgotten passwords (PRD §4.2), and passes the
 * password on out of band. Until its owner changes it, two people know it. CreateUser and
 * ResetPassword set `password_change_required`; ChangePassword clears it.
 *
 * Applied beside `active` and `session.auth` on every authenticated group, web and API, and never on
 * `web` itself: the public support routes share `web`, and nothing there may resolve a user.
 *
 * What stays reachable is exactly what changing the password needs, plus leaving: the form and its
 * submit, the API password endpoint, `me` (a client must be able to learn why it is refused), and
 * sign-out. Everything else redirects to the form (web) or answers 403 with a code (API).
 */
final class EnsurePasswordIsOwn
{
    /** @var list<string> */
    private const ALLOWED_ROUTES = [
        'profile.password.edit',
        'profile.password.update',
        'logout',
        'api.v1.auth.password.update',
        'api.v1.auth.me',
        'api.v1.auth.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->password_change_required || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('auth.password_change.required'),
                'code' => 'password_change_required',
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('profile.password.edit');
    }
}
