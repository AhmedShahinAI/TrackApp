<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Modules\Imports\Pipeline\FileValidator;
use App\Modules\Imports\Pipeline\ImportReasons;
use App\Modules\Settings\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Phase 15 review corrections (2026-10-10)
|--------------------------------------------------------------------------
*/

/**
 * Upload, then confirm with the given options, and check — returning the validated job.
 *
 * @param  array<string, mixed>  $world
 * @param  array<string, int>  $options
 */
function registrarChecked(array $world, UploadedFile $file, array $options = []): ImportJob
{
    postRegistrar($world, $file)->assertCreated();

    return mapAndValidate($world['admin'], ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail(), $options);
}

/*
| H1 — a deactivated programme never receives a new section
*/

test('a course whose only programme is deactivated cannot place a new section', function (): void {
    $world = registrarWorld();
    $world['program']->update(['is_active' => false]);

    $job = registrarChecked($world, registrarFile(rows: registrarRows(2)));
    previewAndCommit($world['admin'], $job);

    $reasons = ImportRow::query()->where('import_job_id', $job->id)->get()->flatMap(fn (ImportRow $r) => array_column($r->reasons, 'reason_key'));

    expect($job->summary['errors'])->toBe(2)
        ->and($reasons->unique()->values()->all())->toContain(ImportReasons::UNRESOLVABLE_SECTION)
        ->and(Section::query()->withoutGlobalScopes()->count())->toBe(0);
});

test('with one active and one deactivated programme, the active one is the course\'s single programme', function (): void {
    $world = registrarWorld();
    $retired = Program::factory()->create(['organization_id' => $world['program']->organization_id, 'code' => 'OLD', 'is_active' => false]);
    $world['course']->programs()->attach($retired->id);

    previewAndCommit($world['admin'], registrarChecked($world, registrarFile(rows: registrarRows(1))));

    expect(Section::query()->withoutGlobalScopes()->sole()->program_id)->toBe($world['program']->id);
});

test('the central mode does not count a deactivated programme either', function (): void {
    $world = importWorld();
    $world['program']->update(['is_active' => false]);

    $job = importRoster($world, [rosterRow($world)]);

    expect(Section::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and($job->summary['errors'])->toBe(1);
});

/*
| M1 — the programme choice is one of the course's own active programmes
*/

test('a programme outside the course\'s programmes is refused at the confirmation step', function (): void {
    $world = registrarWorld();
    $unrelated = Program::factory()->create(['organization_id' => $world['program']->organization_id, 'code' => 'HR']);
    postRegistrar($world, registrarFile(rows: registrarRows(1)))->assertCreated();
    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    $this->actingAs($world['admin'])->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
        'options' => ['program_id' => $unrelated->id],
    ])->assertUnprocessable()->assertJsonValidationErrors(['options.program_id']);
});

test('one of the course\'s several programmes, chosen, places the new section', function (): void {
    $world = registrarWorld();
    $second = Program::factory()->create(['organization_id' => $world['program']->organization_id, 'code' => 'APP']);
    $world['course']->programs()->attach($second->id);

    previewAndCommit($world['admin'], registrarChecked($world, registrarFile(rows: registrarRows(1)), ['program_id' => $second->id]));

    expect(Section::query()->withoutGlobalScopes()->sole()->program_id)->toBe($second->id);
});

test('the panel offers only the course\'s active programmes', function (): void {
    $world = registrarWorld();
    Program::factory()->create(['organization_id' => $world['program']->organization_id, 'code' => 'ZZZ', 'name_ar' => 'برنامج غير مرتبط']);
    postRegistrar($world, registrarFile(rows: registrarRows(1)))->assertCreated();

    $this->actingAs($world['admin'])->get(route('imports.show', ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail()))
        ->assertOk()
        ->assertSee('<option value="'.$world['program']->id.'"', false)
        ->assertDontSee('برنامج غير مرتبط');
});

/*
| M3 — a degree or activity the platform cannot apply is said, not swallowed
*/

test('an unknown degree is a notice on the preview and in import.committed, and the new section gets the default', function (): void {
    $world = registrarWorld();
    $job = registrarChecked($world, registrarFile(['الدرجة' => 'الماجستير'] + REGISTRAR_BLOCK, registrarRows(1)));

    expect($job->summary['notices'])->toBe([['key' => 'degree_unknown', 'params' => ['value' => 'الماجستير', 'default' => 'bachelor']]]);

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.registrar.notices.degree_unknown', ['value' => 'الماجستير', 'default' => 'bachelor']));

    previewAndCommit($world['admin'], $job);

    $committed = json_decode((string) DB::table('audit_logs')->where('action', 'import.committed')->value('new_values'), true);

    expect(Section::query()->withoutGlobalScopes()->sole()->degree)->toBe(Settings::values(Settings::SECTION_DEGREES)[0])
        ->and($committed['notices'][0]['key'])->toBe('degree_unknown')
        ->and($committed['metadata']['degree'])->toBe('الماجستير');
});

test('an existing section with a different degree keeps its own, and the operator is told', function (): void {
    $world = registrarWorld();
    Section::factory()->create([
        'term_id' => $world['term']->id, 'branch_id' => $world['branch']->id, 'course_id' => $world['course']->id,
        'program_id' => $world['program']->id, 'section_number' => '2298', 'degree' => 'bachelor',
    ]);

    $job = registrarChecked($world, registrarFile(rows: registrarRows(1)));

    expect($job->summary['notices'])->toBe([['key' => 'degree_differs', 'params' => ['value' => 'الدبلوم المتوسط', 'stored' => 'bachelor']]]);

    previewAndCommit($world['admin'], $job);

    expect(Section::query()->withoutGlobalScopes()->sole()->degree)->toBe('bachelor');
});

/*
| M4 — what a re-import changed, and from what
*/

test('a re-import records the values it replaced on the import row, and writes nothing when they match', function (): void {
    $world = registrarWorld();
    importRegistrar($world, registrarFile(rows: [['3', '441234567', 'طالب', 'معتذر']]));

    $changed = importRegistrar($world, registrarFile(rows: [['4', '441234567', 'طالب', 'منتظم']]));
    $resolved = ImportRow::query()->where('import_job_id', $changed->id)->sole()->resolved;

    expect($resolved)->toMatchArray(['previous_academic_status' => 'excused', 'previous_roster_sequence' => 3])
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->sole()->only(['academic_status', 'roster_sequence']))
        ->toBe(['academic_status' => 'regular', 'roster_sequence' => 4]);

    $stamp = SectionEnrollment::query()->withoutGlobalScopes()->sole()->updated_at;
    $this->travel(5)->minutes();

    $same = importRegistrar($world, registrarFile(rows: [['4', '441234567', 'طالب', 'منتظم']]));

    expect(ImportRow::query()->where('import_job_id', $same->id)->sole()->resolved)->not->toHaveKey('previous_academic_status')
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->sole()->updated_at?->equalTo($stamp))->toBeTrue();
});

/*
| L5 — a repeated sequence is a warning, kept
*/

test('a sequence repeated within the file is a warning naming the first row, and is kept', function (): void {
    $world = registrarWorld();
    $job = registrarChecked($world, registrarFile(rows: [['1', '441000001', 'أ', 'منتظم'], ['1', '441000002', 'ب', 'منتظم']]));

    $second = ImportRow::query()->where('import_job_id', $job->id)->orderByDesc('row_number')->firstOrFail();

    expect($second->status)->toBe('warning')
        ->and(array_column($second->reasons, 'reason_key'))->toContain(ImportReasons::DUPLICATE_SEQUENCE)
        ->and($second->resolved['roster_sequence'])->toBe(1);
});

/*
| Nothing kept on refusal; malformed and oversized files
*/

test('every upload refusal leaves no stored file behind', function (?array $block): void {
    Storage::fake('private');
    $world = registrarWorld();

    postRegistrar($world, registrarFile($block, registrarRows(1)))->assertUnprocessable();

    expect(Storage::disk('private')->allFiles())->toBe([])
        ->and(ImportJob::query()->withoutGlobalScopes()->count())->toBe(0);
})->with([
    'no block' => [null],
    'unknown campus' => [['المقر' => 'الرياض (طلاب)'] + REGISTRAR_BLOCK],
    'unknown course' => [['اسم المقرر' => 'مقرر لا وجود له'] + REGISTRAR_BLOCK],
]);

test('a text file renamed .xlsx is refused through the registrar endpoint too', function (): void {
    $world = registrarWorld();
    $path = tempnam(sys_get_temp_dir(), 'fake').'.xlsx';
    file_put_contents($path, "not a workbook\n");

    postRegistrar($world, new UploadedFile($path, 'export.xlsx', null, null, true))
        ->assertUnprocessable()
        ->assertJsonPath('errors.file.0', __('imports.upload.bad_type'));
});

test('a workbook that unpacks far beyond the upload limit is refused before it is parsed (L6)', function (): void {
    $world = registrarWorld();
    DB::table('settings')->where('key', Settings::IMPORT_MAX_FILE_MB)->update(['value' => json_encode(1)]);
    Settings::forget();

    // A real workbook, plus one entry that compresses 16 MB of zeros to a few kilobytes: under the
    // 1 MB upload limit as uploaded, past 15 × 1 MB once unpacked.
    $file = registrarFile(rows: registrarRows(1));
    $zip = new ZipArchive;
    $zip->open($file->getRealPath());
    $zip->addFromString('xl/media/padding.bin', str_repeat("\0", (FileValidator::MAX_EXPANSION + 1) * 1024 * 1024));
    $zip->close();

    expect(filesize($file->getRealPath()))->toBeLessThan(1024 * 1024);

    postRegistrar($world, new UploadedFile($file->getRealPath(), 'bomb.xlsx', null, null, true))
        ->assertUnprocessable()
        ->assertJsonPath('errors.file.0', __('imports.upload.unpacked_too_large'));
});

/*
| L4 — a refused upload's message takes focus
*/

test('the refusal is focused when the page comes back', function (): void {
    $world = registrarWorld();

    $this->actingAs($world['admin'])
        ->from(route('imports.create.registrar'))
        ->post(route('api.v1.imports.registrar.store'), [
            'file' => registrarFile(null, registrarRows(1)),
            'term_id' => $world['term']->id,
            'instructor_id' => $world['instructor']->id,
        ]);

    $this->actingAs($world['admin'])->get(route('imports.create.registrar'))
        ->assertOk()
        ->assertSee('role="alert"', false)
        ->assertSee('tabindex="-1"', false)
        ->assertSee('x-init="$el.focus()"', false);
});
