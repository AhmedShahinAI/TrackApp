<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T277 — a strict Content Security Policy, with no third-party host in it
|--------------------------------------------------------------------------
|
| The policy is pinned directive by directive: loosening it — a CDN host, 'unsafe-inline' for
| scripts — is an edit to this file, made where a reviewer will see it.
*/

/** @return array<string, list<string>> */
function cspDirectives(string $header): array
{
    $directives = [];

    foreach (array_filter(array_map('trim', explode(';', $header))) as $directive) {
        $parts = preg_split('/\s+/', $directive) ?: [];
        $directives[(string) array_shift($parts)] = $parts;
    }

    return $directives;
}

test('the policy is exactly the strict one: self only, no eval, no inline script', function (): void {
    $csp = cspDirectives((string) $this->get('/login')->assertOk()->headers->get('Content-Security-Policy'));

    expect($csp)->toBe([
        'default-src' => ["'self'"],
        // No 'unsafe-eval': Alpine runs as its CSP build (security review R-01, now closed).
        'script-src' => ["'self'"],
        'style-src' => ["'self'"],
        'style-src-elem' => ["'self'"],
        'style-src-attr' => ["'unsafe-inline'"],
        'img-src' => ["'self'", 'data:'],
        'font-src' => ["'self'"],
        'connect-src' => ["'self'"],
        'object-src' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'frame-ancestors' => ["'none'"],
    ]);
});

test('no third-party host is exempted — not unpkg, jsdelivr or Google Fonts', function (): void {
    $header = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    foreach (['unpkg.com', 'cdn.jsdelivr.net', 'fonts.googleapis.com', 'fonts.gstatic.com', 'https:', 'http:', '*'] as $source) {
        expect($header)->not->toContain($source);
    }
});

test('the headers that travel with it', function (): void {
    $this->get('/login')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeaderMissing('Strict-Transport-Security'); // HTTPS only
});

test('a refusal page carries the policy too — the middleware wraps the binding that refuses', function (): void {
    seedScreens();

    $response = renderScreen('section', 'ar'); // warm; then a section outside every scope
    $response = test()->get('/sections/999999')->assertNotFound();

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

test('no screen ships an inline script or style element the policy would block', function (string $key): void {
    seedScreens();

    $html = (string) renderScreen($key, 'ar')->assertOk()->getContent();

    // A <script> with no src runs inline code; a <style> element is inline CSS. Both are refused by
    // the policy, so either one here is a screen that is broken in the browser.
    preg_match_all('/<script\b(?![^>]*\bsrc=)[^>]*>/i', $html, $scripts);
    preg_match_all('/<style\b[^>]*>/i', $html, $styles);

    expect($scripts[0])->toBe([])->and($styles[0])->toBe([]);
})->with(array_keys(screens()));

test('the health probe is outside the web group and its policy', function (): void {
    expect($this->getJson('/health')->headers->has('Content-Security-Policy'))->toBeFalse();
});
