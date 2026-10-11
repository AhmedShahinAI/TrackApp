<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Modules\Imports\Modes\CentralMode;
use App\Modules\Imports\Pipeline\ImportReasons;
use Illuminate\Http\UploadedFile;

/*
|--------------------------------------------------------------------------
| T221 — the registration unit's real export, fed to the central mode, fails clearly
|--------------------------------------------------------------------------
|
| `design-system/uploads/filedownloadservlet(21).xlsx` is the file the registration unit actually
| sends: a five-line metadata block, then four data columns. It is not a central roster, and C-09
| gave it its own mode (Phase 15, T325 imports it end to end). This test guards the central mode's
| behaviour when someone uploads it there anyway, which they will:
|
|   - the upload is accepted — it is a perfectly valid xlsx, and refusing it at upload would say
|     "bad file" about a good one;
|   - the mapping is refused with one reason per required target the file cannot supply, each named
|     in the reader's language;
|   - and the operator is pointed at the import the file belongs to, rather than left with a list of
|     eight errors and no idea what to do instead.
|
| Nothing is written at any point.
|
*/

test('the registrar export is refused at mapping, naming each missing target and the right mode', function (): void {
    $world = importWorld();
    $before = CoreCounts::snapshot();

    $source = base_path('design-system/uploads/filedownloadservlet(21).xlsx');
    $copy = tempnam(sys_get_temp_dir(), 'registrar').'.xlsx';
    copy($source, $copy);

    $job = uploadRoster($world['admin'], new UploadedFile($copy, 'filedownloadservlet(21).xlsx', null, null, true), $world['term']->id);

    $suggested = $job->file_metadata['suggested_mapping'];

    $response = $this->actingAs($world['admin'])
        ->putJson(route('api.v1.imports.mapping.update', $job), ['mapping' => $suggested])
        ->assertUnprocessable();

    $errors = $response->json('errors');

    $unsupplied = array_values(array_filter(CentralMode::required(), fn (string $t): bool => $suggested[$t] === null));

    // The file's first row is its metadata block, so almost nothing maps — and every target that
    // does not is named, individually.
    expect(count($unsupplied))->toBeGreaterThanOrEqual(6);

    foreach ($unsupplied as $target) {
        expect($errors['mapping.'.$target][0] ?? null)
            ->toBe(__('imports.mapping.required', ['column' => ImportReasons::column($target)]));
    }

    expect($errors['mapping'][0])->toBe(__('imports.mapping.wrong_shape'))
        ->and($job->refresh()->status)->toBe('uploaded')
        ->and(CoreCounts::snapshot())->toBe($before);
});
