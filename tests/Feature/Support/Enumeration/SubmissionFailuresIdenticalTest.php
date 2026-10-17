<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\SupportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;

/*
|--------------------------------------------------------------------------
| T378 — the three verification failures are one answer (FR-119, SC-026, research.md R-10)
|--------------------------------------------------------------------------
|
| The single most important test of the support channel. Three different facts —
|
|   absent      the identifier is on no roster
|   unenrolled  it is on the roster with no enrollment in the current term
|   withdrawn   its only enrollment this term is withdrawn
|
| — must produce the same status, the same body byte for byte, and the same headers. Any difference,
| however small, lets a script walk the identifier space and learn who studies here.
|
| All three requests share one guest session, as one browser's would: the CSRF token in the page is
| per session, and a fresh session per request would differ for reasons unrelated to the identifier.
| Set-Cookie, Date and X-Request-Id are compared by name only — they change on every response of any
| kind. Everything else must match by value; the X-RateLimit-* counters did not, until the public group
| stopped publishing them (HideRateLimitState, Phase 20).
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();

    $current = AcademicTerm::factory()->current()->create();
    $past = AcademicTerm::factory()->closed()->create();

    // unenrolled: on the roster, enrolled only in a past term.
    $unenrolled = Student::factory()->create(['university_id' => '441000002']);
    SectionEnrollment::factory()->create(['student_id' => $unenrolled->id, 'section_id' => Section::factory()->create(['term_id' => $past->id])->id]);

    // withdrawn: on the roster, enrolled this term, withdrawn.
    $withdrawn = Student::factory()->create(['university_id' => '441000003']);
    SectionEnrollment::factory()->withdrawn()->create(['student_id' => $withdrawn->id, 'section_id' => Section::factory()->create(['term_id' => $current->id])->id]);
});

const FAILING_IDENTIFIERS = ['absent' => '441000001', 'unenrolled' => '441000002', 'withdrawn' => '441000003'];

/**
 * @param  TestResponse<Symfony\Component\HttpFoundation\Response>  $response
 * @return array<string, list<string|null>|string>
 */
function comparableHeaders(TestResponse $response): array
{
    $headers = [];

    foreach ($response->headers->all() as $name => $values) {
        $headers[$name] = in_array($name, ['date', 'set-cookie', 'x-request-id'], true)
            ? (string) count($values)
            : $values;
    }

    ksort($headers);

    return $headers;
}

test('a browser gets the same page, status and headers for all three', function (): void {
    // One guest session for all three, as one browser would have.
    $session = $this->get('/support')->getCookie(config('session.cookie'));

    $responses = [];

    foreach (FAILING_IDENTIFIERS as $case => $id) {
        $responses[$case] = $this->withCookie(config('session.cookie'), (string) $session?->getValue())
            ->post('/support', supportSubmission(['university_id' => $id]));
    }

    foreach ($responses as $case => $response) {
        $response->assertStatus(422);
    }

    expect($responses['unenrolled']->getContent())->toBe($responses['absent']->getContent())
        ->and($responses['withdrawn']->getContent())->toBe($responses['absent']->getContent())
        ->and(comparableHeaders($responses['unenrolled']))->toBe(comparableHeaders($responses['absent']))
        ->and(comparableHeaders($responses['withdrawn']))->toBe(comparableHeaders($responses['absent']))
        ->and((string) $responses['absent']->getContent())->toContain(__('support.failures.submission'));

    foreach (FAILING_IDENTIFIERS as $id) {
        expect((string) $responses['absent']->getContent())->not->toContain($id);
    }
});

test('a JSON client gets the same body, status and headers for all three', function (): void {
    $responses = [];

    foreach (FAILING_IDENTIFIERS as $case => $id) {
        $responses[$case] = $this->postJson('/support', supportSubmission(['university_id' => $id]));
    }

    foreach ($responses as $response) {
        $response->assertStatus(422)->assertExactJson(['message' => __('support.failures.submission')]);
    }

    expect(comparableHeaders($responses['unenrolled']))->toBe(comparableHeaders($responses['absent']))
        ->and(comparableHeaders($responses['withdrawn']))->toBe(comparableHeaders($responses['absent']))
        ->and(array_keys(comparableHeaders($responses['absent'])))->not->toContain('x-ratelimit-remaining');
});

test('none of the three leaves a trace a later request could read', function (): void {
    $before = [
        'requests' => SupportRequest::query()->count(),
        'audit' => DB::table('audit_logs')->count(),
    ];

    foreach (FAILING_IDENTIFIERS as $id) {
        $this->postJson('/support', supportSubmission(['university_id' => $id]));
    }

    // No row, no audit entry: a count anyone can observe later does not move for any of the three.
    expect(SupportRequest::query()->count())->toBe($before['requests'])
        ->and(DB::table('audit_logs')->count())->toBe($before['audit']);
});

test('an enrolled student is the one case that differs — the control that the test can see a difference', function (): void {
    enrolledStudent('441000009');

    $this->postJson('/support', supportSubmission(['university_id' => '441000009']))->assertCreated();
});
