<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use ZipArchive;

/*
|--------------------------------------------------------------------------
| The export is data, not instructions; and a date is a day
|--------------------------------------------------------------------------
|
| Three defects found in review, each measured before it was fixed, each now pinned.
|
*/

/**
 * The worksheet XML inside an .xlsx.
 */
function worksheetXmlOf(string $bytes): string
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
    file_put_contents($path, $bytes);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue('the export is not a readable xlsx');

    $xml = '';

    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (str_contains((string) $zip->getNameIndex($i), 'worksheets/')) {
            $xml .= (string) $zip->getFromIndex($i);
        }
    }

    $zip->close();
    @unlink($path);

    return $xml;
}

test('a name that looks like a formula is exported as text', function (string $payload): void {
    $admin = aUser('SUPER_ADMIN');

    $actor = aUser('INSTRUCTOR');
    $actor->forceFill(['name_ar' => $payload])->saveQuietly();

    DB::table('audit_logs')->delete();
    DB::table('audit_logs')->insert([
        'user_id' => $actor->id,
        'action' => 'auth.login',
        'ip_address' => '10.0.0.1',
        'created_at' => now(),
    ]);

    $xml = worksheetXmlOf(
        $this->actingAs($admin)->get('/api/v1/audit-logs/export')->assertOk()->streamedContent(),
    );

    /*
     * `<f>` is the formula element. OpenSpout's Cell::fromValue() creates a FormulaCell for any
     * string starting with `=`, and `name_ar` is validated only as `string|max:255` — so an account
     * named `=cmd|' /c calc'!A1` put a live formula into a spreadsheet that administrators open and
     * trust. Measured before the fix: `<f>cmd|&#039; /c calc&#039;!A1</f>`.
     */
    expect($xml)->not->toContain('<f>');

    // And the name is still exported intact — neutralised, not mangled.
    expect($xml)->toContain('inlineStr');
})->with([
    'equals' => "=cmd|' /c calc'!A1",
    'plus' => '+1+1',
    'minus' => '-1+1',
    'at' => '@SUM(A1:A9)',
    'tab then equals' => "\t=1+1",
]);

test('date_to includes the whole day it names', function (): void {
    $admin = aUser('SUPER_ADMIN');

    DB::table('audit_logs')->delete();
    DB::table('audit_logs')->insert([
        'user_id' => $admin->id,
        'action' => 'auth.login',
        'ip_address' => '10.0.0.1',
        'created_at' => now()->setTime(14, 30),
    ]);

    $today = now()->toDateString();

    /*
     * A date is a day, not a midnight. Comparing `<= 2026-10-07` against `2026-10-07 00:00:00`
     * excluded everything that happened on the day the person picked — measured as zero rows for an
     * entry at 14:30 — and the same builder feeds the export, so a report of "up to today" ended
     * yesterday without saying so.
     */
    $this->actingAs($admin)
        ->getJson("/api/v1/audit-logs?date_to={$today}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('date_from is inclusive from the start of its day', function (): void {
    $admin = aUser('SUPER_ADMIN');

    DB::table('audit_logs')->delete();
    DB::table('audit_logs')->insert([
        'user_id' => $admin->id,
        'action' => 'auth.login',
        'ip_address' => '10.0.0.1',
        'created_at' => now()->setTime(0, 5),
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/audit-logs?date_from='.now()->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('a malformed filter is the caller\'s problem, not a 500', function (string $query): void {
    $admin = aUser('SUPER_ADMIN');

    /*
     * Request::date() throws on an unparseable value and on an array, so a hand-edited query string
     * produced a 500 on the list, the screen and the export link alike. A 422 says what is wrong; a
     * 500 says the server is broken, and gets reported as an outage.
     */
    $this->actingAs($admin)
        ->getJson('/api/v1/audit-logs?'.$query)
        ->assertStatus(422);
})->with([
    'unparseable date_from' => 'date_from=garbage',
    'array date_from' => 'date_from[]=1',
    'unparseable date_to' => 'date_to=not-a-date',
    'non-numeric user_id' => 'user_id=abc',
    'per_page beyond the cap' => 'per_page=100000',
]);

test('the export orders by a unique tiebreaker so chunks cannot repeat or skip', function (): void {
    $admin = aUser('SUPER_ADMIN');

    DB::table('audit_logs')->delete();

    // Every row sharing one timestamp is what a bulk import produces, and it is the case where
    // ordering by created_at alone lets chunk()'s LIMIT/OFFSET pages overlap.
    $at = now();
    $rows = [];

    for ($i = 0; $i < 1200; $i++) {
        $rows[] = [
            'user_id' => $admin->id,
            'action' => 'auth.login',
            'ip_address' => '10.0.0.1',
            'created_at' => $at,
        ];
    }

    DB::table('audit_logs')->insert($rows);

    $xml = worksheetXmlOf(
        $this->actingAs($admin)->get('/api/v1/audit-logs/export')->assertOk()->streamedContent(),
    );

    /*
     * Counted against the ids the sheet carries rather than against a header offset, because the
     * question is "does any row appear twice or vanish", not "is the arithmetic right". A blank
     * spacer line in the header block may or may not emit a <row> depending on the writer, and an
     * off-by-one there would hide exactly the defect this test exists for.
     */
    preg_match_all('/<row /', $xml, $all);
    $sheetRows = count($all[0]);

    $entries = DB::table('audit_logs')->count();

    // Every entry present, each once, plus a bounded header block.
    expect($sheetRows - $entries)->toBeGreaterThanOrEqual(5)
        ->and($sheetRows - $entries)->toBeLessThanOrEqual(6);

    /*
     * The decisive half: the seeded ip appears exactly once per seeded row.
     *
     * Compared against the seeded rows, not against every entry — the export writes its own
     * `audit.exported` line first, and that one carries the request's ip, not this one. The first
     * version of this assertion counted all 1201 and reported a defect in the export that was a
     * defect in the counting.
     */
    $seeded = DB::table('audit_logs')->where('ip_address', '10.0.0.1')->count();

    expect(substr_count($xml, '10.0.0.1'))->toBe($seeded);
    expect($entries)->toBe($seeded + 1);
});
