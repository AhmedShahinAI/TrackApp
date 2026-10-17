<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;

/*
|--------------------------------------------------------------------------
| T424 — every way a lookup can fail is one answer (FR-135, SC-029)
|--------------------------------------------------------------------------
|
|   missing     a reference that does not exist
|   mismatched  a real reference with another person's University ID
|   anonymised  a real reference whose request has no student any more (R-12)
|   malformed   a reference with a wrong check character
|
| Same status, same bytes, same headers (Set-Cookie, Date and X-Request-Id by name only — they change on
| every response of any kind). If "mismatched" differed from "missing" in any of them, a stolen reference
| would become a way to confirm one student's identity, or to learn that the reference is live.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

/**
 * @param  TestResponse<Symfony\Component\HttpFoundation\Response>  $response
 * @return array<string, list<string|null>|string>
 */
function retrievalHeaders(TestResponse $response): array
{
    $headers = [];

    foreach ($response->headers->all() as $name => $values) {
        $headers[$name] = in_array($name, ['date', 'set-cookie', 'x-request-id'], true) ? (string) count($values) : $values;
    }

    ksort($headers);

    return $headers;
}

/**
 * @return array<string, array{reference: string, university_id: string}>
 */
function retrievalFailures(): array
{
    $student = enrolledStudent('441234567');
    $mine = SupportRequest::factory()->create(['student_id' => $student->id]);

    $anonymised = SupportRequest::factory()->create(['student_id' => enrolledStudent('441234568')->id]);
    $anonymised->forceFill(['student_id' => null, 'anonymised_at' => now()])->saveQuietly();

    $wrongCheck = substr($mine->reference, 0, 10).($mine->reference[10] === 'A' ? 'B' : 'A');

    return [
        'missing' => ['reference' => 'K7Q2M9XVT3'.App\Modules\Support\Services\ReferenceGenerator::checkCharacter('K7Q2M9XVT3'), 'university_id' => '441234567'],
        'mismatched' => ['reference' => $mine->reference, 'university_id' => '441999999'],
        'anonymised' => ['reference' => $anonymised->reference, 'university_id' => '441234568'],
        'malformed' => ['reference' => $wrongCheck, 'university_id' => '441234567'],
    ];
}

test('a browser gets the same page, status and headers for every failure', function (): void {
    $cases = retrievalFailures();
    $session = $this->get('/support/status')->getCookie(config('session.cookie'));

    $responses = [];

    foreach ($cases as $case => $fields) {
        $responses[$case] = $this->withCookie(config('session.cookie'), (string) $session?->getValue())->post('/support/status', $fields);
    }

    foreach ($responses as $case => $response) {
        $response->assertStatus(422);

        expect($response->getContent())->toBe($responses['missing']->getContent(), "$case differs from missing")
            ->and(retrievalHeaders($response))->toBe(retrievalHeaders($responses['missing']), "$case headers differ");
    }

    // Nothing the visitor typed comes back.
    foreach ($cases as $fields) {
        expect((string) $responses['missing']->getContent())->not->toContain($fields['university_id']);
    }
});

test('a JSON client gets the same body, status and headers for every failure', function (): void {
    $responses = [];

    foreach (retrievalFailures() as $case => $fields) {
        $responses[$case] = $this->postJson('/support/status', $fields);
    }

    foreach ($responses as $case => $response) {
        $response->assertStatus(422)->assertExactJson(['message' => __('support.failures.status')]);

        expect(retrievalHeaders($response))->toBe(retrievalHeaders($responses['missing']), "$case headers differ");
    }
});

test('the control: the matching pair is the one case that differs', function (): void {
    $student = enrolledStudent('441234567');
    $mine = SupportRequest::factory()->create(['student_id' => $student->id]);

    $this->postJson('/support/status', ['reference' => $mine->reference, 'university_id' => '441234567'])->assertOk();
});

test('a lookup is padded to the floor, found or not', function (array $fields): void {
    Sleep::fake();
    supportSetting(App\Modules\Settings\Settings::SUPPORT_MIN_RESPONSE_MS, 60000);

    $this->postJson('/support/status', $fields);

    Sleep::assertSleptTimes(1);
})->with([
    'not found' => [['reference' => 'K7Q2M9XVT3A', 'university_id' => '441000001']],
    'malformed' => [['reference' => 'nope', 'university_id' => 'nope']],
]);

test('every failure does the same database work: one statement, the same shape', function (): void {
    $cases = retrievalFailures();
    $shapes = [];

    foreach ($cases as $case => $fields) {
        Illuminate\Support\Facades\DB::flushQueryLog();
        Illuminate\Support\Facades\DB::enableQueryLog();
        $this->postJson('/support/status', $fields)->assertStatus(422);

        // Only the lookup's own statements: the settings and session reads are the same for any request.
        $shapes[$case] = collect(Illuminate\Support\Facades\DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $sql): bool => str_contains($sql, 'support_requests'))
            ->values()
            ->all();
        Illuminate\Support\Facades\DB::disableQueryLog();
    }

    // A malformed reference used to return before touching the database (Phase 22 review).
    expect($shapes['malformed'])->toHaveCount(1)
        ->and($shapes['mismatched'])->toBe($shapes['missing'])
        ->and($shapes['anonymised'])->toBe($shapes['missing'])
        ->and($shapes['malformed'])->toBe($shapes['missing']);
});

test('the pause tops each failure up to the floor, not merely sleeps', function (): void {
    supportSetting(App\Modules\Settings\Settings::SUPPORT_MIN_RESPONSE_MS, 60000);

    foreach (retrievalFailures() as $case => $fields) {
        Sleep::fake();
        $this->postJson('/support/status', $fields)->assertStatus(422);

        // The work took far less than a minute, so the requested pause is the floor less that work.
        Sleep::assertSlept(fn (Carbon\CarbonInterval $pause): bool => $pause->totalMilliseconds > 55000 && $pause->totalMilliseconds <= 60000, 1);
    }
});
