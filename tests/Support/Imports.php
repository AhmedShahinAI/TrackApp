<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Shared fixtures for the import suites (US5)
|--------------------------------------------------------------------------
|
| Functions, for the reason Pest.php gives: a dozen test files need the same world and the same
| six-step walk, and a helper declared in one of them is available only while that file happens to
| load first.
|
| The walk goes through the HTTP endpoints, never around them. A test that staged rows by calling the
| validator directly would certify a path no operator takes — the lesson ChangeInstructorTest taught
| in Phase 7, where a hand-seeded history row hid that the application never wrote one.
|
*/

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\ImportJob;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/** The reference's CENTRAL_HEADERS, in the order the registration unit's roster uses. */
const ROSTER_HEADERS = [
    'term' => 'الفصل',
    'branch' => 'الفرع',
    'gender' => 'النوع',
    'course_code' => 'رمز المقرر',
    'course_name' => 'اسم المقرر',
    'section_number' => 'رقم الشعبة',
    'instructor_name' => 'اسم المدرس',
    'instructor_email' => 'البريد',
    'university_id' => 'الرقم الجامعي',
    'student_name' => 'اسم الطالب',
];

/**
 * A term, one male branch, one programme with one course, one instructor, and the Super Admin who
 * runs the import. Everything a valid central row needs to resolve.
 *
 * @return array{admin: User, term: AcademicTerm, branch: Branch, program: Program, course: Course, instructor: User}
 */
function importWorld(): array
{
    $organisation = anOrganisation();
    $admin = aUser('SUPER_ADMIN');

    $term = AcademicTerm::factory()->create(['status' => 'active', 'is_current' => true]);

    /*
     * firstOrCreate throughout, so the world can be asked for twice in one test. ActionCoverageTest
     * runs every performer inside a single test, and four of them are imports; a second call that
     * tried to insert a second "CS101" would fail on the unique index rather than test anything.
     */
    $branch = Branch::query()->firstOrCreate(
        ['organization_id' => $organisation->id, 'campus_name_ar' => 'المجمعة', 'gender' => 'male'],
        ['campus_name_en' => 'Majmaah', 'is_active' => true],
    );

    $program = Program::query()->where('organization_id', $organisation->id)->where('code', 'CS')->first()
        ?? Program::factory()->create(['organization_id' => $organisation->id, 'code' => 'CS']);

    $course = Course::query()->firstOrCreate(
        ['organization_id' => $organisation->id, 'code' => 'CS101'],
        ['name_ar' => 'مقدمة في الحاسب', 'name_en' => 'Introduction to Computing'],
    );
    $course->programs()->syncWithoutDetaching([$program->id]);

    $instructor = User::query()->where('email', 'h.almutairi@mu.edu.sa')->first()
        ?? aUser('INSTRUCTOR', ['email' => 'h.almutairi@mu.edu.sa', 'name_ar' => 'د. هند المطيري']);

    return compact('admin', 'term', 'branch', 'program', 'course', 'instructor');
}

/**
 * One valid central row for importWorld(), with any target overridden.
 *
 * @param  array<string, mixed>  $world
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function rosterRow(array $world, array $overrides = []): array
{
    static $sequence = 0;
    $sequence++;

    return array_merge([
        'term' => $world['term']->name_ar,
        'branch' => 'المجمعة',
        'gender' => 'طلاب',
        'course_code' => 'CS101',
        'course_name' => 'مقدمة في الحاسب',
        'section_number' => '131',
        'instructor_name' => 'د. هند المطيري',
        'instructor_email' => 'h.almutairi@mu.edu.sa',
        'university_id' => '4420'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
        'student_name' => 'طالب رقم '.$sequence,
    ], $overrides);
}

/**
 * `$count` valid rows with distinct university ids.
 *
 * @param  array<string, mixed>  $world
 * @param  array<string, string>  $overrides
 * @return list<array<string, string>>
 */
function rosterRows(array $world, int $count, array $overrides = []): array
{
    $rows = [];

    for ($i = 0; $i < $count; $i++) {
        $rows[] = rosterRow($world, $overrides);
    }

    return $rows;
}

/**
 * Write rows to a real .xlsx or .csv and wrap it as an upload.
 *
 * Every cell is a StringCell, as a registrar's export of university ids would be; a test that wants
 * numeric cells builds its own file.
 *
 * @param  list<array<string, string>|list<string>>  $rows  keyed by target, or plain lists of cells
 * @param  array<string, string>|list<string>|null  $headers  target => header; defaults to the ten central headers
 */
function rosterFile(array $rows, string $name = 'roster.xlsx', ?array $headers = null): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'roster').'.'.pathinfo($name, PATHINFO_EXTENSION);

    $writer = str_ends_with($name, '.csv') ? new CsvWriter : new XlsxWriter;
    $writer->openToFile($path);

    $text = static fn (array $values): Row => new Row(array_map(
        static fn (mixed $v): StringCell => new StringCell((string) $v),
        array_values($values),
    ));

    $headers ??= ROSTER_HEADERS;
    $writer->addRow($text(array_values($headers)));

    foreach ($rows as $row) {
        $writer->addRow($text(array_is_list($row) ? $row : array_map(
            static fn (string|int $target): string => (string) ($row[$target] ?? ''),
            array_keys($headers),
        )));
    }

    $writer->close();

    return new UploadedFile($path, $name, null, null, true);
}

/**
 * The same bytes as an upload a second time. Two writes of one roster are not one file — xlsx
 * records its creation time — so FR-067's "same file" has to be literally the same file.
 */
function sameFileAgain(UploadedFile $file): UploadedFile
{
    $copy = tempnam(sys_get_temp_dir(), 'again').'.'.$file->getClientOriginalExtension();
    copy($file->getRealPath(), $copy);

    return new UploadedFile($copy, $file->getClientOriginalName(), null, null, true);
}

/** Step 1, through the endpoint, as an API caller. */
function uploadRoster(User $actor, UploadedFile $file, int $termId): ImportJob
{
    test()->actingAs($actor)
        ->post(route('api.v1.imports.store'), ['file' => $file, 'term_id' => $termId], ['Accept' => 'application/json'])
        ->assertCreated();

    return ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
}

/**
 * Steps 2 and 3: confirm the suggested mapping and run validation (synchronously, under the test
 * queue driver).
 *
 * @param  array<string, bool|int>  $options
 */
function mapAndValidate(User $actor, ImportJob $job, array $options = []): ImportJob
{
    test()->actingAs($actor)->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
        'options' => $options,
    ])->assertOk();

    test()->actingAs($actor)->postJson(route('api.v1.imports.validate', $job))->assertAccepted();

    return $job->refresh();
}

/** Steps 4 and 5: open the preview, then confirm. */
function previewAndCommit(User $actor, ImportJob $job): ImportJob
{
    test()->actingAs($actor)->getJson(route('api.v1.imports.preview', $job))->assertOk();
    test()->actingAs($actor)->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();

    return $job->refresh();
}

/**
 * The whole walk.
 *
 * @param  array<string, mixed>  $world
 * @param  list<array<string, string>>  $rows
 * @param  array<string, bool|int>  $options
 */
function importRoster(array $world, array $rows, array $options = [], ?UploadedFile $file = null): ImportJob
{
    $job = uploadRoster($world['admin'], $file ?? rosterFile($rows), $world['term']->id);
    $job = mapAndValidate($world['admin'], $job, $options);

    return previewAndCommit($world['admin'], $job);
}

/*
|--------------------------------------------------------------------------
| US6 — the instructor's section list
|--------------------------------------------------------------------------
*/

/** The section mode's two headers, as the template writes them in Arabic. */
const SECTION_HEADERS = [
    'university_id' => 'الرقم الجامعي',
    'student_name' => 'اسم الطالب',
];

/**
 * importWorld() plus one section the world's instructor teaches.
 *
 * @return array{admin: User, term: AcademicTerm, branch: Branch, program: Program, course: Course, instructor: User, section: App\Models\Section}
 */
function sectionWorld(): array
{
    $world = importWorld();

    $world['section'] = App\Models\Section::factory()->create([
        'term_id' => $world['term']->id,
        'branch_id' => $world['branch']->id,
        'course_id' => $world['course']->id,
        'program_id' => $world['program']->id,
        'section_number' => '131',
        'instructor_id' => $world['instructor']->id,
    ]);

    return $world;
}

/**
 * Two-column rows for sectionWorld().
 *
 * @return list<array<string, string>>
 */
function sectionRows(int $count): array
{
    static $sequence = 0;

    $rows = [];

    for ($i = 0; $i < $count; $i++) {
        $sequence++;
        $rows[] = ['university_id' => '4430'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT), 'student_name' => 'طالبة رقم '.$sequence];
    }

    return $rows;
}

/** Step 1 of a section import, through the endpoint. */
function uploadSectionList(User $actor, App\Models\Section $section, UploadedFile $file): ImportJob
{
    test()->actingAs($actor)
        ->post(route('api.v1.sections.imports.store', $section), ['file' => $file], ['Accept' => 'application/json'])
        ->assertCreated();

    return ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
}

/**
 * The whole six-step walk of a section import, as the given caller.
 *
 * @param  list<array<string, string>>  $rows
 */
function importSectionList(User $actor, App\Models\Section $section, array $rows, ?UploadedFile $file = null): ImportJob
{
    $job = uploadSectionList($actor, $section, $file ?? rosterFile($rows, 'list.xlsx', SECTION_HEADERS));

    return previewAndCommit($actor, mapAndValidate($actor, $job));
}
