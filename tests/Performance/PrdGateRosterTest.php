<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Models\ImportRow;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\XLSX\Reader;

/*
|--------------------------------------------------------------------------
| T285 — PRD Phase 1 gate: "a real roster file of at least 5,000 rows imports with an error report"
|--------------------------------------------------------------------------
|
| quickstart.md scenario 5, walked end to end on one file instead of proven in pieces: the roster the
| operator would generate (`track:make-roster-fixture --rows=5000 --with-defects`, built from the
| database's own branches, courses and instructors), uploaded, mapped, validated, previewed and
| committed through the same endpoints the wizard calls.
|
| Asserted on the way: nothing in the core tables moves before the confirmation (FR-060); at least
| 97% of the file imports (SC-006) with no manual repair (SC-005); the error report lists every
| rejected row (SC-009); and the same file uploaded again is flagged and creates nothing (SC-008).
|
|     php artisan test --group=performance tests/Performance/PrdGateRosterTest.php
|
*/

/**
 * The distinct row numbers an error report lists (first column, header skipped), sorted.
 *
 * @return list<string>
 */
function reportedRowNumbers(string $path): array
{
    $reader = new Reader;
    $reader->open($path);
    $numbers = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $i => $row) {
            $cells = $row->toArray();

            if ($i > 1 && isset($cells[0])) {
                $numbers[(string) $cells[0]] = true;
            }
        }
    }

    $reader->close();

    $sorted = array_map('strval', array_keys($numbers));
    sort($sorted);

    return $sorted;
}

test('a 5,000-row roster with every defect imports with an error report', function (): void {
    seedScreens();

    $admin = User::query()->findOrFail(SCREEN_ACCOUNTS['admin']['ar']);
    $path = storage_path('app/fixtures/prd-gate-'.uniqid().'.xlsx');

    $this->artisan('track:make-roster-fixture', ['--rows' => 5000, '--with-defects' => true, '--path' => $path])
        ->assertSuccessful();

    $termId = (int) App\Models\AcademicTerm::query()->orderByDesc('is_current')->value('id');
    $file = new UploadedFile($path, 'roster.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $started = hrtime(true);

    // 1. Upload.
    $job = uploadRoster($admin, $file, $termId);
    expect($job->status)->toBe('uploaded');

    // 2–3. Map and validate. Nothing has reached the core tables.
    $before = CoreCounts::snapshot();
    $job = mapAndValidate($admin, $job);

    expect(in_array($job->status, ['validated', 'ready'], true) || $job->canBePreviewed())->toBeTrue()
        ->and(CoreCounts::snapshot())->toBe($before);

    // 4–5. Preview and confirm.
    $job = previewAndCommit($admin, $job);
    $seconds = (hrtime(true) - $started) / 1e9;

    $total = ImportRow::query()->where('import_job_id', $job->id)->count();
    $rejected = ImportRow::query()->where('import_job_id', $job->id)->whereIn('status', ['error', 'skipped'])->count();
    $share = ($total - $rejected) / max(1, $total);

    expect($job->status)->toBe('completed')
        ->and($total)->toBeGreaterThanOrEqual(5000)
        ->and($rejected)->toBeGreaterThan(0)          // the defects were caught …
        ->and($share)->toBeGreaterThanOrEqual(0.97);  // … and the rest went in (SC-006)

    // 6. The error report names every rejected row.
    $response = $this->actingAs($admin)->get(route('api.v1.imports.errors.download', $job))->assertOk();
    $reportPath = tempnam(sys_get_temp_dir(), 'gate').'.xlsx';
    file_put_contents($reportPath, $response->streamedContent());

    $reported = reportedRowNumbers($reportPath);

    $rejectedRows = ImportRow::query()->where('import_job_id', $job->id)->whereIn('status', ['error', 'skipped'])
        ->pluck('row_number')->map(fn ($n) => (string) $n)->sort()->values()->all();

    expect($reported)->toBe($rejectedRows);

    // 7. The same file again: flagged, and nothing new.
    $afterFirst = CoreCounts::snapshot();
    $again = new UploadedFile($path, 'roster.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    $second = uploadRoster($admin, $again, $termId);

    expect($second->duplicateOf())->toBe($job->id);

    $second = previewAndCommit($admin, mapAndValidate($admin, $second));

    expect($second->status)->toBe('completed')
        ->and($second->imported)->toBe(0)
        ->and(CoreCounts::snapshot())->toBe($afterFirst);

    fwrite(STDERR, sprintf(
        "\n  PRD gate: %s rows, %s rejected (%.1f%% imported), error report %s rows, %.1f s\n",
        number_format($total), number_format($rejected), $share * 100, number_format(count($reported)), $seconds,
    ));

    @unlink($path);
    @unlink($reportPath);
})->group('performance');
