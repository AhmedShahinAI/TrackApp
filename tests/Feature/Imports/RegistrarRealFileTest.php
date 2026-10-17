<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Models\ImportJob;
use App\Models\Section;
use App\Models\SectionEnrollment;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T325 — the registration unit's real export format, end to end (SC-020)
|--------------------------------------------------------------------------
|
| design-system/uploads/filedownloadservlet(21).xlsx is the registrar's real export with its personal
| data redacted (security plan SEC-012): only xl/sharedStrings.xml changed — the 22 University IDs and
| names became synthetic ones (999000001–999000022) — and docProps lost its author. Every other part,
| and so every format quirk this test exists to prove, is the export's own. Never put a real roster
| back: tests/Architecture/NoPersonalDataFixturesTest pins the redacted file. This supersedes T221,
| which only recorded why the central mode cannot read it (that guard still runs, in
| CentralModeRejectsRegistrarFileTest).
|
| The file carries 22 students. tasks.md said 19 — the UI fixture's count, which seeds the first 19
| of them (store.js REAL_FILE.rows.slice(0,19)). This test asserts the file's own number.
|
*/

test('the real file loads end to end: 22 students in the resolved section, with status and sequence', function (): void {
    $world = registrarWorld();

    $upload = postRegistrar($world, realRegistrarFile())->assertCreated();

    expect($upload->json('data.total_rows'))->toBe(22)
        ->and($upload->json('data.resolved.branch_id'))->toBe($world['branch']->id)
        ->and($upload->json('data.resolved.course_id'))->toBe($world['course']->id);

    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    $before = CoreCounts::snapshot();

    $job = mapAndValidate($world['admin'], $job);

    // FR-102: checking writes nothing.
    expect(CoreCounts::snapshot())->toBe($before)
        ->and($job->summary)->toMatchArray(['total_rows' => 22, 'valid' => 22, 'errors' => 0]);

    $job = previewAndCommit($world['admin'], $job);

    $section = Section::query()->withoutGlobalScopes()->sole();
    $enrollments = SectionEnrollment::query()->withoutGlobalScopes()->with('student')->orderBy('roster_sequence')->get();

    expect($job->status)->toBe('completed')
        ->and($job->imported)->toBe(22)
        ->and($section->only(['term_id', 'branch_id', 'course_id', 'program_id', 'section_number', 'instructor_id', 'degree', 'activity']))->toBe([
            'term_id' => $world['term']->id,
            'branch_id' => $world['branch']->id,
            'course_id' => $world['course']->id,
            'program_id' => $world['program']->id,
            'section_number' => '2298',
            'instructor_id' => $world['instructor']->id,
            'degree' => 'intermediate_diploma',
            'activity' => 'lecture',
        ])
        ->and($enrollments)->toHaveCount(22)
        ->and($enrollments->pluck('roster_sequence')->all())->toBe(range(1, 22))
        ->and($enrollments->pluck('academic_status')->unique()->all())->toBe(['regular'])
        ->and($enrollments->pluck('status')->unique()->all())->toBe(['active'])
        ->and($enrollments->first()->student->university_id)->toBe('999000001')
        ->and($enrollments->first()->student->name_ar)->toBe('باسل أنور لطفي راشد جابر اليامي');
});

test('it ends on the report step; its error report is a 404, because the real file has no faulty row', function (): void {
    $world = registrarWorld();
    $job = importRegistrar($world, realRegistrarFile());

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.steps.report'));

    // contracts/imports.md: "404 when there is nothing to report". All 22 rows are valid.
    $this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job))->assertNotFound();
});

test('a registrar file with a faulty row produces a downloadable error report (FR-102)', function (): void {
    $world = registrarWorld();
    $job = importRegistrar($world, registrarFile(rows: [...registrarRows(2), ['3', 'not-an-id', 'طالب', 'منتظم']]));

    expect($job->errors)->toBe(1);

    $this->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job))->assertOk();
});

test('a second upload of the same file creates nothing (FR-067)', function (): void {
    $world = registrarWorld();
    $job = importRegistrar($world, realRegistrarFile());

    $again = postRegistrar($world, realRegistrarFile())->assertCreated();

    expect($again->json('data.duplicate_of'))->toBe($job->id)
        ->and($again->json('data.resolved.section_id'))->toBe(Section::query()->withoutGlobalScopes()->sole()->id);

    $counts = CoreCounts::snapshot();
    $second = previewAndCommit($world['admin'], mapAndValidate($world['admin'], ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail()));

    expect($second->imported)->toBe(0)
        ->and(CoreCounts::snapshot())->toBe($counts);
});

test('the import is audited as committed, like every other mode', function (): void {
    $world = registrarWorld();
    importRegistrar($world, realRegistrarFile());

    expect(DB::table('audit_logs')->where('action', 'import.committed')->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| T467 — every real export the design system holds, not just the one by name
|--------------------------------------------------------------------------
|
| T467 expected v2 to bring two more real registrar exports, with differing column shapes. It brought
| two more FILES: `scratch/roster_sample.xlsx` and `uploads/filedownloadservlet(21)-4d54b7af.xlsx`.
| By SHA-1 they, `scratch/roster.xlsx` and the original were one export under four names (Phase 26).
| Copying them into tests/Fixtures would run one file four times and call it four. The two untracked
| copies held real personal data and were moved out of the repository (security plan SEC-017); the
| two tracked names now hold the redacted export.
|
| So the evidence is collected by content, not by name. Every registrar-shaped .xlsx the vendored design
| system holds is grouped by hash, and each distinct one must load end to end. The pin below says there
| is one today. A genuinely different export, arriving in a later vendoring, fails the pin until it is
| added here and given the world it resolves against. Any other spreadsheet is passed over.
|
*/

/**
 * Every distinct registrar-shaped export under design-system/, keyed by SHA-1, with the names it
 * goes by. The path is the test file's, not base_path(): the data set is built before the app boots.
 *
 * @return array<string, list<string>>
 */
function vendoredRegistrarExports(): array
{
    $byHash = [];

    foreach (Symfony\Component\Finder\Finder::create()->files()->in(dirname(__DIR__, 3).'/design-system')->name('*.xlsx')->sortByName() as $file) {
        // Registrar-shaped only (Phase 26 review): a template or report spreadsheet in a later
        // vendoring is not a roster, and running it as one would fail for the wrong reason.
        if (! looksLikeRegistrarExport($file->getPathname())) {
            continue;
        }

        $byHash[sha1_file($file->getPathname())][] = str_replace('\\', '/', $file->getRelativePathname());
    }

    return $byHash;
}

/**
 * The registrar export's signature (FR-097, FR-098): a metadata block whose first label is «المقر», then
 * a header row carrying «رقم الطالب», within the first rows of the first sheet.
 */
function looksLikeRegistrarExport(string $path): bool
{
    $reader = new OpenSpout\Reader\XLSX\Reader;

    try {
        $reader->open($path);
    } catch (Throwable) {
        return false;
    }

    $firstLabel = null;
    $header = false;

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $index => $row) {
            $cells = array_map(static fn ($cell): string => trim((string) $cell->getValue()), $row->cells);
            $firstLabel ??= $cells[0] ?? '';
            $header = $header || in_array('رقم الطالب', $cells, true);

            if ($header || $index >= 12) {
                break;
            }
        }

        break;
    }

    $reader->close();

    return $firstLabel === 'المقر' && $header;
}

test('the design system holds one distinct registrar export today', function (): void {
    $exports = vendoredRegistrarExports();

    /*
     * The pin is the export's content and the count, not its file names (Phase 26 review). The two
     * names it goes by today are one git object, so they cost the history nothing. But pinning their
     * paths would stop any of the duplicate copies being removed without editing this test. The hash
     * is the redacted export's (SEC-012); a real roster dropped in here fails this pin.
     */
    expect($exports)->toHaveCount(1)
        ->and(array_keys($exports))->toBe(['ef3fa59ed7a8e654d9db1706afe9cfd730294a24'])
        ->and($exports['ef3fa59ed7a8e654d9db1706afe9cfd730294a24'])->toContain('uploads/filedownloadservlet(21).xlsx');
});

test('the export signature tells a roster from any other spreadsheet', function (): void {
    $roster = tempnam(sys_get_temp_dir(), 'sig').'.xlsx';
    copy(base_path('design-system/uploads/filedownloadservlet(21).xlsx'), $roster);

    // A written registrar file has the signature; a plain sheet with the same header but no block does not.
    $written = registrarFile(rows: registrarRows(1))->getPathname();
    $plain = registrarFile(block: null, rows: registrarRows(1))->getPathname();

    expect(looksLikeRegistrarExport($roster))->toBeTrue()
        ->and(looksLikeRegistrarExport($written))->toBeTrue()
        ->and(looksLikeRegistrarExport($plain))->toBeFalse()
        ->and(looksLikeRegistrarExport(__FILE__))->toBeFalse();
});

test('each distinct vendored export resolves its branch, course and section from its own block', function (string $hash, array $names): void {
    $world = registrarWorld();

    $copy = tempnam(sys_get_temp_dir(), 'vendored').'.xlsx';
    copy(base_path('design-system/'.$names[0]), $copy);

    $upload = postRegistrar($world, new Illuminate\Http\UploadedFile($copy, basename($names[0]), null, null, true))->assertCreated();

    expect($upload->json('data.resolved.branch_id'))->toBe($world['branch']->id)
        ->and($upload->json('data.resolved.course_id'))->toBe($world['course']->id)
        ->and($upload->json('data.total_rows'))->toBeGreaterThan(0);

    $job = previewAndCommit($world['admin'], mapAndValidate($world['admin'], ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail()));

    expect($job->status)->toBe('completed')
        ->and($job->errors)->toBe(0)
        ->and(Section::query()->withoutGlobalScopes()->sole()->section_number)->toBe('2298');
})->with(fn () => collect(vendoredRegistrarExports())->map(fn (array $names, string $hash) => [$hash, $names])->values()->all());
