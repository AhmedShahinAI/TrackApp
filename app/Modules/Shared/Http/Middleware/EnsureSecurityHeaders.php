<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEC-070, SEC-071 (security plan G-06) — no response leaves without the security headers.
 *
 * ContentSecurityPolicy sits on the `web` group, which is right for every page but misses three kinds
 * of response that never enter that group: a URL no route matches (Laravel renders its 404 outside
 * any group), the framework's `/up`, and the readiness probe `/health`. Those went out with no CSP,
 * no X-Frame-Options and no nosniff — an error page an attacker can frame, for instance.
 *
 * Global and last-resort: it adds the same set only when a response does not already carry a policy,
 * so the `web` group's own middleware stays the source for every page it serves. The local-only
 * component gallery keeps its deliberate exemption. The probes are also marked `no-store`: a cached
 * "healthy" is a lie told later.
 */
final class EnsureSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_starts_with((string) $request->route()?->getName(), 'dev.')) {
            return $response;
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            app(ContentSecurityPolicy::class)->apply($request, $response);
        }

        if ($request->is('up', 'health')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
