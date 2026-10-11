<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * T277 — a strict Content Security Policy on every web response (constitution security floor).
 *
 * ## Nothing from anywhere else
 *
 * Every source is 'self'. Fonts (T058), icons (T059) and scripts (T066) are self-hosted, so the
 * policy names no third-party host at all — not unpkg, not jsdelivr, not Google Fonts. A page that
 * needs one is a page that was built against the plan, and the browser refusing it is how that
 * shows (tests/Feature/Security/ContentSecurityPolicyTest.php pins the policy).
 *
 * ## No eval
 *
 * Alpine runs as its CSP build (`@alpinejs/csp`), which evaluates `x-data` / `x-on` expressions with
 * its own parser instead of `new Function` — so script-src is 'self' alone: no 'unsafe-eval', no
 * 'unsafe-inline'. The price is that templates hold single expressions only; anything with a
 * statement, an arrow function, a template literal or a browser global is a component method in
 * resources/js/components (security review R-01, closed 2026-10-09).
 *
 * ## Styles
 *
 * <style> elements and stylesheets: 'self' only (the [x-cloak] rule lives in the bundle). Style
 * *attributes* are allowed — a dozen views set a width or a grid with `style=""`, and Alpine's
 * x-show writes `display` the same way — through `style-src-attr`, so the allowance covers
 * attributes and never a <style> block.
 *
 * ## Not applied
 *
 * The local-only design-reference routes (`dev.*`): the vendored React kit compiles JSX in the browser
 * and loads its own CDN fonts, which is the reason it is a reference and not the product. They are
 * not registered outside APP_ENV=local.
 */
final class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_starts_with((string) $request->route()?->getName(), 'dev.')) {
            return $response;
        }

        $response->headers->set('Content-Security-Policy', $this->policy());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    public function policy(): string
    {
        // `npm run dev` serves scripts, styles and a websocket from its own origin; only while it runs.
        $dev = Vite::isRunningHot() ? $this->viteOrigins() : [];

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", ...$dev],
            'style-src' => ["'self'", ...$dev],
            'style-src-elem' => ["'self'", ...$dev],
            'style-src-attr' => ["'unsafe-inline'"],
            'img-src' => ["'self'", 'data:'],
            'font-src' => ["'self'"],
            'connect-src' => ["'self'", ...$dev],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        return implode('; ', array_map(
            static fn (string $name, array $sources): string => $name.' '.implode(' ', $sources),
            array_keys($directives),
            $directives,
        ));
    }

    /** @return list<string> */
    private function viteOrigins(): array
    {
        $url = trim((string) @file_get_contents(Vite::hotFile()));
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return [];
        }

        $host = str_contains($parts['host'], ':') && ! str_starts_with($parts['host'], '[') ? "[{$parts['host']}]" : $parts['host'];
        $authority = $host.(isset($parts['port']) ? ':'.$parts['port'] : '');

        return [($parts['scheme'] ?? 'http').'://'.$authority, 'ws://'.$authority];
    }
}
