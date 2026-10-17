<?php

declare(strict_types=1);

use App\Modules\Settings\Settings;
use Carbon\CarbonInterval;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T379 — every public response takes at least support_min_response_ms (research.md R-10)
|--------------------------------------------------------------------------
|
| Two layers. With the clock faked, every outcome of the submission asks for a pause that tops it up
| to the floor — the wiring. With the clock real, the two verification failures that differ in work
| done (one index lookup against a join) are both measured at or above the floor — the guarantee.
|
| The page views are **not** padded (Phase 20 review): they take no identifier, so there is nothing
| for a stopwatch to learn, and a sleeping worker per page view is how a flood empties the pool.
|
| A refusal by a rate limit is the one response not held (Phase 19 review): its 429 says "refused"
| whatever its timing, and holding it would let a refused flood hold the PHP workers.
|
*/

beforeEach(function (): void {
    supportWorld();
});

test('every outcome of a submission is held to the floor', function (string $method, string $uri, Closure $payload): void {
    Sleep::fake();
    supportSetting(Settings::SUPPORT_MIN_RESPONSE_MS, 60000);
    enrolledStudent('441234567');

    $this->call($method, $uri, $payload());

    Sleep::assertSleptTimes(1);
    Sleep::assertSlept(fn (CarbonInterval $pause): bool => $pause->totalMilliseconds > 50000 && $pause->totalMilliseconds <= 60000);
})->with([
    'a submission accepted' => ['POST', '/support', fn (): array => supportSubmission()],
    'a submission refused by verification' => ['POST', '/support', fn (): array => supportSubmission(['university_id' => '441000001'])],
    'a submission refused by a guard' => ['POST', '/support', fn (): array => supportSubmission(['_honeypot' => 'bot'])],
    'a submission refused by validation' => ['POST', '/support', fn (): array => supportSubmission(['description' => ''])],
]);

test('the page views take no identifier and are not held, so they cannot park workers', function (string $uri): void {
    Sleep::fake();
    supportSetting(Settings::SUPPORT_MIN_RESPONSE_MS, 60000);

    $this->get($uri);

    Sleep::assertNeverSlept();
})->with(['/support', '/support/challenge', '/support/submitted']);

test('the two verification paths, measured, both leave no earlier than the floor', function (): void {
    supportSetting(Settings::SUPPORT_MIN_RESPONSE_MS, 250);

    // Absent: one index lookup. Unenrolled: found, then the join fails.
    App\Models\Student::factory()->create(['university_id' => '441000002']);

    foreach (['441000001', '441000002'] as $id) {
        $payload = supportSubmission(['university_id' => $id]);

        $started = hrtime(true);
        $this->postJson('/support', $payload)->assertStatus(422);
        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        expect($elapsedMs)->toBeGreaterThanOrEqual(250.0, "$id left after {$elapsedMs} ms");
    }
});
