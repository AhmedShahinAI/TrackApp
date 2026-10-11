<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/*
|--------------------------------------------------------------------------
| Phase 16 / US8 — course and staff files, and the six-step walk
|--------------------------------------------------------------------------
*/

/** The course template's headers, as the Arabic template writes them. */
const COURSE_HEADERS = ['رمز المقرر', 'الاسم بالعربية', 'الاسم بالإنجليزية', 'البرامج'];

/** The staff template's headers, likewise. */
const USER_HEADERS = ['الاسم بالعربية', 'الاسم بالإنجليزية', 'البريد الإلكتروني', 'الدور', 'الفروع'];

/**
 * @param  list<string>  $headers
 * @param  list<list<string>>  $rows
 */
function bulkFile(array $headers, array $rows, string $name = 'bulk.xlsx'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'bulk').'.xlsx';
    $writer = new XlsxWriter;
    $writer->openToFile($path);

    $text = static fn (array $values): Row => new Row(array_map(static fn (mixed $v): StringCell => new StringCell((string) $v), $values));
    $writer->addRow($text($headers));

    foreach ($rows as $row) {
        $writer->addRow($text($row));
    }

    $writer->close();

    return new UploadedFile($path, $name, null, null, true);
}

/**
 * @param  list<list<string>>  $rows
 */
function courseFile(array $rows): UploadedFile
{
    return bulkFile(COURSE_HEADERS, $rows, 'courses.xlsx');
}

/**
 * @param  list<list<string>>  $rows
 */
function staffFile(array $rows): UploadedFile
{
    return bulkFile(USER_HEADERS, $rows, 'staff.xlsx');
}

/**
 * Step 1 through the endpoint, as an API caller.
 *
 * @return TestResponse<Symfony\Component\HttpFoundation\Response>
 */
function postBulk(User $actor, string $mode, UploadedFile $file): TestResponse
{
    return test()->actingAs($actor)->post(route('api.v1.imports.'.$mode.'.store'), ['file' => $file], ['Accept' => 'application/json']);
}

/**
 * Upload, map with the suggestion and the given choices, and check.
 *
 * @param  array<string, bool|string>  $options
 */
function checkBulk(User $actor, string $mode, UploadedFile $file, array $options = []): ImportJob
{
    postBulk($actor, $mode, $file)->assertCreated();

    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    test()->actingAs($actor)->putJson(route('api.v1.imports.mapping.update', $job), array_filter([
        'mapping' => $job->file_metadata['suggested_mapping'],
        'options' => $options === [] ? null : $options,
    ], static fn (mixed $v): bool => $v !== null))->assertOk();

    test()->actingAs($actor)->postJson(route('api.v1.imports.validate', $job))->assertAccepted();

    return $job->refresh();
}

/**
 * The whole walk.
 *
 * @param  array<string, bool|string>  $options
 */
function importBulk(User $actor, string $mode, UploadedFile $file, array $options = []): ImportJob
{
    return previewAndCommit($actor, checkBulk($actor, $mode, $file, $options));
}

/**
 * The reasons recorded on one import's rows, by row number.
 *
 * @return array<int, list<string>>
 */
function rowReasons(ImportJob $job): array
{
    return App\Models\ImportRow::query()->where('import_job_id', $job->id)->orderBy('row_number')->get()
        ->mapWithKeys(static fn ($row): array => [$row->row_number => array_column($row->reasons ?? [], 'reason_key')])
        ->all();
}
