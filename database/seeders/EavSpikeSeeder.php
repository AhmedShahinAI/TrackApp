<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * T289 — the typed EAV capacity spike's data (research.md R-06, FR-087).
 *
 *     php artisan migrate:fresh && php artisan db:seed --class=EavSpikeSeeder
 *
 * ## The shape
 *
 * 1 term, 6 branches, 3,000 sections, 30,000 students, 30,000 enrollments, 10 initiatives × 5 schema
 * versions, 50 field definitions, 30,000 records and **1,500,000** values. Initiative 1 is the one under
 * load: it runs in all 3,000 sections, targets every enrolled student, and its current schema version
 * holds the 50 fields — so each of the 30,000 records carries 50 values. The other nine initiatives exist
 * with their five versions each, as the term's other initiatives would, and carry no records.
 *
 * Section 1 holds 200 students: R-06's grid query reads "one assignment, 200 records × 15 fields". The
 * other 29,800 students spread over the remaining 2,999 sections, nine or ten each.
 *
 * ## The values
 *
 * The 50 fields are 23 text, 12 number, 7 Yes/No, 5 date and 3 JSON, so the 1.5M values fall about
 * 46 / 24 / 14 / 10 / 6 % across the typed columns — R-06's mix. Values are drawn from a seeded
 * generator, not a pattern: R-06 rejects uniform synthetic data because it flatters index selectivity,
 * so Yes/No answers are about 70 % Yes and a required text answer is left empty about 8 % of the time.
 * Five fields are required for completion (four Yes/No, one text), which leaves roughly a quarter of the
 * records complete — and `is_complete` is seeded consistent with the values, so the completion queries
 * measure real work.
 *
 * ## The method
 *
 * Chunked multi-row INSERTs through the query builder, never Eloquent: 1.5M model instances would
 * measure PHP, not the database. Ids are assigned here, from the table's current maximum, so no row
 * has to be read back to learn its key. One transaction per chunk.
 *
 * `config('eav_spike.scale')` (EAV_SPIKE_SCALE) shrinks the sections, students and records together for
 * a smoke run; every record still carries all 50 values.
 */
final class EavSpikeSeeder extends Seeder
{
    public const SECTIONS = 3000;

    public const STUDENTS = 30000;

    public const GRID_SECTION_STUDENTS = 200;

    public const INITIATIVES = 10;

    public const VERSIONS = 5;

    /** Field counts by type: text, number, Yes/No, date, JSON. */
    public const FIELD_MIX = ['text' => 23, 'number' => 12, 'bool' => 7, 'date' => 5, 'json' => 3];

    /** Fields shown in the instructor's grid (R-06 query 1). */
    public const GRID_FIELDS = 15;

    private const CHUNK = 1000;

    private const RECORDS_PER_PASS = 400;

    /** The only connection this seeder writes to (tests/Support/EavSpike.php). */
    public const CONNECTION = 'eav_spike';

    /** Words for the free-text answers — Arabic, multi-byte, as the real notes will be. */
    private const WORDS = ['الطالب', 'حضر', 'الجلسة', 'وأكمل', 'المهمة', 'المطلوبة', 'بنجاح', 'مع', 'ملاحظة', 'حول',
        'التقدم', 'في', 'المشروع', 'ويحتاج', 'إلى', 'متابعة', 'إضافية', 'من', 'المرشد', 'الأكاديمي', 'خلال', 'الأسبوع', 'القادم'];

    /** @var array<string, int> the ids this run created, for the spike test to address */
    public static array $ids = [];

    public function run(): void
    {
        /*
         * Never the application's database. It inserts a user, a term, 3,000 sections and 30,000
         * students into whatever connection is the default — so it runs only where the spike harness
         * points it (`eav_spike`, a SQLite file or a schema of its own) and never in production.
         * Run the spike with: php artisan test --group=performance tests/Performance/EavSpikeTest.php
         */
        if (app()->environment('production')) {
            throw new RuntimeException('EavSpikeSeeder does not run in production.');
        }

        if (DB::getDefaultConnection() !== self::CONNECTION) {
            throw new RuntimeException('EavSpikeSeeder writes only to the eav_spike connection. Run tests/Performance/EavSpikeTest.php, which sets it up.');
        }

        mt_srand(20261009);

        $scale = max(0.001, (float) config('eav_spike.scale', 1.0));
        $sections = max(2, (int) round(self::SECTIONS * $scale));
        $students = max($sections + self::GRID_SECTION_STUDENTS, (int) round(self::STUDENTS * $scale));
        $now = now()->toDateTimeString();

        $organization = $this->insertOne('organizations', ['type' => 'college', 'name_ar' => 'كلية الاختبار', 'name_en' => 'Spike College', 'created_at' => $now, 'updated_at' => $now]);
        $user = $this->insertOne('users', [
            'organization_id' => $organization, 'name_ar' => 'مستخدم التحمل', 'name_en' => 'Spike User',
            'email' => 'eav-spike-'.uniqid().'@spike.invalid', 'password' => Hash::make(str()->random(32)),
            'locale' => 'ar', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $term = $this->insertOne('academic_terms', [
            'name_ar' => 'فصل التحمل', 'name_en' => 'Spike term', 'academic_year' => '2026/2027', 'semester' => 'first',
            'start_date' => '2026-09-01', 'end_date' => '2027-01-15', 'status' => 'active', 'is_current' => false,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $course = $this->insertOne('courses', ['organization_id' => $organization, 'code' => 'SPK'.mt_rand(100, 999), 'name_ar' => 'مقرر التحمل', 'name_en' => 'Spike course', 'created_at' => $now, 'updated_at' => $now]);
        $program = $this->insertOne('programs', ['organization_id' => $organization, 'code' => 'SPKP'.mt_rand(100, 999), 'name_ar' => 'برنامج التحمل', 'name_en' => 'Spike programme', 'unit' => 'admin', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        $branches = [];
        for ($b = 1; $b <= 6; $b++) {
            $branches[] = $this->insertOne('branches', ['organization_id' => $organization, 'campus_name_ar' => 'فرع '.$b, 'campus_name_en' => 'Branch '.$b, 'gender' => $b % 2 === 0 ? 'female' : 'male', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // Sections, numbered per branch so (term, branch, course, number) stays unique.
        $firstSection = $this->nextId('sections');
        $this->insertChunked('sections', (function () use ($sections, $firstSection, $branches, $term, $course, $program, $user, $now) {
            for ($i = 0; $i < $sections; $i++) {
                yield [
                    'id' => $firstSection + $i, 'term_id' => $term, 'branch_id' => $branches[$i % 6], 'course_id' => $course,
                    'program_id' => $program, 'section_number' => (string) (1000 + intdiv($i, 6)), 'status' => 'active',
                    'created_by' => $user, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        })());

        // Students and their one enrollment each: 200 in the first section, the rest spread evenly.
        $firstStudent = $this->nextId('students');
        $sectionOf = static function (int $n) use ($sections): int {
            if ($n < self::GRID_SECTION_STUDENTS) {
                return 0;
            }

            return 1 + (($n - self::GRID_SECTION_STUDENTS) % ($sections - 1));
        };

        $this->insertChunked('students', (function () use ($students, $firstStudent, $now) {
            for ($n = 0; $n < $students; $n++) {
                yield ['id' => $firstStudent + $n, 'university_id' => (string) (470000000 + $firstStudent + $n), 'name_ar' => 'طالب '.$n, 'name_en' => 'Student '.$n, 'created_at' => $now, 'updated_at' => $now];
            }
        })());

        $this->insertChunked('section_enrollments', (function () use ($students, $firstStudent, $firstSection, $sectionOf, $now) {
            for ($n = 0; $n < $students; $n++) {
                yield ['student_id' => $firstStudent + $n, 'section_id' => $firstSection + $sectionOf($n), 'status' => 'active', 'enrolled_at' => $now, 'created_at' => $now, 'updated_at' => $now];
            }
        })());

        // Initiatives and their schema versions; initiative 1's current version holds the 50 fields.
        $initiatives = [];
        $versions = [];
        for ($k = 1; $k <= self::INITIATIVES; $k++) {
            $initiative = $this->insertOne('initiatives', [
                'term_id' => $term, 'name_ar' => 'مبادرة '.$k, 'name_en' => 'Initiative '.$k, 'status' => 'active',
                'start_date' => '2026-09-01', 'end_date' => '2026-12-31', 'created_by' => $user, 'created_at' => $now, 'updated_at' => $now,
            ]);

            for ($v = 1; $v <= self::VERSIONS; $v++) {
                $versions[$k][$v] = $this->insertOne('initiative_schema_versions', ['initiative_id' => $initiative, 'version_number' => $v, 'published_at' => $now, 'published_by' => $user, 'created_at' => $now, 'updated_at' => $now]);
            }

            DB::table('initiatives')->where('id', $initiative)->update(['current_schema_version_id' => $versions[$k][self::VERSIONS]]);
            $initiatives[$k] = $initiative;
        }

        $fields = [];
        $order = 0;
        foreach (self::FIELD_MIX as $type => $count) {
            for ($c = 1; $c <= $count; $c++) {
                $order++;
                $required = ($type === 'bool' && $c <= 4) || ($type === 'text' && $c === 1);
                $id = $this->insertOne('initiative_fields', [
                    'schema_version_id' => $versions[1][self::VERSIONS], 'field_key' => $type.'_'.$c,
                    'label_ar' => 'حقل '.$order, 'label_en' => 'Field '.$order, 'type' => $type,
                    'is_required' => $required, 'sort_order' => $order,
                    'visible_to_instructor' => $order <= self::GRID_FIELDS || $required,
                    'required_for_completion' => $required, 'is_active' => true,
                    'options' => $type === 'json' ? json_encode(['choices' => ['a', 'b', 'c']]) : null,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $fields[] = ['id' => $id, 'type' => $type, 'required' => $required];
            }
        }

        // Initiative 1 runs in every section; each enrolled student is a target with one record.
        $firstAssignment = $this->nextId('initiative_sections');
        $this->insertChunked('initiative_sections', (function () use ($sections, $firstAssignment, $initiatives, $firstSection, $now) {
            for ($i = 0; $i < $sections; $i++) {
                yield ['id' => $firstAssignment + $i, 'initiative_id' => $initiatives[1], 'section_id' => $firstSection + $i, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now];
            }
        })());

        $this->insertChunked('initiative_targets', (function () use ($students, $firstAssignment, $firstStudent, $sectionOf, $now) {
            for ($n = 0; $n < $students; $n++) {
                yield ['assignment_id' => $firstAssignment + $sectionOf($n), 'student_id' => $firstStudent + $n, 'origin' => 'roster', 'created_at' => $now, 'updated_at' => $now];
            }
        })());

        // Records and their 50 values, a pass of records at a time.
        $firstRecord = $this->nextId('student_initiative_records');
        for ($start = 0; $start < $students; $start += self::RECORDS_PER_PASS) {
            $records = [];
            $values = [];

            for ($n = $start; $n < min($students, $start + self::RECORDS_PER_PASS); $n++) {
                $recordId = $firstRecord + $n;
                $complete = true;

                foreach ($fields as $field) {
                    $value = $this->value($field['type'], $field['required']);

                    if ($field['required'] && ($value['value_bool'] === false || $value['value_text'] === '')) {
                        $complete = false;
                    }

                    $values[] = ['record_id' => $recordId, 'field_id' => $field['id'], 'updated_at' => $now] + $value;
                }

                $records[] = [
                    'id' => $recordId, 'assignment_id' => $firstAssignment + $sectionOf($n), 'student_id' => $firstStudent + $n,
                    'is_complete' => $complete, 'completed_at' => $complete ? $now : null, 'created_at' => $now, 'updated_at' => $now,
                ];
            }

            DB::transaction(function () use ($records, $values): void {
                DB::table('student_initiative_records')->insert($records);

                foreach (array_chunk($values, self::CHUNK) as $chunk) {
                    DB::table('student_initiative_values')->insert($chunk);
                }
            });
        }

        self::$ids = [
            'initiative' => $initiatives[1],
            'grid_assignment' => $firstAssignment,
            'first_record' => $firstRecord,
            'records' => $students,
            'sections' => $sections,
        ];
    }

    /**
     * One value, in the typed column its field's type uses; every other typed column null.
     *
     * @return array{value_text: ?string, value_number: ?float, value_date: ?string, value_bool: ?bool, value_json: ?string}
     */
    private function value(string $type, bool $required): array
    {
        $value = ['value_text' => null, 'value_number' => null, 'value_date' => null, 'value_bool' => null, 'value_json' => null];

        switch ($type) {
            case 'text':
                // Mostly short answers, some paragraphs: 70 % of 2–8 words, 25 % of 10–40, 5 % of 40–70
                // — roughly 10 to 400 characters of Arabic, so row sizes are a note's, not a token's.
                $value['value_text'] = $required && mt_rand(1, 100) <= 8 ? '' : $this->words();
                break;
            case 'number':
                $value['value_number'] = round(mt_rand(0, 100000) / 100, 2);
                break;
            case 'bool':
                $value['value_bool'] = mt_rand(1, 100) <= 70;
                break;
            case 'date':
                $value['value_date'] = sprintf('2026-%02d-%02d', mt_rand(9, 12), mt_rand(1, 28));
                break;
            case 'json':
                $value['value_json'] = json_encode([
                    'choice' => ['a', 'b', 'c'][mt_rand(0, 2)],
                    'score' => mt_rand(1, 5),
                    'tags' => array_slice(['حضور', 'مشاركة', 'تكليف', 'اختبار', 'مشروع'], 0, mt_rand(1, 5)),
                    'note' => $this->words(),
                ], JSON_UNESCAPED_UNICODE);
                break;
        }

        return $value;
    }

    private function words(): string
    {
        $roll = mt_rand(1, 100);
        $count = $roll <= 70 ? mt_rand(2, 8) : ($roll <= 95 ? mt_rand(10, 40) : mt_rand(40, 70));
        $words = [];

        for ($i = 0; $i < $count; $i++) {
            $words[] = self::WORDS[mt_rand(0, count(self::WORDS) - 1)];
        }

        return implode(' ', $words);
    }

    /** @param  array<string, mixed>  $row */
    private function insertOne(string $table, array $row): int
    {
        return (int) DB::table($table)->insertGetId($row);
    }

    private function nextId(string $table): int
    {
        return (int) DB::table($table)->max('id') + 1;
    }

    /** @param  iterable<array<string, mixed>>  $rows */
    private function insertChunked(string $table, iterable $rows): void
    {
        $chunk = [];

        foreach ($rows as $row) {
            $chunk[] = $row;

            if (count($chunk) === self::CHUNK) {
                DB::table($table)->insert($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table($table)->insert($chunk);
        }
    }
}
