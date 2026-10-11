<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Models\Setting;
use App\Modules\Settings\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| T208 — what upload refuses, and that it refuses before storing (FR-055)
|--------------------------------------------------------------------------
|
| Four refusals: over the size limit, over the row limit, an unsupported type, and a file whose
| content disagrees with its extension. Each is asserted to leave nothing on the private disk and no
| job behind, because "refused" that still stores the file is the half of FR-055 that matters to
| security.
|
| The row limit is lowered through its setting for the test rather than by writing 50,001 rows: what
| is under test is that the limit is read from `import_max_rows` and enforced, and a test that takes
| a minute to say so would be skipped by the first person in a hurry. The seeded value is asserted
| separately.
|
*/

/**
 * The world, with the private disk faked so "nothing was stored" can be asserted.
 *
 * @return array<string, mixed>
 */
function uploadWorld(): array
{
    Storage::fake('private');

    return importWorld();
}

/**
 * @param  array<string, mixed>  $world
 * @return Illuminate\Testing\TestResponse<Symfony\Component\HttpFoundation\Response>
 */
function uploadExpectingRefusal(array $world, UploadedFile $file): Illuminate\Testing\TestResponse
{
    return test()->actingAs($world['admin'])
        ->post(route('api.v1.imports.store'), ['file' => $file, 'term_id' => $world['term']->id], ['Accept' => 'application/json'])
        ->assertUnprocessable();
}

function nothingWasStored(): void
{
    expect(Storage::disk('private')->allFiles())->toBe([])
        ->and(ImportJob::query()->withoutGlobalScopes()->count())->toBe(0);
}

test('a valid roster is accepted, stored privately, and opens a job in uploaded', function (): void {
    $world = uploadWorld();
    $response = $this->actingAs($world['admin'])->post(route('api.v1.imports.store'), [
        'file' => rosterFile(rosterRows($world, 3)),
        'term_id' => $world['term']->id,
    ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.mode', 'central')
        ->assertJsonPath('data.status', 'uploaded')
        ->assertJsonPath('data.total_rows', 3)
        ->assertJsonPath('data.duplicate_of', null);

    $job = ImportJob::query()->withoutGlobalScopes()->firstOrFail();

    Storage::disk('private')->assertExists($job->file_path);

    // A UUID, not the uploader's filename: a name chosen by the uploader is a path chosen by them.
    expect($job->file_path)->not->toContain('roster')
        ->and($job->original_filename)->toBe('roster.xlsx')
        ->and($job->file_hash)->toHaveLength(64);
});

test('a CSV is accepted too', function (): void {
    $world = uploadWorld();
    $this->actingAs($world['admin'])->post(route('api.v1.imports.store'), [
        'file' => rosterFile(rosterRows($world, 2), 'roster.csv'),
        'term_id' => $world['term']->id,
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.total_rows', 2);
});

test('a file over the size limit is refused, naming the limit', function (): void {
    $world = uploadWorld();
    $response = uploadExpectingRefusal($world, UploadedFile::fake()->create('big.xlsx', 21 * 1024));

    expect($response->json('errors.file.0'))->toBe(__('imports.upload.too_large', ['mb' => 20]));
    nothingWasStored();
});

test('a file over the row limit is refused, naming the limit', function (): void {
    $world = uploadWorld();
    Setting::query()->where('key', Settings::IMPORT_MAX_ROWS)->update(['value' => json_encode(5)]);
    Settings::forget();

    $response = uploadExpectingRefusal($world, rosterFile(rosterRows($world, 6)));

    expect($response->json('errors.file.0'))->toBe(__('imports.upload.too_many_rows', ['rows' => '5']));
    nothingWasStored();
});

test('exactly the row limit is accepted', function (): void {
    $world = uploadWorld();
    Setting::query()->where('key', Settings::IMPORT_MAX_ROWS)->update(['value' => json_encode(5)]);
    Settings::forget();

    $this->actingAs($world['admin'])->post(route('api.v1.imports.store'), [
        'file' => rosterFile(rosterRows($world, 5)),
        'term_id' => $world['term']->id,
    ], ['Accept' => 'application/json'])->assertCreated();
});

test('the seeded limits are the specification\'s: 20 MB and 50,000 rows', function (): void {
    $world = uploadWorld();
    expect(Settings::int(Settings::IMPORT_MAX_FILE_MB))->toBe(20)
        ->and(Settings::int(Settings::IMPORT_MAX_ROWS))->toBe(50000);
});

test('an unsupported type is refused, naming the accepted types', function (): void {
    $world = uploadWorld();
    $response = uploadExpectingRefusal($world, UploadedFile::fake()->create('roster.pdf', 10, 'application/pdf'));

    expect($response->json('errors.file.0'))->toBe(__('imports.upload.bad_type'));
    nothingWasStored();
});

test('an .xlsx whose bytes are an executable is refused', function (): void {
    $world = uploadWorld();
    $path = tempnam(sys_get_temp_dir(), 'exe');
    file_put_contents($path, "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xFF\xFF".str_repeat("\x00", 512));

    $response = uploadExpectingRefusal($world, new UploadedFile($path, 'roster.xlsx', null, null, true));

    expect($response->json('errors.file.0'))->toBe(__('imports.upload.bad_type'));
    nothingWasStored();
});

test('a CSV renamed to .xlsx is refused — the extension is a claim, the bytes are the file', function (): void {
    $world = uploadWorld();
    $csv = rosterFile(rosterRows($world, 2), 'roster.csv');

    uploadExpectingRefusal($world, new UploadedFile($csv->getRealPath(), 'roster.xlsx', null, null, true));
    nothingWasStored();
});

test('a zip that is not a workbook is refused even with the right extension', function (): void {
    $world = uploadWorld();
    $path = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('word/document.xml', '<w:document/>');
    $zip->close();

    uploadExpectingRefusal($world, new UploadedFile($path, 'roster.xlsx', null, null, true));
    nothingWasStored();
});

test('a file with only a header row is accepted, and reported at preview as having no rows', function (): void {
    $world = uploadWorld();
    $job = uploadRoster($world['admin'], rosterFile([]), $world['term']->id);

    expect($job->total_rows)->toBe(0);

    mapAndValidate($world['admin'], $job);

    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))
        ->assertOk()
        ->assertJsonPath('data.summary.total_rows', 0)
        ->assertJsonPath('data.rows', []);

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.preview.no_rows'));
});

test('a browser upload that is refused comes back to the form with the reason', function (): void {
    $world = uploadWorld();
    $this->actingAs($world['admin'])
        ->from(route('imports.create'))
        ->post(route('api.v1.imports.store'), [
            'file' => UploadedFile::fake()->create('roster.pdf', 10, 'application/pdf'),
            'term_id' => $world['term']->id,
        ])
        ->assertRedirect(route('imports.create'))
        ->assertSessionHasErrors(['file' => __('imports.upload.bad_type')]);
});
