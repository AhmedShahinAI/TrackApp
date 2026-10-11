<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * T205 — `track:make-roster-fixture --rows= --with-defects`: a central roster built from this
 * database's own branches, courses and instructors (quickstart.md scenario 5).
 *
 * ## Valid against the database it runs on
 *
 * A fixture of invented branch names would be all "unknown branch" and prove nothing about the rows
 * that should import. So the valid rows use the active branches, the courses that have a programme,
 * and the accounts that hold the instructor role — whatever this database has. Run it after
 * `db:seed --class=UiFixtureSeeder`.
 *
 * Each row carries the optional eleventh column, the programme code, because a course in several
 * programmes gives a new section no single programme to belong to (FR-090) and the fixture's
 * courses are exactly that.
 *
 * ## One of each defect, at a known row
 *
 * `--with-defects` appends one row per category in contracts/imports.md, in the order the contract
 * lists them, and prints where each landed. Knowing the row is what makes the preview checkable: the
 * operator looks for row N and confirms the reason beside it.
 *
 * University ids are generated in a range no seeded student uses, so the valid rows are new students
 * the first time and matched students on a re-upload — which is exactly the FR-067 check.
 */
final class MakeRosterFixture extends Command
{
    private const HEADERS = [
        'الفصل', 'الفرع', 'النوع', 'رمز المقرر', 'اسم المقرر', 'رقم الشعبة',
        'اسم المدرس', 'البريد', 'الرقم الجامعي', 'اسم الطالب', 'رمز البرنامج',
    ];

    private const FIRST_NAMES = ['عبدالرحمن', 'لمى', 'يوسف', 'جود', 'فيصل', 'رهف', 'سعود', 'غلا', 'نايف', 'دانة', 'بندر', 'شهد'];

    private const LAST_NAMES = ['العتيبي', 'القحطاني', 'الحربي', 'الدوسري', 'الشمري', 'المطيري', 'الغامدي', 'الزهراني'];

    /**
     * How the registration unit writes a branch's gender in its roster. A lookup rather than a
     * comparison: the value is rendered, never branched on (NoHardcodedBranchesTest).
     */
    private const GENDER_WORDS = ['male' => 'طلاب', 'female' => 'طالبات'];

    /** Students per section in the generated roster — about a real section's size. */
    private const PER_SECTION = 40;

    protected $signature = 'track:make-roster-fixture
        {--rows=5000 : Valid data rows to generate}
        {--with-defects : Append one row for each validation category}
        {--path= : Output file (default storage/app/fixtures/roster-{rows}.xlsx)}';

    protected $description = 'Write a central-import roster built from this database, optionally with every defect category';

    public function handle(): int
    {
        $rows = max(1, (int) $this->option('rows'));

        $term = AcademicTerm::query()->orderByDesc('is_current')->first();
        $branches = Branch::query()->where('is_active', true)->get();
        $courses = Course::query()->with('programs:id,code')->get()->filter(fn (Course $c): bool => $c->programs->isNotEmpty())->values();
        $instructors = User::role('INSTRUCTOR')->where('is_active', true)->get();

        if ($term === null || $branches->isEmpty() || $courses->isEmpty() || $instructors->isEmpty()) {
            $this->error('This database has no term, active branch, course with a programme, or instructor. Seed it first: php artisan db:seed --class=UiFixtureSeeder');

            return self::FAILURE;
        }

        $data = [];

        for ($i = 0; $i < $rows; $i++) {
            $group = intdiv($i, self::PER_SECTION);
            $branch = $branches[$group % $branches->count()];
            $course = $courses[intdiv($group, $branches->count()) % $courses->count()];
            $instructor = $instructors[$group % $instructors->count()];

            $data[] = [
                $term->name_ar,
                $branch->campus_name_ar,
                self::GENDER_WORDS[$branch->gender] ?? $branch->gender,
                $course->code,
                $course->name_ar,
                (string) (700 + $group),
                $instructor->name_ar,
                $instructor->email,
                // 49xxxxxxx: a range the fixture seeders never use. The defect rows use
                // 48xxxxxxx, so a defect never collides with a valid row by accident.
                '49'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                self::FIRST_NAMES[$i % 12].' '.self::LAST_NAMES[($i * 5) % 8],
                (string) $course->programs->first()?->code,
            ];
        }

        if ($this->option('with-defects')) {
            $data = $this->appendDefects($data);
        }

        $path = (string) ($this->option('path') ?: storage_path('app/fixtures/roster-'.$rows.'.xlsx'));

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(self::textRow(self::HEADERS));

        foreach ($data as $row) {
            $writer->addRow(self::textRow($row));
        }

        $writer->close();

        $this->info(sprintf('Wrote %s data rows to %s', number_format(count($data)), $path));

        return self::SUCCESS;
    }

    /**
     * @param  list<list<string>>  $data
     * @return list<list<string>>
     */
    private function appendDefects(array $data): array
    {
        $template = $data[0];

        $defects = [];

        // Duplicate within the file: the first valid row, again.
        $defects['import.duplicate_in_file'] = $template;

        $malformed = $template;
        $malformed[8] = '44A10023';
        $defects['import.invalid_university_id'] = $malformed;

        $missing = $template;
        $missing[8] = '480000000';
        $missing[9] = '';
        $defects['import.missing_required'] = $missing;

        $branch = $template;
        $branch[1] = 'الدرعية';
        $branch[8] = '480000001';
        $defects['import.unknown_branch'] = $branch;

        $course = $template;
        $course[3] = 'PHYS110';
        $course[4] = 'فيزياء عامة';
        $course[8] = '480000002';
        $defects['import.unknown_course'] = $course;

        // An unregistered instructor matters only to a section that does not exist yet.
        $instructor = $template;
        $instructor[5] = '899';
        $instructor[6] = 'أ. بدر السهلي';
        $instructor[7] = 'b.alsahli.fixture@mu.edu.sa';
        $instructor[8] = '480000003';
        $defects['import.unknown_instructor'] = $instructor;

        $existing = Student::query()->withoutGlobalScopes()->orderBy('id')->first();

        if ($existing !== null) {
            $mismatch = $template;
            $mismatch[8] = $existing->university_id;
            $mismatch[9] = 'اسم مختلف تمامًا';
            $defects['import.name_mismatch'] = $mismatch;
        } else {
            $this->warn('No student exists yet, so the name-mismatch row was not added.');
        }

        // Longer than any section number can be (varchar 20), so no section can be resolved for it.
        $section = $template;
        $section[5] = str_repeat('9', 25);
        $section[8] = '480000004';
        $defects['import.unresolvable_section'] = $section;

        $report = [];

        foreach ($defects as $reason => $row) {
            // +1 for the header row, +1 because spreadsheet rows count from 1.
            $report[] = [count($data) + 2, $reason];
            $data[] = $row;
        }

        $this->table(['row', 'expected reason'], $report);

        return $data;
    }

    /**
     * @param  list<string>  $values
     */
    private static function textRow(array $values): Row
    {
        return new Row(array_map(static fn (string $v): StringCell => new StringCell($v), $values));
    }
}
