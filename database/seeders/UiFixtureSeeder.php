<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionAccessGrant;
use App\Models\SectionEnrollment;
use App\Models\SectionInstructorHistory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * T051 + T305 - the design system's demo dataset, ported from design-system/ui_kits/web-app/store.js.
 *
 * Every row here exists so a Blade screen and its React reference render identical content, which is
 * what makes a visual-regression diff mean something. Ids are explicit and match store.js, because a
 * baseline that renders a different section number is a failed diff for no reason.
 *
 * The whole thing runs inside Model::withoutEvents(). Without that, the Auditable trait would write
 * roughly 200 audit entries while seeding, and the audit screen's baseline - which must show the ten
 * reference entries at the bottom of this file and nothing else - would be unusable.
 *
 * Two deliberate departures from the kit, both because the specification governs behaviour:
 *   - Audit action keys use the contract vocabulary (contracts/audit.md), not the kit's. The kit
 *     writes `auth.signed_in`, `import.completed`, `term.current_set`, `user.role_changed`; the
 *     contract names them `auth.login`, `import.committed`, `term.set_current`, `user.roles_changed`.
 *     Keys are filter values in the API, so the contract wins; the visible label is localized
 *     server-side, so the rendered Arabic still matches the reference.
 *   - `name_en` for the nineteen students from the real registrar file is null, not the kit's empty
 *     string. The column is nullable and an empty string is not a name.
 */
class UiFixtureSeeder extends Seeder
{
    private const PASSWORD = 'Track2026';

    /** @var list<string> */
    private const AR_FIRST = [
        'عبدالرحمن', 'لمى', 'يوسف', 'جود', 'فيصل', 'رهف', 'سعود', 'غلا', 'نايف',
        'دانة', 'بندر', 'شهد', 'ريان', 'أروى', 'مشاري', 'هيا', 'تركي', 'وعد',
    ];

    /** @var list<string> */
    private const AR_LAST = [
        'العتيبي', 'القحطاني', 'الحربي', 'الدوسري', 'الشمري', 'المطيري',
        'الغامدي', 'الزهراني', 'العنزي', 'السبيعي', 'الرشيدي', 'الشهري',
    ];

    /** @var list<string> */
    private const EN_FIRST = [
        'Abdulrahman', 'Lama', 'Yousef', 'Jood', 'Faisal', 'Rahaf', 'Saud', 'Ghala', 'Nayef',
        'Dana', 'Bandar', 'Shahad', 'Rayan', 'Arwa', 'Mishari', 'Haya', 'Turki', 'Waad',
    ];

    /** @var list<string> */
    private const EN_LAST = [
        'Al-Otaibi', 'Al-Qahtani', 'Al-Harbi', 'Al-Dosari', 'Al-Shammari', 'Al-Mutairi',
        'Al-Ghamdi', 'Al-Zahrani', 'Al-Anazi', 'Al-Subaie', 'Al-Rashidi', 'Al-Shehri',
    ];

    /**
     * The nineteen rows the platform's first real registrar export carried, kept verbatim: sequence,
     * university id, Arabic name. They are the reference for the registrar import screens and for
     * how a genuine Saudi name renders at its full length.
     *
     * @var list<array{string, string, string}>
     */
    private const REGISTRAR_ROWS = [
        ['1', '442104959', 'سعود بن محمد بن ابراهيم الشدوخي'],
        ['2', '462103556', 'علي بن ابراهيم بن علي حدادي'],
        ['3', '462109105', 'عبدالعزيز بن عبدالرحمن بن عبدالعزيز الجويعي'],
        ['4', '462109230', 'يونس بن علي بن محمد حدادي'],
        ['5', '462109232', 'فهد بن سلطان بن ماضي العتيبي'],
        ['6', '471103890', 'عبدالمجيد بن محمد بن عبدالله الراشد'],
        ['7', '471104932', 'عبدالعزيز بن محمد بن سالم البديري المطيري'],
        ['8', '471105041', 'ثامر بن ابراهيم بن حمد اللهيب'],
        ['9', '471105043', 'عبدالسلام بن فهد بن حمود السبيعي'],
        ['10', '471105048', 'عبدالرحمن بن عبدالله بن ضميان السبيعي العنزي'],
        ['11', '471105050', 'مازن بن بشير بن نافع العنزي'],
        ['12', '471105056', 'ريان بن جلوي بن مسفر المطيري'],
        ['13', '471105058', 'تركي بن سلمان بن فيصل العتيبي'],
        ['14', '471105060', 'بدر بن فهد بن سليمان السبيعي العنزي'],
        ['15', '471105061', 'سلمان بن بدر بن محمد الشثيلي المطيري'],
        ['16', '471105064', 'عبدالعزيز بن مطلق بن حمد العيباني المطيري'],
        ['17', '471105067', 'فهد بن منيور بن قريزح الضبيطي المطيري'],
        ['18', '471105068', 'فارس بن مرزوق بن مقبل البديرى المطيرى'],
        ['19', '471105069', 'عبدالعزيز بن حمد بن عبدالعزيز الجوف'],
    ];

    public function run(): void
    {
        Model::withoutEvents(function (): void {
            $organizationId = $this->organizationId();

            $this->seedBranches($organizationId);
            $this->seedTerms();
            $this->seedCourses($organizationId);
            $this->seedPrograms($organizationId);
            $this->seedCourseProgramLinks();
            $this->seedUsers($organizationId);
            $this->seedSections();
            $this->seedGrantsAndHistory();
            $this->seedStudentsAndEnrollments();
            $this->seedAuditEntries();
        });
    }

    private function organizationId(): int
    {
        $organization = Organization::query()->where('type', 'college')->first();

        if ($organization === null) {
            $this->call(OrganizationSeeder::class);
            $organization = Organization::query()->where('type', 'college')->firstOrFail();
        }

        return (int) $organization->id;
    }

    private function seedBranches(int $organizationId): void
    {
        $branches = [
            [1, 'المجمعة', 'Majmaah', 'male', true],
            [2, 'المجمعة', 'Majmaah', 'female', true],
            [3, 'الزلفي', 'Zulfi', 'male', true],
            [4, 'الزلفي', 'Zulfi', 'female', true],
            [5, 'رماح', 'Rumah', 'male', true],

            // Deactivated on purpose: the branches list needs a row in the inactive state, and the
            // sections screen needs to prove a section in an inactive branch still renders.
            [6, 'رماح', 'Rumah', 'female', false],
        ];

        foreach ($branches as [$id, $ar, $en, $gender, $active]) {
            Branch::query()->updateOrCreate(['id' => $id], [
                'organization_id' => $organizationId,
                'campus_name_ar' => $ar,
                'campus_name_en' => $en,
                'gender' => $gender,
                'is_active' => $active,
            ]);
        }
    }

    private function seedTerms(): void
    {
        $terms = [
            [1, 'الفصل الأول 1448', 'First Semester 1448', '2026/2027', 'first', '2026-08-30', '2027-01-07', 'active', true],

            // `closed` appears here although data-model.md Group 2 says no seeder sets it. That note
            // is about not implying the closure *enforcement* exists - it is a later feature. This is
            // a fixture seeder for visual baselines, and the terms screen needs the closed badge.
            [2, 'الفصل الثاني 1447', 'Second Semester 1447', '2025/2026', 'second', '2026-01-18', '2026-05-28', 'closed', false],

            [3, 'الفصل الثاني 1448', 'Second Semester 1448', '2026/2027', 'second', '2027-01-17', '2027-05-27', 'draft', false],
        ];

        foreach ($terms as [$id, $ar, $en, $year, $semester, $start, $end, $status, $current]) {
            AcademicTerm::query()->updateOrCreate(['id' => $id], [
                'name_ar' => $ar,
                'name_en' => $en,
                'academic_year' => $year,
                'semester' => $semester,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $status,
                'is_current' => $current,
            ]);
        }
    }

    private function seedCourses(int $organizationId): void
    {
        $courses = [
            [1, 'MATH101', 'مبادئ الرياضيات', 'Principles of Mathematics'],
            [2, 'ENG102', 'اللغة الإنجليزية 2', 'English Language 2'],
            [3, 'CS101', 'مقدمة في الحاسب', 'Introduction to Computing'],
            [4, 'STAT101', 'مبادئ الإحصاء', 'Principles of Statistics'],
            [5, 'ISLM101', 'الثقافة الإسلامية', 'Islamic Culture'],
            [6, 'CS212', 'برمجة 2', 'Programming 2'],
        ];

        foreach ($courses as [$id, $code, $ar, $en]) {
            Course::query()->updateOrCreate(['id' => $id], [
                'organization_id' => $organizationId,
                'code' => $code,
                'name_ar' => $ar,
                'name_en' => $en,
            ]);
        }
    }

    private function seedPrograms(int $organizationId): void
    {
        $programs = [
            [1, 'HR', 'الموارد البشرية', 'Human Resources', 'admin'],
            [2, 'ACC', 'المحاسبة والضرائب', 'Accounting & Tax', 'admin'],
            [3, 'LOG', 'إدارة اللوجستيات', 'Logistics Management', 'admin'],
            [4, 'MKT', 'التسويق', 'Marketing', 'admin'],
            [5, 'CS', 'البرمجة وعلوم الحاسب', 'Programming & Computer Science', 'tech'],
            [6, 'APP', 'تطوير التطبيقات', 'Application Development', 'tech'],
            [7, 'GD', 'التصميم الجرافيكي', 'Graphic Design', 'tech'],
            [8, 'RE', 'إدارة وتطوير العقار', 'Real Estate Management', 'admin'],
            [9, 'EC', 'التجارة الإلكترونية', 'E-Commerce', 'tech'],
        ];

        foreach ($programs as [$id, $code, $ar, $en, $unit]) {
            Program::query()->updateOrCreate(['id' => $id], [
                'organization_id' => $organizationId,
                'code' => $code,
                'name_ar' => $ar,
                'name_en' => $en,
                'unit' => $unit,
                'is_active' => true,
            ]);
        }
    }

    private function seedCourseProgramLinks(): void
    {
        // COURSE_PROG from store.js. ISLM101 belongs to all nine, which is what a general-requirement
        // course looks like and what the programme filter needs in order to be worth testing.
        $links = [
            1 => [2, 3, 5],
            2 => [1, 4],
            3 => [5, 6, 9],
            4 => [2, 4],
            5 => [1, 2, 3, 4, 5, 6, 7, 8, 9],
            6 => [5, 6],
        ];

        foreach ($links as $courseId => $programIds) {
            Course::query()->findOrFail($courseId)->programs()->sync($programIds);
        }
    }

    private function seedUsers(int $organizationId): void
    {
        $password = Hash::make(self::PASSWORD);

        $users = [
            [1, 'مها السالم', 'Maha Al-Salem', 'm.alsalem@mu.edu.sa', 'SUPER_ADMIN', [], true, '2026-08-20 08:10:00', '2026-09-26 07:55:00'],
            [2, 'د. نورة الدوسري', 'Dr. Noura Al-Dosari', 'n.aldosari@mu.edu.sa', 'BRANCH_COORDINATOR', [4], true, '2026-09-01 09:00:00', '2026-09-25 12:30:00'],

            // Never signed in: the account that renders the first-sign-in banner (FR-012).
            [3, 'د. فيصل العتيبي', 'Dr. Faisal Al-Otaibi', 'f.alotaibi@mu.edu.sa', 'INSTRUCTOR', [], true, null, null],

            [4, 'د. خالد الشمري', 'Dr. Khalid Al-Shammari', 'k.alshammari@mu.edu.sa', 'BRANCH_COORDINATOR', [1, 2], true, '2026-09-02 10:00:00', '2026-09-24 09:10:00'],
            [5, 'أ. ريم العنزي', 'Reem Al-Anazi', 'r.alanazi@mu.edu.sa', 'INSTRUCTOR', [], true, '2026-09-03 11:00:00', '2026-09-22 13:00:00'],

            // Deactivated, and still the instructor of record on a closed term's section (18) and in
            // section 11's history. That combination is the point: deactivation must not erase the
            // historical record (constitution V).
            [6, 'أ. سلمان الرشيدي', 'Salman Al-Rashidi', 's.alrashidi@mu.edu.sa', 'INSTRUCTOR', [], false, '2026-01-20 08:00:00', '2026-05-10 08:00:00'],

            [7, 'د. هند المطيري', 'Dr. Hind Al-Mutairi', 'h.almutairi@mu.edu.sa', 'INSTRUCTOR', [], true, '2026-09-01 08:00:00', '2026-09-26 06:40:00'],

            /*
             * An English-preference Super Admin, added for the visual suite — and it is a fixture
             * change rather than a test-only trick for a reason.
             *
             * SetLocale resolves user -> session -> query, so the only way to photograph a
             * signed-in screen in English is an account whose stored locale IS English. The suite
             * previously signed in as user 1 and flipped that row through /locale, which works
             * until two of four parallel workers do it at once: a baseline named `ar-*` was
             * captured in English mid-race, and committed. One account per locale removes the
             * shared row the race needs.
             */
            [8, 'منى الحربي', 'Mona Al-Harbi', 'm.alharbi@mu.edu.sa', 'SUPER_ADMIN', [], true, '2026-08-21 08:10:00', '2026-09-26 08:05:00', 'en'],
            [9, 'د. سارة القحطاني', 'Dr. Sara Al-Qahtani', 's.alqahtani@mu.edu.sa', 'BRANCH_COORDINATOR', [1, 2], true, '2026-09-02 11:00:00', '2026-09-24 10:10:00', 'en'],
            [10, 'أ. عمر الزهراني', 'Omar Al-Zahrani', 'o.alzahrani@mu.edu.sa', 'INSTRUCTOR', [], true, '2026-09-03 12:00:00', '2026-09-22 14:00:00', 'en'],
        ];

        foreach ($users as $row) {
            [$id, $ar, $en, $email, $role, $branchIds, $active, $firstLogin, $lastLogin] = $row;
            $locale = $row[9] ?? 'ar';

            // forceFill rather than updateOrCreate: `password` is deliberately absent from the
            // fillable list (it is set through an audited action), and the column is NOT NULL, so a
            // mass-assigned create would fail on the very first row.
            $user = User::query()->find($id) ?? new User;

            $user->forceFill([
                'id' => $id,
                'organization_id' => $organizationId,
                'name_ar' => $ar,
                'name_en' => $en,
                'email' => $email,
                'password' => $password,
                'locale' => $locale,
                'is_active' => $active,
                'first_login_at' => $firstLogin,
                'last_login_at' => $lastLogin,
            ])->saveQuietly();

            $user->syncRoles([$role]);
            $user->coordinatorBranches()->sync($branchIds);
        }
    }

    private function seedSections(): void
    {
        // [id, term, branch, course, number, instructor, program, degree]
        //
        // Sections 13 and 14 both carry number 123 in the same term and course but different
        // branches, which is FR-041's whole point: the number is unique per term+branch+course, never
        // on its own. Section 17 has no instructor. Section 18 sits in the closed term.
        $sections = [
            [11, 1, 4, 2, '214', 3, 1, 'bachelor'],
            [12, 1, 4, 1, '215', 3, 2, 'bachelor'],
            [13, 1, 1, 1, '123', 5, 3, 'bachelor'],
            [14, 1, 4, 1, '123', 7, 2, 'bachelor'],
            [15, 1, 3, 3, '305', 7, 5, 'bachelor'],
            [16, 1, 2, 4, '142', 5, 4, 'bachelor'],
            [17, 1, 5, 5, '157', null, 8, 'bachelor'],
            [18, 2, 4, 2, '214', 6, 1, 'bachelor'],
            [19, 1, 1, 6, '2298', 3, 5, 'intermediate_diploma'],
        ];

        foreach ($sections as [$id, $term, $branch, $course, $number, $instructor, $program, $degree]) {
            Section::query()->updateOrCreate(['id' => $id], [
                'term_id' => $term,
                'branch_id' => $branch,
                'course_id' => $course,
                'program_id' => $program,
                'section_number' => $number,
                'instructor_id' => $instructor,
                'degree' => $degree,
                'activity' => 'lecture',

                // All nine predate Amendment 4, so they are active and needed no approval - which is
                // exactly the no-backfill case the `active` default exists for (FR-173).
                'status' => 'active',
                'created_by' => 1,
                'requires_approval' => false,
            ]);
        }
    }

    private function seedGrantsAndHistory(): void
    {
        // Instructor 3 can reach section 15 without being its instructor - the "shared access" state
        // on the sections list (FR-038, FR-039).
        SectionAccessGrant::query()->updateOrCreate(
            ['section_id' => 15, 'user_id' => 3],
            ['granted_by' => 1, 'granted_at' => '2026-09-10 10:00:00'],
        );

        /*
         * A second grant, for instructor 5 on section 14 (taught by 7).
         *
         * Added in US2 so the visual suite can shoot the shared-access badge without signing in as
         * instructor 3 — that account is the never-signed-in one, and us1.spec.ts owns it for the
         * first-sign-in banner. Two specs consuming one account means whichever runs first wins and
         * the other photographs the wrong state.
         *
         * Instructor 5 teaches 13 and 16, so their list shows both states at once: two of their own
         * and one shared.
         */
        SectionAccessGrant::query()->updateOrCreate(
            ['section_id' => 14, 'user_id' => 5],
            ['granted_by' => 1, 'granted_at' => '2026-09-12 09:00:00'],
        );

        // Section 11 changed hands: one closed period and one open. The closed row names a user who
        // has since been deactivated, and it still reads correctly (FR-048).
        $history = [
            [11, 6, '2026-08-30 00:00:00', '2026-09-08 00:00:00'],
            [11, 3, '2026-09-08 00:00:00', null],
        ];

        foreach ($history as [$sectionId, $instructorId, $from, $to]) {
            SectionInstructorHistory::query()->updateOrCreate(
                ['section_id' => $sectionId, 'instructor_id' => $instructorId, 'assigned_at' => $from],
                ['unassigned_at' => $to, 'changed_by' => 1],
            );
        }
    }

    private function seedStudentsAndEnrollments(): void
    {
        $sectionIds = [11, 12, 13, 14, 15, 16, 17];

        // The kit's 36 generated students, reproduced by the same arithmetic so the ids, university
        // ids, and names match the reference exactly.
        $generated = [];

        for ($i = 0; $i < 36; $i++) {
            $generated[] = [
                'id' => 100 + $i,
                'university_id' => '44'.str_pad((string) (1002315 + $i * 37), 7, '0', STR_PAD_LEFT),
                'name_ar' => self::AR_FIRST[$i % 18].' '.self::AR_LAST[($i * 5) % 12],
                'name_en' => self::EN_FIRST[$i % 18].' '.self::EN_LAST[($i * 5) % 12],
            ];
        }

        foreach ($generated as $student) {
            Student::query()->updateOrCreate(['id' => $student['id']], [
                'university_id' => $student['university_id'],
                'name_ar' => $student['name_ar'],
                'name_en' => $student['name_en'],
            ]);
        }

        // Each student takes one section; every third takes a second. The dual enrollment is what
        // makes the "a student appears in two rosters" case testable.
        $enrollments = [];

        foreach ($generated as $i => $student) {
            $enrollments[] = [
                'student_id' => $student['id'],
                'section_id' => $sectionIds[$i % 7],
                'enrolled_at' => '2026-09-01 00:00:00',
            ];

            if ($i % 3 === 0) {
                $enrollments[] = [
                    'student_id' => $student['id'],
                    'section_id' => $sectionIds[($i + 2) % 7],
                    'enrolled_at' => '2026-09-02 00:00:00',
                ];
            }
        }

        // The registrar's standing, spread so the roster shows all three values.
        foreach ($enrollments as $i => $enrollment) {
            $enrollments[$i]['academic_status'] = match (true) {
                $i % 11 === 5 => 'withdrawn',
                $i % 13 === 7 => 'excused',
                default => 'regular',
            };
            $enrollments[$i]['roster_sequence'] = null;
            $enrollments[$i]['status'] = 'active';
            $enrollments[$i]['withdrawn_at'] = null;
            $enrollments[$i]['withdraw_reason'] = null;
        }

        // The nineteen rows from the real registrar export, enrolled in section 19 with their file
        // sequence preserved (FR-112).
        foreach (self::REGISTRAR_ROWS as $i => [$sequence, $universityId, $nameAr]) {
            Student::query()->updateOrCreate(['id' => 500 + $i], [
                'university_id' => $universityId,
                'name_ar' => $nameAr,
                'name_en' => null,
            ]);

            $enrollments[] = [
                'student_id' => 500 + $i,
                'section_id' => 19,
                'enrolled_at' => '2026-09-01 00:00:00',
                'academic_status' => 'regular',
                'roster_sequence' => (int) $sequence,
                'status' => 'active',
                'withdrawn_at' => null,
                'withdraw_reason' => null,
            ];
        }

        // The first enrollment in section 11 is withdrawn by TRACK while its registrar standing stays
        // `regular`. That disagreement is deliberate and is the only way to see FR-111 on a screen:
        // two columns, two authorities, neither overwriting the other.
        $enrollments[0]['status'] = 'withdrawn';
        $enrollments[0]['withdrawn_at'] = '2026-09-15 00:00:00';
        $enrollments[0]['withdraw_reason'] = 'انسحاب من المقرر عبر عمادة القبول';

        foreach ($enrollments as $i => $enrollment) {
            SectionEnrollment::query()->updateOrCreate(['id' => $i + 1], $enrollment);
        }
    }

    private function seedAuditEntries(): void
    {
        // Ten entries for the audit screen's baseline. Action keys follow contracts/audit.md rather
        // than the kit's wording - see the class docblock.
        $entries = [
            ['2026-09-26 07:55:12', 1, 'auth.login', 'User', 1, null, null],
            ['2026-09-25 14:02:40', 2, 'access.denied', 'Section', 13, null, ['reason' => 'out_of_scope']],
            ['2026-09-25 11:20:03', 1, 'import.committed', 'ImportJob', 1, null, [
                'file' => 'roster_1448_first.xlsx', 'processed' => 4210, 'imported' => 4102,
                'duplicates' => 64, 'errors' => 44,
            ]],
            ['2026-09-15 09:31:00', 3, 'enrollment.withdrawn', 'SectionEnrollment', 1,
                ['status' => 'active'],
                ['status' => 'withdrawn', 'withdraw_reason' => 'انسحاب من المقرر عبر عمادة القبول'],
            ],
            ['2026-09-10 10:00:00', 1, 'section.access_granted', 'Section', 15, null, ['user_id' => 3]],
            ['2026-09-08 08:12:45', 1, 'section.instructor_changed', 'Section', 11,
                ['instructor_id' => 6], ['instructor_id' => 3],
            ],
            ['2026-09-05 13:40:00', 1, 'user.deactivated', 'User', 6, ['is_active' => true], ['is_active' => false]],
            ['2026-09-01 08:00:00', 1, 'user.created', 'User', 3, null, [
                'name_ar' => 'د. فيصل العتيبي', 'email' => 'f.alotaibi@mu.edu.sa',
            ]],
            ['2026-08-28 10:15:00', 1, 'term.set_current', 'AcademicTerm', 1, ['is_current' => false], ['is_current' => true]],
            ['2026-08-27 09:00:00', 1, 'user.roles_changed', 'User', 2, ['branch_ids' => [3]], ['branch_ids' => [4]]],
        ];

        $addresses = ['10.12.4.21', '10.12.8.77', '10.12.4.21'];
        $agents = ['Chrome 128 / Windows', 'Edge 128 / Windows', 'Safari 17 / macOS'];

        DB::table('audit_logs')->whereIn('id', range(991, 1000))->delete();

        foreach ($entries as $i => [$at, $actor, $action, $entityType, $entityId, $old, $new]) {
            DB::table('audit_logs')->insert([
                'id' => 1000 - $i,
                'user_id' => $actor,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'new_values' => $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'ip_address' => $addresses[$i % 3],
                'user_agent' => $agents[$i % 3],
                'created_at' => $at,
            ]);
        }
    }
}
