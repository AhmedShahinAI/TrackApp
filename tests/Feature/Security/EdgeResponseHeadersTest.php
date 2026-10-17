<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| SEC-070, SEC-071 — the security headers on every response, not only on pages (security plan G-06)
|--------------------------------------------------------------------------
*/

/** @param  Illuminate\Testing\TestResponse<Symfony\Component\HttpFoundation\Response>  $response */
function assertSecurityHeaders(Illuminate\Testing\TestResponse $response): void
{
    expect($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'")
        ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
}

test('a URL no route matches is a 404 with the full header set', function (): void {
    $response = $this->get('/no/such/page/anywhere')->assertNotFound();

    assertSecurityHeaders($response);
});

test('an unauthenticated 404 writes no audit entry', function (): void {
    $this->get('/no/such/page/anywhere')->assertNotFound();

    expect(DB::table('audit_logs')->where('action', 'access.denied')->count())->toBe(0);
});

test('the liveness and readiness probes carry the headers and are never cached', function (string $path): void {
    $response = $this->get($path);

    assertSecurityHeaders($response);
    expect((string) $response->headers->get('Cache-Control'))->toContain('no-store');
})->with(['/up', '/health']);

test('a page keeps the policy the web group set, once', function (): void {
    $response = $this->get('/login')->assertOk();

    assertSecurityHeaders($response);
    expect($response->headers->all('content-security-policy'))->toHaveCount(1);
});
