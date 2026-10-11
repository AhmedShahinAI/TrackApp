<?php

declare(strict_types=1);

use App\Models\ImportError;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

/*
|--------------------------------------------------------------------------
| T216 — the error report lists every rejected row, with nothing missing (FR-071, SC-009)
|--------------------------------------------------------------------------
|
| The download is opened and read back, not just status-checked. The set of row numbers in the file
| is compared with the set of rejected staging rows — exact equality, so a row missing from the
| report fails as surely as a row reported twice under different numbers.
|
| "Rejected" includes the rows skipped by the operator's create-or-skip choice: they were not
| imported, and the report is where the operator finds out which ones and why.
|
*/

/**
 * The world and one committed import with five rejected rows, one of them a formula.
 *
 * @return array{0: array<string, mixed>, 1: App\Models\ImportJob}
 */
function reportWorld(): array
{
    $world = importWorld();

    $job = importRoster($world, [
        rosterRow($world),
        rosterRow($world, ['university_id' => '44A10023']),
        rosterRow($world, ['student_name' => '']),
        rosterRow($world, ['branch' => 'الدرعية']),
        rosterRow($world, ['course_code' => 'PHYS110', 'section_number' => '401']),
        rosterRow($world),
        // A formula in a submitted value. It must arrive in the report as text.
        rosterRow($world, ['university_id' => '=cmd|\' /c calc\'!A1']),
    ]);

    return [$world, $job];
}

/**
 * @param  Illuminate\Testing\TestResponse<Symfony\Component\HttpFoundation\Response>  $response
 * @return list<list<string>>
 */
function downloadedReport(Illuminate\Testing\TestResponse $response): array
{
    $path = tempnam(sys_get_temp_dir(), 'report').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    $reader = new Reader;
    $reader->open($path);
    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = array_map('strval', $row->toArray());
        }
    }

    $reader->close();

    return $rows;
}

test('every rejected row is in the report with its number, column, value and reason', function (): void {
    [$world, $job] = reportWorld();
    $response = $this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job));

    $response->assertOk()->assertDownload();

    $rows = downloadedReport($response);
    $header = array_shift($rows);

    expect($header)->toBe([
        __('imports.report.columns.row'), __('imports.report.columns.column'),
        __('imports.report.columns.value'), __('imports.report.columns.reason'),
    ]);

    $rejected = ImportRow::query()->where('import_job_id', $job->id)
        ->whereIn('status', ['error', 'skipped'])->pluck('row_number')->map(fn ($n) => (string) $n)->sort()->values()->all();

    $reported = collect($rows)->pluck(0)->unique()->sort()->values()->all();

    expect($rejected)->toHaveCount(5)
        ->and($reported)->toBe($rejected)
        ->and($job->errors)->toBe(5);

    // Arabic survives the round trip, in the cell it belongs in.
    expect($rows)->toContain(['3', __('imports.targets.university_id'), '44A10023', 'الرقم الجامعي لا يطابق الصيغة (9 أرقام)'])
        ->and($rows)->toContain(['5', __('imports.targets.branch'), 'الدرعية — طلاب', 'فرع غير معروف: الدرعية — طلاب']);

    // The skipped row carries both why it was flagged and that it was skipped.
    $skipped = collect($rows)->filter(fn (array $row): bool => $row[0] === '6')->pluck(3)->all();
    expect($skipped)->toContain('مقرر غير معروف: PHYS110')->toContain(__('imports.reasons.skipped_by_choice'));
});

test('a submitted formula arrives as text, never as a live formula', function (): void {
    [$world, $job] = reportWorld();
    $content = $this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job))->streamedContent();

    $path = tempnam(sys_get_temp_dir(), 'formula').'.xlsx';
    file_put_contents($path, $content);

    $zip = new ZipArchive;
    $zip->open($path);
    $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    expect($sheet)->not->toContain('<f>')
        ->and($sheet)->toContain('=cmd|');
});

test('import_errors is written once, at completion, and a download never rewrites it', function (): void {
    [$world, $job] = reportWorld();

    // Written when the import completed, in the language of the person who ran it.
    $lines = ImportError::query()->where('import_job_id', $job->id)->orderBy('id')->get(['id', 'row_number', 'reason'])->toArray();

    expect($lines)->not->toBeEmpty()
        ->and(collect($lines)->firstWhere('row_number', 3)['reason'] ?? null)->toBe('الرقم الجامعي لا يطابق الصيغة (9 أرقام)');

    // An English reader gets an English file — and the table is exactly as it was. The first version
    // rebuilt it on every GET, so two concurrent downloads interleaved their deletes and inserts.
    $world['admin']->forceFill(['locale' => 'en'])->saveQuietly();
    $rows = downloadedReport($this->actingAs($world['admin']->fresh())->get(route('api.v1.imports.errors.download', $job)));

    expect($rows)->toContain(['3', 'University ID', '44A10023', 'University ID does not match the format (9 digits)'])
        ->and(ImportError::query()->where('import_job_id', $job->id)->orderBy('id')->get(['id', 'row_number', 'reason'])->toArray())->toBe($lines);
});

test('two downloads in different languages each get a complete report', function (): void {
    [$world, $job] = reportWorld();

    $arabic = downloadedReport($this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job)));

    $world['admin']->forceFill(['locale' => 'en'])->saveQuietly();
    $english = downloadedReport($this->actingAs($world['admin']->fresh())->get(route('api.v1.imports.errors.download', $job)));

    // Same rows in the same order, one language each (row 0 is the header, itself translated).
    expect(array_column(array_slice($arabic, 1), 0))->toBe(array_column(array_slice($english, 1), 0))
        ->and($arabic[1][3])->not->toBe($english[1][3]);
});

test('downloading is audited once', function (): void {
    [$world, $job] = reportWorld();
    DB::table('audit_logs')->delete();

    $this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job))->streamedContent();

    expect(DB::table('audit_logs')->where('action', 'import.error_report_downloaded')->count())->toBe(1);
});

test('an import with no rejected rows has no report: 404', function (): void {
    [$world, $job] = reportWorld();
    $clean = importRoster($world, rosterRows($world, 2), [], rosterFile(rosterRows($world, 2), 'clean.xlsx'));

    $this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $clean))->assertNotFound();
});

test('the report screen offers the download only where there is one', function (): void {
    [$world, $job] = reportWorld();
    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.report.done'))
        ->assertSee(route('api.v1.imports.errors.download', $job));
});
