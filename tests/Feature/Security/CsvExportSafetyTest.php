<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Section;
use App\Modules\Shared\Support\CsvSafe;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| SEC-060, SEC-061 — the sections CSV is shown by a spreadsheet, never run, and it is audited
|--------------------------------------------------------------------------
*/

test('every formula trigger is defused, and ordinary text is untouched', function (string $in, string $out): void {
    expect(CsvSafe::cell($in))->toBe($out);
})->with([
    'equals' => ['=HYPERLINK("http://x","y")', "'=HYPERLINK(\"http://x\",\"y\")"],
    'plus' => ['+1+1', "'+1+1"],
    'minus' => ['-2+3', "'-2+3"],
    'at' => ['@SUM(1)', "'@SUM(1)"],
    'tab' => ["\t=1", "'\t=1"],
    'carriage return' => ["\r=1", "'\r=1"],
    'plain' => ['2298', '2298'],
    'arabic' => ['برمجة 2', 'برمجة 2'],
    'empty' => ['', ''],
]);

test('null and enum values become text', function (): void {
    expect(CsvSafe::cell(null))->toBe('')
        ->and(CsvSafe::cell(42))->toBe('42');
});

test('the sections export defuses hostile cells and is audited with its filters', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $course = Course::factory()->create(['code' => '+CMD']);
    Section::factory()->create(['section_number' => '=1+1', 'course_id' => $course->id]);

    $csv = $this->actingAs($admin)->get('/api/v1/sections/export?status=active')->assertOk()->streamedContent();

    expect($csv)->toContain("'=1+1")
        ->and($csv)->toContain("'+CMD")
        ->and(preg_match('/(^|,)=1\+1/m', $csv))->toBe(0);

    $entry = DB::table('audit_logs')->where('action', 'section.exported')->sole();
    $values = json_decode((string) $entry->new_values, true);

    expect($values['filters'])->toBe(['status' => 'active'])
        ->and($values)->toHaveKey('rows');
});
