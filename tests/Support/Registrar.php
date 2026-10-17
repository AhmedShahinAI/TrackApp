<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\ImportJob;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/*
|--------------------------------------------------------------------------
| Phase 15 — the registrar import's world, files and walk
|--------------------------------------------------------------------------
|
| The world matches design-system/uploads/filedownloadservlet(21).xlsx: campus «المجمعة (طلاب)»,
| course «برمجة 2», section 2298. So the real file resolves against it unmodified (T325), and a
| written file with the same block resolves the same way.
|
*/

/** The reference file's own metadata block (FR-097). */
const REGISTRAR_BLOCK = [
    'المقر' => 'المجمعة (طلاب)',
    'الدرجة' => 'الدبلوم المتوسط',
    'اسم المقرر' => 'برمجة 2',
    'النشاط' => 'محاضرة',
    'الشعبة' => '2298',
];

/** And its header row (FR-098). */
const REGISTRAR_HEADERS = ['تسلسل', 'رقم الطالب', 'اسم الطالب', 'الحالة'];

/**
 * A term, the male Majmaah branch, the course «برمجة 2» in exactly one programme, an instructor, and
 * the Super Admin who runs the import.
 *
 * @return array{admin: User, term: AcademicTerm, branch: Branch, program: Program, course: Course, instructor: User}
 */
function registrarWorld(): array
{
    $organisation = anOrganisation();
    $admin = aUser('SUPER_ADMIN');

    $term = AcademicTerm::query()->where('is_current', true)->first()
        ?? AcademicTerm::factory()->create(['status' => 'active', 'is_current' => true]);

    $branch = Branch::query()->firstOrCreate(
        ['organization_id' => $organisation->id, 'campus_name_ar' => 'المجمعة', 'gender' => 'male'],
        ['campus_name_en' => 'Majmaah', 'is_active' => true],
    );

    $program = Program::query()->where('organization_id', $organisation->id)->where('code', 'CS')->first()
        ?? Program::factory()->create(['organization_id' => $organisation->id, 'code' => 'CS']);

    $course = Course::query()->firstOrCreate(
        ['organization_id' => $organisation->id, 'code' => 'CS102'],
        ['name_ar' => 'برمجة 2', 'name_en' => 'Programming 2'],
    );
    $course->programs()->syncWithoutDetaching([$program->id]);

    $instructor = User::query()->where('email', 'registrar.instructor@mu.edu.sa')->first()
        ?? aUser('INSTRUCTOR', ['email' => 'registrar.instructor@mu.edu.sa', 'name_ar' => 'د. سارة العتيبي']);

    return compact('admin', 'term', 'branch', 'program', 'course', 'instructor');
}

/**
 * A registrar export written to disk: the block (label => value, in order; null for no block), a
 * blank line, the header, then the rows as given.
 *
 * @param  array<string, string>|null  $block
 * @param  list<list<string>>  $rows
 * @param  list<string>|null  $headers
 */
function registrarFile(?array $block = REGISTRAR_BLOCK, array $rows = [], ?array $headers = null, string $name = 'filedownloadservlet.xlsx'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'registrar').'.xlsx';
    $writer = new XlsxWriter;
    $writer->openToFile($path);

    $text = static fn (array $values): Row => new Row(array_map(static fn (mixed $v): StringCell => new StringCell((string) $v), $values));

    foreach ($block ?? [] as $label => $value) {
        $writer->addRow($text([$label, $value, '', '']));
    }

    if ($block !== null) {
        $writer->addRow($text(['', '', '', '']));
    }

    $writer->addRow($text($headers ?? REGISTRAR_HEADERS));

    foreach ($rows as $row) {
        $writer->addRow($text($row));
    }

    $writer->close();

    return new UploadedFile($path, $name, null, null, true);
}

/**
 * `$count` valid data rows: sequence, a nine-digit university id, a name, «منتظم».
 *
 * @return list<list<string>>
 */
function registrarRows(int $count, string $status = 'منتظم', int $from = 1): array
{
    return array_map(
        static fn (int $i): array => [(string) $i, '44'.str_pad((string) (2100000 + $i), 7, '0', STR_PAD_LEFT), 'طالب التسجيل '.$i, $status],
        range($from, $from + $count - 1),
    );
}

/**
 * The reference file (the registrar's real export, personal data redacted: SEC-012), copied so the
 * upload's move cannot touch the original.
 */
function realRegistrarFile(): UploadedFile
{
    $copy = tempnam(sys_get_temp_dir(), 'real').'.xlsx';
    copy(base_path('design-system/uploads/filedownloadservlet(21).xlsx'), $copy);

    return new UploadedFile($copy, 'filedownloadservlet(21).xlsx', null, null, true);
}

/**
 * Step 1 through the endpoint, as an API caller. Returns the response so a test can assert a 422.
 *
 * @param  array<string, mixed>  $world
 * @param  array<string, mixed>  $overrides
 * @return TestResponse<Symfony\Component\HttpFoundation\Response>
 */
function postRegistrar(array $world, UploadedFile $file, array $overrides = []): TestResponse
{
    return test()->actingAs($world['admin'])->post(route('api.v1.imports.registrar.store'), $overrides + [
        'file' => $file,
        'term_id' => $world['term']->id,
        'instructor_id' => $world['instructor']->id,
    ], ['Accept' => 'application/json']);
}

/**
 * The whole six-step walk: upload, confirm (mapping and choices), check, preview, commit.
 *
 * @param  array<string, mixed>  $world
 * @param  array<string, int>  $options
 */
function importRegistrar(array $world, UploadedFile $file, array $options = []): ImportJob
{
    postRegistrar($world, $file)->assertCreated();

    $job = ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    $job = mapAndValidate($world['admin'], $job, $options);

    return previewAndCommit($world['admin'], $job);
}
