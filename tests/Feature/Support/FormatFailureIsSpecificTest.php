<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T380 — a malformed University ID gets a specific message, before any roster query (FR-117)
|--------------------------------------------------------------------------
|
| The one deliberate exception to the generic-failure rule. The format is published, so naming it
| reveals nothing — and "your ID should be 9 digits" is the difference between a usable form and a
| frustrating one. What must hold is that this answer comes from the format alone: the roster is not
| consulted, so a malformed identifier cannot be used to learn anything about a well-formed one.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

test('a malformed identifier is told the expected format, and the roster is never queried', function (string $malformed): void {
    $rosterQueries = [];

    DB::listen(function (QueryExecuted $query) use (&$rosterQueries): void {
        if (preg_match('/\b(students|section_enrollments)\b/', $query->sql) === 1) {
            $rosterQueries[] = $query->sql;
        }
    });

    $response = $this->post('/support', supportSubmission(['university_id' => $malformed]));

    $response->assertSessionHasErrors('university_id');

    $message = session('errors')->first('university_id');

    expect($message)->toContain(__('validation.university_id_digits', ['count' => 9]))
        ->and($message)->not->toBe(__('support.failures.submission'))
        ->and($rosterQueries)->toBe([]);
})->with(['12345', 'abcdefghi', '4412345678', '441-234-56']);

test('the format message is the field\'s alone; the other fields are not reported with it', function (): void {
    $this->post('/support', supportSubmission(['university_id' => '12', 'description' => '']))
        ->assertSessionHasErrors(['university_id', 'description']);

    // bail stops further rules on the identifier only; the specific message is one sentence, not a
    // list of everything wrong with it.
    expect(session('errors')->get('university_id'))->toHaveCount(1);
});

test('Arabic-Indic digits are a well-formed identifier, not a format failure', function (): void {
    enrolledStudent('441234567');

    $this->post('/support', supportSubmission(['university_id' => '٤٤١٢٣٤٥٦٧']))
        ->assertRedirect(route('support.submitted'));
});
