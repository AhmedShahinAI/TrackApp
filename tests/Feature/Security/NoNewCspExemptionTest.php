<?php

declare(strict_types=1);

use App\Modules\Shared\Http\Middleware\ContentSecurityPolicy;

/*
|--------------------------------------------------------------------------
| T359 — the student support channel adds no Content-Security-Policy exemption
|--------------------------------------------------------------------------
|
| Amendment 2 is the platform's first public form, and the easy way to protect a public form is a
| hosted CAPTCHA — which needs its origin in script-src, frame-src and connect-src. R-08 rejected
| that, and T358 rejected even the self-hosted ALTCHA widget because its blob:/data: workers would
| need `worker-src blob:`. This pins the policy exactly as 001 left it, so any later exemption is a
| deliberate, reviewed change to this file rather than a line slipped into the middleware.
|
| Pinned in full, not by "contains 'self'": a single added source is the regression this exists to
| catch, and a containment check would pass it.
|
*/

/** The policy 001 shipped, directive for directive, outside `npm run dev`. */
const POLICY_AS_SHIPPED = [
    'default-src' => "'self'",
    'script-src' => "'self'",
    'style-src' => "'self'",
    'style-src-elem' => "'self'",
    'style-src-attr' => "'unsafe-inline'",
    'img-src' => "'self' data:",
    'font-src' => "'self'",
    'connect-src' => "'self'",
    'object-src' => "'none'",
    'base-uri' => "'self'",
    'form-action' => "'self'",
    'frame-ancestors' => "'none'",
];

/** @return array<string, string> */
function parsePolicy(string $header): array
{
    $directives = [];

    foreach (array_filter(array_map('trim', explode(';', $header))) as $directive) {
        [$name, $sources] = array_pad(explode(' ', $directive, 2), 2, '');
        $directives[$name] = $sources;
    }

    return $directives;
}

test('the policy is exactly the one 001 shipped: no source added, no directive added', function (): void {
    expect(parsePolicy((new ContentSecurityPolicy)->policy()))->toBe(POLICY_AS_SHIPPED);
});

test('no worker-src or frame-src exists to widen — workers stay on script-src \'self\'', function (): void {
    $policy = parsePolicy((new ContentSecurityPolicy)->policy());

    expect($policy)->not->toHaveKey('worker-src')
        ->and($policy)->not->toHaveKey('frame-src')
        ->and($policy)->not->toHaveKey('child-src');
});

test('a route on the public group — the group routes/public.php is loaded with — carries that same policy', function (): void {
    // routes/public.php is empty until Phase 21, so a probe is registered on the very group the
    // file is loaded with (bootstrap/app.php). Any route there inherits this; a group that dropped
    // or reordered the CSP middleware would fail here, not on /support in production.
    Illuminate\Support\Facades\Route::middleware('public')->get('/__probe/public-csp', fn () => 'ok');

    // The group's flood floor reads the `public_throttle` setting (Phase 19 review), and settings are
    // part of a working deployment, not optional.
    (new Database\Seeders\SettingSeeder)->run();

    $response = $this->get('/__probe/public-csp')->assertOk();

    expect(parsePolicy((string) $response->headers->get('Content-Security-Policy')))->toBe(POLICY_AS_SHIPPED)
        ->and($response->headers->get('X-Frame-Options'))->toBe('DENY');
});

test('the sign-in page, the other page a stranger reaches, carries it too', function (): void {
    $header = (string) $this->get(route('login'))->assertOk()->headers->get('Content-Security-Policy');

    expect(parsePolicy($header))->toBe(POLICY_AS_SHIPPED);
});
