<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case bindings
|--------------------------------------------------------------------------
|
| F-02 fix. Without this file Pest never binds TestCase to the test directories, so `$this` in a
| test is a plain PHPUnit case with no Laravel application behind it. A test calling
| `$this->get('/up')` fails with an undefined method, which is how the gap was found: the
| placeholder tests passed only because `expect(1 + 1)->toBe(2)` needs no application.
|
| Every suite except Unit gets the application. Unit tests are deliberately left without it — a
| unit test that needs a booted framework is an integration test wearing the wrong label, and
| keeping the boundary visible here is cheaper than relitigating it in review.
|
*/

// Architecture reads files and route definitions, so it needs the application but not a database.
// A per-test transaction there would be pure overhead on the suite that runs most often.
pest()->extend(TestCase::class)->in('Architecture');

// The suites that touch data get RefreshDatabase. Scope and Authorization need real rows to prove a
// cross-branch leak cannot happen (constitution principle I); Performance needs them to measure
// anything at all.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Scope', 'Authorization', 'Performance');

// Unit is deliberately left unbound: a unit test that needs a booted framework is an integration
// test wearing the wrong label, and keeping that boundary visible here is cheaper than
// relitigating it in review.

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

/**
 * Assert that two responses are indistinguishable — identical status, identical body, and no header
 * that varies with the reason.
 *
 * Exists for the enumeration suite the support channel requires (FR-120, FR-128, SC-026, SC-029):
 * three submission failures and two retrieval failures must be byte-identical, or roster membership
 * leaks one identifier at a time. A shared expectation keeps every one of those tests asserting the
 * same thing rather than each author deciding what "identical" means.
 */
expect()->extend('toBeIndistinguishableFrom', function (mixed $other) {
    expect($this->value->getStatusCode())->toBe($other->getStatusCode());
    expect($this->value->getContent())->toBe($other->getContent());

    foreach (['content-type', 'cache-control', 'x-reason'] as $header) {
        expect($this->value->headers->get($header))->toBe($other->headers->get($header));
    }

    return $this;
});
