<?php

declare(strict_types=1);

use App\Models\ImportRow;
use App\Models\Student;
use App\Modules\Imports\Pipeline\ImportReasons;

/*
|--------------------------------------------------------------------------
| T210 — the eight categories, each with its outcome and its reason (FR-057, FR-062, FR-068)
|--------------------------------------------------------------------------
|
| One file, one defective row per category, beside a valid first row. Each defect is asserted three
| ways: the row's status (error or warning, as contracts/imports.md assigns it), the reason key, and
| the sentence the reader sees — in Arabic, then the same row in English, because FR-062 is about the
| reader's language and a key that resolves to itself would pass the first two checks.
|
*/

/**
 * The world, one existing student, and one validated file holding a row per category.
 *
 * @return array{world: array<string, mixed>, existing: Student, rows: array<string, array<string, string>>, job: App\Models\ImportJob, staged: array<string, ImportRow>}
 */
function categoriesWorld(): array
{
    $world = importWorld();

    $existing = Student::factory()->create(['university_id' => '441000001', 'name_ar' => 'نورة السبيعي']);

    // A course in two programmes: a new section of it has no single programme to belong to.
    $twoPrograms = App\Models\Course::factory()->create(['organization_id' => anOrganisation()->id, 'code' => 'MATH101']);
    $twoPrograms->programs()->attach([
        $world['program']->id,
        App\Models\Program::factory()->create(['organization_id' => anOrganisation()->id])->id,
    ]);

    $valid = rosterRow($world);

    $rows = [
        'valid' => $valid,
        'duplicate_in_file' => $valid,
        'invalid_university_id' => rosterRow($world, ['university_id' => '44A10023']),
        'missing_required' => rosterRow($world, ['student_name' => '']),
        'unknown_branch' => rosterRow($world, ['branch' => 'الدرعية']),
        'unknown_course' => rosterRow($world, ['course_code' => 'PHYS110']),
        'unknown_instructor' => rosterRow($world, ['section_number' => '401', 'instructor_email' => 'b.alsahli@mu.edu.sa']),
        'name_mismatch' => rosterRow($world, ['university_id' => '441000001', 'student_name' => 'اسم مختلف تمامًا']),
        'unresolvable_section' => rosterRow($world, ['course_code' => 'MATH101', 'section_number' => '500']),
    ];

    $job = uploadRoster($world['admin'], rosterFile(array_values($rows)), $world['term']->id);
    $job = mapAndValidate($world['admin'], $job);

    // Row numbers follow the file: the header is row 1, so the n-th data row is row n + 1.
    $staged = [];

    foreach (array_keys($rows) as $i => $category) {
        $staged[$category] = ImportRow::query()
            ->where('import_job_id', $job->id)
            ->where('row_number', $i + 2)
            ->firstOrFail();
    }

    return compact('world', 'existing', 'rows', 'job', 'staged');
}

test('the valid row is valid and has no problem', function (): void {
    ['world' => $world, 'existing' => $existing, 'job' => $job, 'staged' => $staged] = categoriesWorld();
    expect($staged['valid']->status)->toBe('valid')
        ->and(ImportReasons::hasProblem($staged['valid']->reasons))->toBeFalse();
});

test('each category produces its status and reason key', function (string $category, string $status): void {
    ['world' => $world, 'existing' => $existing, 'job' => $job, 'staged' => $staged] = categoriesWorld();
    $row = $staged[$category];

    expect($row->status)->toBe($status)
        ->and(array_column($row->reasons ?? [], 'reason_key'))->toContain('import.'.$category);
})->with([
    ['duplicate_in_file', 'error'],
    ['invalid_university_id', 'error'],
    ['missing_required', 'error'],
    ['unknown_branch', 'error'],
    ['unknown_course', 'warning'],
    ['unknown_instructor', 'warning'],
    ['name_mismatch', 'warning'],
    ['unresolvable_section', 'error'],
]);

test('the reasons name what the operator needs to fix', function (): void {
    ['world' => $world, 'existing' => $existing, 'job' => $job, 'staged' => $staged] = categoriesWorld();
    $reason = fn (string $category): string => ImportReasons::localise(collect($staged[$category]->reasons)
        ->firstWhere('reason_key', 'import.'.$category));

    expect($reason('duplicate_in_file'))->toBe('مكرر داخل الملف (الصف 2)')
        ->and($reason('invalid_university_id'))->toBe('الرقم الجامعي لا يطابق الصيغة (9 أرقام)')
        ->and($reason('missing_required'))->toBe('قيمة إلزامية مفقودة: اسم الطالب')
        ->and($reason('unknown_branch'))->toBe('فرع غير معروف: الدرعية — طلاب')
        ->and($reason('unknown_course'))->toBe('مقرر غير معروف: PHYS110')
        ->and($reason('unknown_instructor'))->toBe('مدرس بلا حساب: b.alsahli@mu.edu.sa')
        ->and($reason('name_mismatch'))->toBe('الاسم يختلف عن المحفوظ (نورة السبيعي) — سيُحتفظ بالاسم المحفوظ');
});

test('the preview renders the same reasons in the reader\'s language', function (): void {
    ['world' => $world, 'existing' => $existing, 'job' => $job, 'staged' => $staged] = categoriesWorld();
    $arabic = $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->json('data.rows');

    $world['admin']->forceFill(['locale' => 'en'])->saveQuietly();

    $english = $this->actingAs($world['admin']->fresh())->getJson(route('api.v1.imports.preview', $job))->json('data.rows');

    expect($arabic[1]['reasons'])->toContain('مكرر داخل الملف (الصف 2)')
        ->and($english[1]['reasons'])->toContain('Duplicate within file (row 2)')
        ->and($english[3]['reasons'])->toContain('Missing required value: Student name');
});

test('an existing student whose name matches is linked, not warned about', function (): void {
    ['world' => $world, 'existing' => $existing, 'job' => $job, 'staged' => $staged] = categoriesWorld();
    $job = uploadRoster($world['admin'], rosterFile([
        rosterRow($world, ['university_id' => '441000001', 'student_name' => 'نورة السبيعي']),
    ], 'same-name.xlsx'), $world['term']->id);

    $row = ImportRow::query()->where('import_job_id', mapAndValidate($world['admin'], $job)->id)->firstOrFail();

    expect($row->status)->toBe('valid')
        ->and(array_column($row->reasons, 'reason_key'))->toBe(['import.existing_student'])
        ->and($row->resolved['student_id'])->toBe($existing->id);
});

test('Arabic-Indic digits are the same student as Western ones', function (): void {
    ['world' => $world, 'existing' => $existing, 'job' => $job, 'staged' => $staged] = categoriesWorld();
    $job = uploadRoster($world['admin'], rosterFile([
        rosterRow($world, ['university_id' => '٤٤١٠٠٠٠٠١', 'student_name' => 'نورة السبيعي']),
    ], 'arabic-digits.xlsx'), $world['term']->id);

    $row = ImportRow::query()->where('import_job_id', mapAndValidate($world['admin'], $job)->id)->firstOrFail();

    expect($row->status)->toBe('valid')
        ->and($row->resolved['student_id'])->toBe($existing->id);
});

test('a numeric university-id cell is read as the digits, not as a float', function (): void {
    ['world' => $world, 'existing' => $existing, 'job' => $job, 'staged' => $staged] = categoriesWorld();
    $path = tempnam(sys_get_temp_dir(), 'num').'.xlsx';
    $writer = new OpenSpout\Writer\XLSX\Writer;
    $writer->openToFile($path);
    $writer->addRow(OpenSpout\Common\Entity\Row::fromValues(array_values(ROSTER_HEADERS)));

    $row = array_values(rosterRow($world));
    $row[8] = 441000001; // an integer cell, as Excel stores a typed number
    $writer->addRow(OpenSpout\Common\Entity\Row::fromValues($row));
    $writer->close();

    $job = uploadRoster($world['admin'], new Illuminate\Http\UploadedFile($path, 'numeric.xlsx', null, null, true), $world['term']->id);
    $staged = ImportRow::query()->where('import_job_id', mapAndValidate($world['admin'], $job)->id)->firstOrFail();

    expect($staged->raw['university_id'])->toBe('441000001')
        ->and($staged->resolved['student_id'])->toBe($existing->id);
});

test('a deactivated branch is not a destination', function (): void {
    ['world' => $world, 'existing' => $existing, 'job' => $job, 'staged' => $staged] = categoriesWorld();
    $world['branch']->forceFill(['is_active' => false])->saveQuietly();

    $job = uploadRoster($world['admin'], rosterFile([rosterRow($world)], 'inactive.xlsx'), $world['term']->id);
    $row = ImportRow::query()->where('import_job_id', mapAndValidate($world['admin'], $job)->id)->firstOrFail();

    expect($row->status)->toBe('error')
        ->and(array_column($row->reasons, 'reason_key'))->toContain('import.unknown_branch');
});
