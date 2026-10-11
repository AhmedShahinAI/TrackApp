<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

use App\Models\Branch;
use App\Models\Course;
use App\Models\ImportJob;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\User;
use App\Modules\Imports\Modes\CentralMode;
use App\Modules\Imports\Modes\CourseMode;
use App\Modules\Imports\Modes\RegistrarMode;
use App\Modules\Imports\Modes\SectionMode;
use App\Modules\Imports\Modes\UserMode;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\UniversityId;
use Spatie\Permission\Models\Role;

/**
 * T227 — every check the central import makes before anything is written (FR-057, FR-068).
 *
 * ## Read-only, and that is not an accident of the code
 *
 * This class runs inside ValidateImportJob, before the operator has seen a preview, so by FR-060 it
 * may create or change nothing. It holds no write call of any kind — every query below is a lookup —
 * and NoWritesBeforeConfirmTest snapshots the core tables across it to keep that true.
 *
 * ## Unscoped lookups, as in EnrollStudent
 *
 * A student, section or enrollment the importer cannot *currently* see still exists, and the unique
 * indexes will refuse a second one. Asking the scoped query whether a student exists answers "do I
 * see them", gets "no" for every student outside the caller's branches, and stages a row that will
 * fail at commit. So existence is asked of the table, not of the caller's view of it. Nothing found
 * here is returned to the caller except as "this row will link to an existing record".
 *
 * ## Batched, because the budget is ten thousand rows in five minutes (SC-007)
 *
 * A lookup per row per entity is four queries a row — forty thousand for a modest roster. Branches,
 * courses and programmes are small and loaded once; students, sections and enrollments are fetched
 * once per 500-row batch with `whereIn`; instructors are cached per distinct email, of which a
 * roster has dozens.
 *
 * ## Order of checks
 *
 * Structural faults first (missing values, a malformed id, a repeated row), then resolution. A row
 * that is already an error is not resolved further: telling the operator that the course of a row
 * with no university id is also unknown adds a second reason they cannot act on until the first is
 * fixed — the same order the approved reference uses.
 */
final class RowValidator
{
    /** @var array<string, int> "normalised campus|gender" => branch id, active branches only */
    private array $branches = [];

    /** @var array<string, array{id: int, program_ids: list<int>}> CODE => course */
    private array $courses = [];

    /** @var array<string, int> CODE => programme id, active programmes only */
    private array $programs = [];

    /** @var array<string, int|null> lower-cased email => user id, or null when there is no account */
    private array $instructors = [];

    /** @var array<string, int> identity key => the row number it first appeared on */
    private array $seen = [];

    /** @var array{valid: int, warnings: int, errors: int, skips: int} */
    private array $counts = ['valid' => 0, 'warnings' => 0, 'errors' => 0, 'skips' => 0];

    /** @var array<string, array<string, true>> distinct-entity sets behind the preview counts */
    private array $distinct = [
        'new_sections' => [], 'new_students' => [], 'new_courses' => [], 'new_instructors' => [],
        'matched_sections' => [], 'matched_students' => [],
        // US8 — the course and staff modes.
        'new_programs' => [], 'matched_courses' => [], 'new_accounts' => [],
    ];

    private int $newEnrollments = 0;

    private int $matchedEnrollments = 0;

    private readonly UniversityId $universityId;

    /** @var class-string<CentralMode|SectionMode|RegistrarMode|CourseMode|UserMode> */
    private readonly string $mode;

    /**
     * The registrar job's one section, as it stands when validation runs.
     *
     * @var array{branch_id: int, course_id: int|null, section_id: int|null, section_number: string, program_id: int|null, course_label: string}|null
     */
    private ?array $registrar = null;

    /**
     * Course mode: every programme, keyed by each way a file may name it (normalised code and both
     * names). A name two programmes share maps to null — never guessed between.
     *
     * @var array<string, array{id: int, active: bool}|null>
     */
    private array $programsByName = [];

    /** @var array<string, int> user mode: normalised role name or label => role name's index into */
    private array $roles = [];

    /** @var list<string> user mode: the role rows' names */
    private array $roleNames = [];

    /** @var list<array{id: int, names: list<string>, gender: string}> user mode: active branches */
    private array $staffBranches = [];

    /** @var list<array{key: string, params: array<string, string>}> job-level notices (registrarNotices) */
    private array $notices = [];

    /** @var array<int, int> registrar sequence => the row number it first appeared on (L5) */
    private array $seenSequences = [];

    public function __construct(private readonly ImportJob $job)
    {
        $this->universityId = new UniversityId;
        $this->mode = $job->modeClass();

        // A section list names no branch, course or programme, so there is nothing to look them up
        // against (T255).
        if ($this->mode === SectionMode::class) {
            return;
        }

        // A registrar file describes one section, resolved once for the whole job (T333).
        if ($this->mode === RegistrarMode::class) {
            $this->registrar = $this->registrarSection($job);

            return;
        }

        $organisationId = (int) User::query()->withTrashed()->whereKey($job->user_id)->value('organization_id');

        // T344 — the catalogue and its programmes, each loaded once: a college has hundreds of
        // courses and tens of programmes, and a lookup per row would be most of the check's cost.
        if ($this->mode === CourseMode::class) {
            $this->loadCatalogue($organisationId);

            return;
        }

        // T346 — the role rows and the organization's active branches, likewise loaded once.
        if ($this->mode === UserMode::class) {
            $this->loadStaffReferences($organisationId);

            return;
        }

        /*
         * Only active branches resolve. Importing a roster into a branch that has been deactivated is
         * how a closed campus quietly acquires a new term of sections; the operator is told the branch
         * is not recognised, which is true of it as a destination.
         */
        foreach (Branch::query()->where('organization_id', $organisationId)->where('is_active', true)->get() as $branch) {
            foreach ([$branch->campus_name_ar, $branch->campus_name_en] as $name) {
                $this->branches[ColumnMapper::normalise((string) $name).'|'.$branch->gender] = (int) $branch->id;
            }
        }

        // Active programmes only, for the same reason as Course::activeProgramIds: "the course's one
        // programme" must never be a deactivated one, which takes no new section.
        $activePrograms = static fn ($q) => $q->where('programs.is_active', true)->select('programs.id');

        foreach (Course::query()->where('organization_id', $organisationId)->with(['programs' => $activePrograms])->get() as $course) {
            $this->courses[self::code($course->code)] = [
                'id' => (int) $course->id,
                'program_ids' => $course->programs->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
            ];
        }

        foreach (Program::query()->where('organization_id', $organisationId)->where('is_active', true)->get(['id', 'code']) as $program) {
            $this->programs[self::code($program->code)] = (int) $program->id;
        }
    }

    /**
     * Validate one batch of mapped rows.
     *
     * @param  list<array{row_number: int, raw: array<string, string>}>  $batch
     * @return list<array{row_number: int, raw: array<string, string>, resolved: array<string, mixed>, status: string, reasons: list<array<string, mixed>>}>
     */
    public function validate(array $batch): array
    {
        $staged = array_map($this->checkStructure(...), $batch);

        $this->resolveBatch($staged);

        foreach ($staged as $row) {
            $this->tally($row);
        }

        return $staged;
    }

    /**
     * The create-versus-match counts the preview shows (FR-061), with the operator's create-or-skip
     * choices applied — a row that will be skipped creates and matches nothing.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'total_rows' => $this->counts['valid'] + $this->counts['warnings'] + $this->counts['errors'],
            'valid' => $this->counts['valid'],
            'warnings' => $this->counts['warnings'],
            'errors' => $this->counts['errors'],
            'will_skip' => $this->counts['skips'],
            // Registrar mode: facts about the job's one section, not any row (registrarNotices).
            'notices' => $this->notices,
            'will_create' => [
                'sections' => count($this->distinct['new_sections']),
                'students' => count($this->distinct['new_students']),
                'enrollments' => $this->newEnrollments,
                'courses' => count($this->distinct['new_courses']),
                'instructors' => count($this->distinct['new_instructors']),
            ] + $this->bulkCreates(),
            'will_match' => [
                'sections' => count($this->distinct['matched_sections']),
                'students' => count($this->distinct['matched_students']),
                'enrollments' => $this->matchedEnrollments,
            ] + ($this->mode === CourseMode::class ? ['courses' => count($this->distinct['matched_courses'])] : []),
        ];
    }

    /**
     * US8's counters, only in the summaries of the modes that produce them — the roster modes'
     * summary keeps the shape contracts/imports.md documents for it.
     *
     * @return array<string, int>
     */
    private function bulkCreates(): array
    {
        return match ($this->mode) {
            CourseMode::class => ['programs' => count($this->distinct['new_programs'])],
            UserMode::class => ['accounts' => count($this->distinct['new_accounts'])],
            default => [],
        };
    }

    /**
     * male / female from whatever the registrar wrote, or null when it is neither.
     */
    public static function gender(string $value): ?string
    {
        $normalised = ColumnMapper::normalise($value);

        return match (true) {
            in_array($normalised, ['طلاب', 'بنين', 'ذكور', 'ذكر', 'رجال', 'male', 'm', 'boys', 'men'], true) => 'male',
            in_array($normalised, ['طالبات', 'بنات', 'اناث', 'انثي', 'نساء', 'female', 'f', 'girls', 'women'], true) => 'female',
            default => null,
        };
    }

    /**
     * A section number as one section, however it was written (FR-066).
     *
     * Trimmed, Arabic-Indic digits folded to Western ones, upper-cased, and leading zeros dropped
     * from an all-digit number. Compared raw, "١", "1" and "01" were three sections: the in-file
     * check let all three through, the lookup matched none of them to the existing section, and the
     * approval_key unique index could not help because the stored strings differed.
     */
    public static function sectionNumber(string $value): string
    {
        $number = mb_strtoupper((string) preg_replace('/\s+/u', '', UniversityId::normalise($value)));

        if (preg_match('/^\d+$/', $number) === 1) {
            $number = ltrim($number, '0');

            return $number === '' ? '0' : $number;
        }

        return $number;
    }

    /** Course and programme codes compare upper-cased and unspaced, as they are stored. */
    public static function code(string $value): string
    {
        return mb_strtoupper((string) preg_replace('/\s+/u', '', $value));
    }

    /**
     * @param  array{row_number: int, raw: array<string, string>}  $row
     * @return array{row_number: int, raw: array<string, string>, resolved: array<string, mixed>, status: string, reasons: list<array<string, mixed>>}
     */
    private function checkStructure(array $row): array
    {
        $raw = $row['raw'];
        $reasons = [];

        foreach ($this->mode::required() as $target) {
            if (($raw[$target] ?? '') === '') {
                $reasons[] = ImportReasons::make(ImportReasons::MISSING_REQUIRED, $target, '', ['column' => $target]);
            }
        }

        $universityId = UniversityId::normalise($raw['university_id'] ?? '');

        if ($universityId !== '' && ! $this->universityId->matches($universityId)) {
            $reasons[] = ImportReasons::make(
                ImportReasons::INVALID_UNIVERSITY_ID, 'university_id', $raw['university_id'] ?? '',
                // The parts of the format, not the sentence: the reason is rendered in whichever
                // language its reader uses, possibly long after this job has finished.
                $this->universityId->digits() !== null
                    ? ['digits' => $this->universityId->digits()]
                    : ['pattern' => $this->universityId->pattern()],
            );
        }

        $identity = implode('|', array_map(
            static fn (string $target): string => match ($target) {
                'university_id' => $universityId,
                'section_number' => self::sectionNumber($raw[$target] ?? ''),
                'course_code' => self::code($raw[$target] ?? ''),
                'email' => mb_strtolower(trim($raw[$target] ?? '')),
                default => ColumnMapper::normalise($raw[$target] ?? ''),
            },
            $this->mode::IDENTITY,
        ));

        if (isset($this->seen[$identity])) {
            $reasons[] = ImportReasons::make(
                // Against the column that makes the row "the same row": the student for a roster, the
                // code for a course, the email for an account.
                ImportReasons::DUPLICATE_IN_FILE, $this->identityColumn(), $raw[$this->identityColumn()] ?? '',
                ['row' => $this->seen[$identity]],
            );
        } else {
            $this->seen[$identity] = $row['row_number'];
        }

        return [
            'row_number' => $row['row_number'],
            'raw' => $raw,
            'resolved' => ['university_id' => $universityId],
            'status' => $reasons === [] ? 'valid' : 'error',
            'reasons' => $reasons,
        ];
    }

    /**
     * Branch, course, section, instructor, student and enrollment — in that order, because each
     * needs the one before it.
     *
     * @param  list<array{row_number: int, raw: array<string, string>, resolved: array<string, mixed>, status: string, reasons: list<array<string, mixed>>}>  $staged
     */
    private function resolveBatch(array &$staged): void
    {
        if ($this->mode === SectionMode::class) {
            $this->resolveSectionBatch($staged);

            return;
        }

        if ($this->mode === RegistrarMode::class) {
            $this->resolveRegistrarBatch($staged);

            return;
        }

        if ($this->mode === CourseMode::class) {
            $this->resolveCourseBatch($staged);

            return;
        }

        if ($this->mode === UserMode::class) {
            $this->resolveUserBatch($staged);

            return;
        }

        foreach ($staged as &$row) {
            if ($row['status'] === 'error') {
                continue;
            }

            $raw = $row['raw'];
            $gender = self::gender($raw['gender']);
            $branchId = $gender === null ? null : ($this->branches[ColumnMapper::normalise($raw['branch']).'|'.$gender] ?? null);

            if ($branchId === null) {
                $this->add($row, 'error', ImportReasons::make(
                    ImportReasons::UNKNOWN_BRANCH, 'branch', $raw['branch'].' — '.$raw['gender'],
                    ['value' => $raw['branch'].' — '.$raw['gender']],
                ));

                continue;
            }

            $code = self::code($raw['course_code']);
            $course = $this->courses[$code] ?? null;

            if ($course === null) {
                $this->add($row, 'warning', ImportReasons::make(
                    ImportReasons::UNKNOWN_COURSE, 'course_code', $raw['course_code'], ['value' => $raw['course_code']],
                ));
            }

            $row['resolved'] += [
                'branch_id' => $branchId,
                'course_code' => $code,
                'course_id' => $course['id'] ?? null,
                'section_number' => self::sectionNumber($raw['section_number']),
                'section_key' => implode('|', [$this->job->term_id, $branchId, $code, self::sectionNumber($raw['section_number'])]),
            ];
        }
        unset($row);

        $sections = $this->existingSections($staged);
        $students = $this->existingStudents($staged);

        foreach ($staged as &$row) {
            if ($row['status'] === 'error') {
                continue;
            }

            $this->resolveSection($row, $sections);

            if ($row['status'] === 'error') {
                continue;
            }

            $this->resolveStudent($row, $students);
        }
        unset($row);

        $enrollments = $this->existingEnrollments($staged);

        foreach ($staged as &$row) {
            $sectionId = $row['resolved']['section_id'] ?? null;
            $studentId = $row['resolved']['student_id'] ?? null;

            $row['resolved']['enrollment_exists'] = $sectionId !== null && $studentId !== null
                && isset($enrollments[$sectionId.'|'.$studentId]);
        }
        unset($row);
    }

    /**
     * T255 — the section mode: the section is the job's, so only the student and the enrollment are
     * resolved. Nothing read from the file can name, create or redirect to another section (FR-053,
     * FR-054); the name-mismatch warning and the link-not-duplicate rule are the central mode's own.
     *
     * @param  list<array{row_number: int, raw: array<string, string>, resolved: array<string, mixed>, status: string, reasons: list<array<string, mixed>>}>  $staged
     */
    private function resolveSectionBatch(array &$staged): void
    {
        $students = $this->existingStudents($staged);

        // Who is already in this section — the only students whose stored record the instructor can
        // already see, and so the only ones whose stored name a reason may repeat back (resolveStudent).
        $studentIds = array_values(array_map(static fn (array $s): int => $s['id'], $students));
        $enrolledHere = $studentIds === [] ? [] : array_fill_keys(
            SectionEnrollment::query()->withoutGlobalScopes()
                ->where('section_id', $this->job->section_id)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
            true,
        );

        foreach ($staged as &$row) {
            if ($row['status'] === 'error') {
                continue;
            }

            $row['resolved']['section_id'] = (int) $this->job->section_id;
            $this->resolveStudent($row, $students, $enrolledHere);
        }
        unset($row);

        foreach ($staged as &$row) {
            $studentId = $row['resolved']['student_id'] ?? null;

            $row['resolved']['enrollment_exists'] = $studentId !== null && isset($enrolledHere[$studentId]);
        }
        unset($row);
    }

    /**
     * T333 — the registrar mode. Every row joins the job's one section; what varies per row is the
     * student, and the registrar's sequence and academic status (FR-110, FR-112).
     *
     * Sequence and status that cannot be read are warnings, stored null — the row still imports
     * (contracts/imports.md). A course the file names ambiguously, with no choice made at the
     * confirmation step, flags every row `ambiguous_course`; those rows are skipped at commit rather
     * than attached to one of two same-named courses by guess (FR-100).
     *
     * @param  list<array{row_number: int, raw: array<string, string>, resolved: array<string, mixed>, status: string, reasons: list<array<string, mixed>>}>  $staged
     */
    private function resolveRegistrarBatch(array &$staged): void
    {
        $context = $this->registrar;
        $students = $this->existingStudents($staged);

        foreach ($staged as &$row) {
            if ($row['status'] === 'error' || $context === null) {
                continue;
            }

            $raw = $row['raw'];
            $sequence = RegistrarMode::sequence($raw['sequence'] ?? '');
            $status = RegistrarMode::academicStatus($raw['academic_status'] ?? '');

            if ($sequence === null) {
                $this->add($row, 'warning', ImportReasons::make(ImportReasons::INVALID_SEQUENCE, 'sequence', $raw['sequence'] ?? ''));
            } elseif (isset($this->seenSequences[$sequence])) {
                // Kept, not refused: the number is still the registrar's best order for the row, and
                // the roster breaks the tie by id. The operator is told the file repeats it (L5).
                $this->add($row, 'warning', ImportReasons::make(
                    ImportReasons::DUPLICATE_SEQUENCE, 'sequence', $raw['sequence'] ?? '',
                    ['row' => $this->seenSequences[$sequence]],
                ));
            } else {
                $this->seenSequences[$sequence] = $row['row_number'];
            }

            if ($status === null) {
                $this->add($row, 'warning', ImportReasons::make(
                    ImportReasons::UNKNOWN_ACADEMIC_STATUS, 'academic_status', $raw['academic_status'] ?? '',
                    ['value' => $raw['academic_status'] ?? ''],
                ));
            }

            $row['resolved'] += [
                'branch_id' => $context['branch_id'],
                'course_id' => $context['course_id'],
                'section_id' => $context['section_id'],
                'section_number' => $context['section_number'],
                'section_key' => implode('|', [$this->job->term_id, $context['branch_id'], $context['course_id'], $context['section_number']]),
                'program_id' => $context['program_id'],
                'roster_sequence' => $sequence,
                'academic_status' => $status,
            ];

            if ($context['course_id'] === null) {
                $this->add($row, 'warning', ImportReasons::make(
                    ImportReasons::AMBIGUOUS_COURSE, 'course', $context['course_label'], ['value' => $context['course_label']],
                ));
            } elseif ($context['section_id'] === null && $context['program_id'] === null) {
                // A section to be created needs a programme (FR-090), and neither the course nor the
                // operator supplied exactly one.
                $this->add($row, 'error', ImportReasons::make(
                    ImportReasons::UNRESOLVABLE_SECTION, 'section_number', $context['section_number'],
                    ['value' => $context['course_label'].' / '.$context['section_number']],
                ));

                continue;
            }

            $this->resolveStudent($row, $students);
        }
        unset($row);

        $enrollments = $this->existingEnrollments($staged);

        foreach ($staged as &$row) {
            $sectionId = $row['resolved']['section_id'] ?? null;
            $studentId = $row['resolved']['student_id'] ?? null;

            $row['resolved']['enrollment_exists'] = $sectionId !== null && $studentId !== null
                && isset($enrollments[$sectionId.'|'.$studentId]);
        }
        unset($row);
    }

    /**
     * The registrar job's section, resolved again now rather than trusted from upload: another
     * import may have created the section since, and a choice made at the confirmation step settles
     * an ambiguous course.
     *
     * @return array{branch_id: int, course_id: int|null, section_id: int|null, section_number: string, program_id: int|null, course_label: string}|null
     */
    private function registrarSection(ImportJob $job): ?array
    {
        $resolution = $job->registrarResolution();

        if ($resolution === null) {
            return null;
        }

        $chosen = (int) ($job->options['course_id'] ?? 0);
        $courseId = $resolution['course_id']
            ?? (in_array($chosen, $resolution['course_candidates'], true) ? $chosen : null);

        $sectionId = $courseId === null ? null : ResolveRegistrarSection::existingSection(
            (int) $job->term_id, (int) $resolution['branch_id'], $courseId, (string) $resolution['section_number'],
        );

        $programId = null;

        if ($courseId !== null && $sectionId === null) {
            /*
             * The new section's programme: the operator's choice if it is one of THIS course's active
             * programmes, else the course's single active one. Re-checked here, not only at mapping:
             * a programme deactivated, or detached from the course, between the confirmation and the
             * check must not receive a new section (FR-090, FR-092).
             */
            $active = Course::query()->findOrFail($courseId)->activeProgramIds();
            $chosenProgram = (int) ($job->options['program_id'] ?? 0);

            $programId = in_array($chosenProgram, $active, true)
                ? $chosenProgram
                : (count($active) === 1 ? $active[0] : null);
        }

        $this->notices = $this->registrarNotices($job, $resolution, $sectionId);

        return [
            'branch_id' => (int) $resolution['branch_id'],
            'course_id' => $courseId,
            'section_id' => $sectionId,
            'section_number' => (string) $resolution['section_number'],
            'program_id' => $programId,
            'course_label' => (string) ($job->file_metadata['registrar']['course'] ?? ''),
        ];
    }

    /** The column whose value makes two rows "the same row", for the duplicate reason. */
    private function identityColumn(): string
    {
        return in_array($this->mode, [CourseMode::class, UserMode::class], true) ? $this->mode::IDENTITY[0] : 'university_id';
    }

    /** A programme or branch name for comparison: normalised, with Arabic-Indic digits folded. */
    public static function nameKey(string $value): string
    {
        return ColumnMapper::normalise(UniversityId::normalise($value));
    }

    private function loadCatalogue(int $organisationId): void
    {
        foreach (Course::query()->where('organization_id', $organisationId)->get(['id', 'code']) as $course) {
            $this->courses[self::code((string) $course->code)] = ['id' => (int) $course->id, 'program_ids' => []];
        }

        foreach (Program::query()->where('organization_id', $organisationId)->get(['id', 'code', 'name_ar', 'name_en', 'is_active']) as $program) {
            $entry = ['id' => (int) $program->id, 'active' => (bool) $program->is_active];

            foreach (array_unique([self::nameKey((string) $program->code), self::nameKey((string) $program->name_ar), self::nameKey((string) $program->name_en)]) as $key) {
                if ($key === '') {
                    continue;
                }

                // Two programmes answering to one name: the name settles nothing (null), and the
                // row is refused rather than attached to either.
                $this->programsByName[$key] = array_key_exists($key, $this->programsByName)
                    && ($this->programsByName[$key] === null || $this->programsByName[$key]['id'] !== $entry['id'])
                    ? null
                    : $entry;
            }
        }
    }

    private function loadStaffReferences(int $organisationId): void
    {
        /*
         * Staff roles only — coordinator and instructor, the staff FR-104 loads — never every role
         * row. Taken from every role, a spreadsheet cell could mint a SUPER_ADMIN with
         * `scope.bypass_all`: harmless only while Super Admins alone hold `import.user.run`, and an
         * escalation the day the permission is granted to anyone else. A Super Admin account is
         * created deliberately, on the form.
         */
        $this->roleNames = Role::query()->where('guard_name', 'web')
            ->whereIn('name', UserMode::ROLES)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        foreach ($this->roleNames as $index => $name) {
            // The role's own name, and its label in either language, as a person would type it.
            $spellings = [$name, (string) __('users.roles.'.$name, [], 'ar'), (string) __('users.roles.'.$name, [], 'en')];

            foreach ($spellings as $spelling) {
                if ($spelling !== '' && ! str_starts_with($spelling, 'users.roles.')) {
                    $this->roles[ColumnMapper::normalise($spelling)] = $index;
                }
            }
        }

        foreach (Branch::query()->where('organization_id', $organisationId)->where('is_active', true)->get(['id', 'campus_name_ar', 'campus_name_en', 'gender']) as $branch) {
            $this->staffBranches[] = [
                'id' => (int) $branch->id,
                'names' => array_values(array_filter([self::nameKey((string) $branch->campus_name_ar), self::nameKey((string) $branch->campus_name_en)])),
                'gender' => (string) $branch->gender,
            ];
        }
    }

    /**
     * Refuse a value its column cannot hold, naming the column and its limit.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, array{0: string, 1: int}>  $values  column => [value, max length]
     */
    private function refuseTooLong(array &$row, array $values): void
    {
        foreach ($values as $column => [$value, $max]) {
            if (mb_strlen($value) > $max) {
                $this->add($row, 'error', ImportReasons::make(ImportReasons::TOO_LONG, $column, $value, ['column' => $column, 'max' => $max]));
            }
        }
    }

    /**
     * T344 — courses. Existing codes are matched; each named programme is resolved, flagged as
     * unknown (create-or-skip) or deactivated (associate only if confirmed), or refused as ambiguous.
     *
     * @param  list<array{row_number: int, raw: array<string, string>, resolved: array<string, mixed>, status: string, reasons: list<array<string, mixed>>}>  $staged
     */
    private function resolveCourseBatch(array &$staged): void
    {
        foreach ($staged as &$row) {
            if ($row['status'] === 'error') {
                continue;
            }

            $raw = $row['raw'];
            $code = self::code($raw['course_code']);
            $nameAr = trim($raw['name_ar']);
            $nameEn = trim($raw['name_en'] ?? '') !== '' ? trim($raw['name_en']) : $nameAr;

            $this->refuseTooLong($row, ['course_code' => [$code, 20], 'name_ar' => [$nameAr, 255], 'name_en' => [$nameEn, 255]]);

            if ($row['status'] === 'error') {
                continue;
            }

            $programIds = [];
            $inactiveIds = [];
            $unknown = [];
            $labels = CourseMode::list($raw['programs']);

            foreach ($labels as $label) {
                $key = self::nameKey($label);

                if (array_key_exists($key, $this->programsByName) && $this->programsByName[$key] === null) {
                    $this->add($row, 'error', ImportReasons::make(ImportReasons::AMBIGUOUS_PROGRAM, 'programs', $label, ['value' => $label]));

                    continue;
                }

                $match = $this->programsByName[$key] ?? null;

                if ($match === null) {
                    $unknown[] = $label;
                    $this->add($row, 'warning', ImportReasons::make(ImportReasons::UNKNOWN_PROGRAM, 'programs', $label, ['value' => $label]));
                } elseif (! $match['active']) {
                    $inactiveIds[] = $match['id'];
                    $this->add($row, 'warning', ImportReasons::make(ImportReasons::INACTIVE_PROGRAM, 'programs', $label, ['value' => $label]));
                } else {
                    $programIds[] = $match['id'];
                }
            }

            // A cell holding only separators names no programme.
            if ($labels === []) {
                $this->add($row, 'error', ImportReasons::make(ImportReasons::MISSING_REQUIRED, 'programs', '', ['column' => 'programs']));
            }

            if ($row['status'] === 'error') {
                continue;
            }

            $existing = $this->courses[$code] ?? null;

            if ($existing !== null) {
                $row['reasons'][] = ImportReasons::make(ImportReasons::EXISTING_COURSE, 'course_code', $code);
            }

            $row['resolved'] += [
                'course_code' => $code,
                'course_id' => $existing['id'] ?? null,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'program_ids' => array_values(array_unique($programIds)),
                'inactive_program_ids' => array_values(array_unique($inactiveIds)),
                'new_programs' => $unknown,
            ];
        }
        unset($row);
    }

    /**
     * T346 — staff accounts. Every refusal is the row's own (contracts/imports.md, `user`): a taken
     * email, an unknown role or branch, a coordinator with no branch.
     *
     * @param  list<array{row_number: int, raw: array<string, string>, resolved: array<string, mixed>, status: string, reasons: list<array<string, mixed>>}>  $staged
     */
    private function resolveUserBatch(array &$staged): void
    {
        // One lookup per batch. withTrashed: a deactivated account still holds its email (FR-005).
        $emails = array_values(array_unique(array_filter(array_map(
            static fn (array $r): string => $r['status'] === 'error' ? '' : mb_strtolower(trim($r['raw']['email'])),
            $staged,
        ))));

        $taken = $emails === [] ? [] : array_fill_keys(
            User::query()->withTrashed()->whereIn('email', $emails)->pluck('email')
                ->map(static fn (mixed $e): string => mb_strtolower((string) $e))->all(),
            true,
        );

        foreach ($staged as &$row) {
            if ($row['status'] === 'error') {
                continue;
            }

            $raw = $row['raw'];
            $email = mb_strtolower(trim($raw['email']));
            $nameAr = trim($raw['name_ar']);
            $nameEn = trim($raw['name_en']);

            $this->refuseTooLong($row, ['name_ar' => [$nameAr, 255], 'name_en' => [$nameEn, 255], 'email' => [$email, 255]]);

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $this->add($row, 'error', ImportReasons::make(ImportReasons::INVALID_EMAIL, 'email', $raw['email'], ['value' => $raw['email']]));
            } elseif (isset($taken[$email])) {
                $this->add($row, 'error', ImportReasons::make(ImportReasons::EMAIL_TAKEN, 'email', $email, ['value' => $email]));
            }

            $roleIndex = $this->roles[ColumnMapper::normalise($raw['role'])] ?? null;
            $role = $roleIndex === null ? null : $this->roleNames[$roleIndex];

            if ($role === null) {
                $this->add($row, 'error', ImportReasons::make(ImportReasons::UNKNOWN_ROLE, 'role', $raw['role'], ['value' => $raw['role']]));
            }

            $branchIds = [];

            // Only a coordinator's branches mean anything (FR-004); for any other role the column is
            // ignored (contracts/imports.md), so an instructor row is never refused over a branch it
            // would not have been given.
            foreach ($role === UserMode::COORDINATOR ? CourseMode::list($raw['branches'] ?? '') : [] as $label) {
                $branchId = $this->staffBranch($label);

                if ($branchId === null) {
                    $this->add($row, 'error', ImportReasons::make(ImportReasons::UNKNOWN_BRANCH, 'branches', $label, ['value' => $label]));
                } else {
                    $branchIds[] = $branchId;
                }
            }

            // FR-004. Only when the branches named were all understood: a coordinator whose one
            // branch was unknown has already been told so, and "needs a branch" would contradict it.
            if ($role === UserMode::COORDINATOR && $branchIds === [] && $row['status'] !== 'error') {
                $this->add($row, 'error', ImportReasons::make(ImportReasons::COORDINATOR_NEEDS_BRANCH, 'branches', $raw['branches'] ?? ''));
            }

            $row['resolved'] += [
                'email' => $email,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'role' => $role,
                // A branch claim is a coordinator's (FR-004); for any other role it grants nothing.
                'branch_ids' => $role === UserMode::COORDINATOR ? array_values(array_unique($branchIds)) : [],
            ];
        }
        unset($row);
    }

    /**
     * One active branch for a label such as «<campus> (طلاب)», or null for none or several.
     */
    private function staffBranch(string $label): ?int
    {
        [$campus, $gender] = ResolveBranchFromCampus::split($label);
        $name = self::nameKey($campus);

        $ids = array_values(array_map(
            static fn (array $b): int => $b['id'],
            array_filter($this->staffBranches, static fn (array $b): bool => in_array($name, $b['names'], true)
                && ($gender === null || $b['gender'] === $gender)),
        ));

        return count($ids) === 1 ? $ids[0] : null;
    }

    /**
     * M3 — what the operator must be told about the job's ONE section that is not about any row: the
     * registrar's degree or activity is not in the settings list (a new section then takes the list's
     * first value), or an existing section stores a different one (and keeps its own — an import does
     * not re-describe a section someone set up). Shown on the preview and kept in the summary, which
     * `import.committed` carries, so the substitution is never silent.
     *
     * @param  array<string, mixed>  $resolution
     * @return list<array{key: string, params: array<string, string>}>
     */
    private function registrarNotices(ImportJob $job, array $resolution, ?int $sectionId): array
    {
        $given = $job->file_metadata['registrar'] ?? [];
        $existing = $sectionId === null ? null
            : Section::query()->withoutGlobalScopes()->whereKey($sectionId)->first(['id', 'degree', 'activity']);
        $notices = [];

        foreach (['degree' => Settings::SECTION_DEGREES, 'activity' => Settings::SECTION_ACTIVITIES] as $field => $key) {
            $label = trim((string) ($given[$field] ?? ''));
            $value = $resolution[$field] ?? null;

            if ($label !== '' && $value === null) {
                $notices[] = ['key' => $field.'_unknown', 'params' => [
                    'value' => $label,
                    'default' => (string) (Settings::values($key)[0] ?? ''),
                ]];
            } elseif ($existing !== null && $value !== null && $existing->{$field} !== $value) {
                $notices[] = ['key' => $field.'_differs', 'params' => [
                    'value' => $label,
                    'stored' => (string) $existing->{$field},
                ]];
            }
        }

        return $notices;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $sections
     */
    private function resolveSection(array &$row, array $sections): void
    {
        $raw = $row['raw'];
        $resolved = &$row['resolved'];
        $sectionId = $resolved['course_id'] === null ? null : ($sections[$this->sectionKey($resolved)] ?? null);

        $resolved['section_id'] = $sectionId;

        if ($sectionId !== null) {
            return;
        }

        // The column is varchar(20). A longer value cannot be a section number the college uses,
        // and cannot be stored as one.
        if (mb_strlen($resolved['section_number']) > 20) {
            $this->add($row, 'error', ImportReasons::make(
                ImportReasons::UNRESOLVABLE_SECTION, 'section_number', $raw['section_number'], ['value' => $raw['section_number']],
            ));

            return;
        }

        $programId = $this->programFor($raw, $resolved['course_code']);

        /*
         * A new course with "skip" chosen will not be imported at all, so its section's programme is
         * not a question — the row stays the unknown-course warning it is and is skipped at commit.
         * Only a section that would really be created needs a programme it cannot be given.
         */
        if ($programId === null && $resolved['course_id'] === null && ! $this->job->option('create_missing_courses')) {
            return;
        }

        if ($programId === null) {
            $this->add($row, 'error', ImportReasons::make(
                ImportReasons::UNRESOLVABLE_SECTION, 'program_code', $raw['program_code'] ?? '',
                ['value' => $raw['course_code'].' / '.$raw['section_number']],
            ));

            return;
        }

        $resolved['program_id'] = $programId;

        // The instructor matters only to a section being created. An existing section keeps the
        // instructor it has: changing it from a file would move access without the history row and
        // the audit entry ChangeSectionInstructor exists to write (FR-048).
        $email = mb_strtolower(trim($raw['instructor_email']));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->add($row, 'error', ImportReasons::make(
                ImportReasons::INVALID_EMAIL, 'instructor_email', $raw['instructor_email'], ['value' => $raw['instructor_email']],
            ));

            return;
        }

        $resolved['instructor_email'] = $email;
        $resolved['instructor_id'] = $this->instructorId($email);

        if ($resolved['instructor_id'] === null) {
            $this->add($row, 'warning', ImportReasons::make(
                ImportReasons::UNKNOWN_INSTRUCTOR, 'instructor_email', $raw['instructor_email'], ['value' => $raw['instructor_email']],
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, array{id: int, name_ar: string, name_en: ?string, trashed: bool}>  $students
     * @param  array<int, true>|null  $enrolledHere  students already in the job's section; null in the central mode
     */
    private function resolveStudent(array &$row, array $students, ?array $enrolledHere = null): void
    {
        $existing = $students[$row['resolved']['university_id']] ?? null;
        $row['resolved']['student_id'] = $existing['id'] ?? null;

        if ($existing === null) {
            return;
        }

        /*
         * A deleted student is not brought back by an instructor's list.
         *
         * The central import restores one (EnrollStudent does too): a person on the registration
         * unit's term roster is a current student, and the roster is the authority. A list an
         * instructor typed is not that authority, and restoring from it let an instructor undo an
         * administrator's deletion without anyone deciding to.
         */
        if ($this->mode === SectionMode::class && $existing['trashed']) {
            $row['resolved']['student_id'] = null;
            $this->add($row, 'error', ImportReasons::make(
                ImportReasons::STUDENT_UNAVAILABLE, 'university_id', $row['resolved']['university_id'],
            ));

            return;
        }

        // FR-069: the stored name wins, and the operator is told it differs.
        if (! self::sameName($row['raw']['student_name'], $existing)) {
            /*
             * The stored name is repeated back only to someone who may already see it.
             *
             * The section mode's lookup is unscoped — it has to be, to link rather than duplicate —
             * so it finds every student in the university. Putting their stored name in the reason
             * turned the preview into a directory: upload made-up ids with a placeholder name, read
             * the real names off the preview, never commit. In this mode the name is shown only for a
             * student already in the job's section; for anyone else the reason says only that the
             * name differs. The central mode is a Super Admin's, who sees every student anyway.
             */
            $disclose = $enrolledHere === null || isset($enrolledHere[$existing['id']]);

            $this->add($row, 'warning', ImportReasons::make(
                ImportReasons::NAME_MISMATCH, 'student_name', $row['raw']['student_name'],
                $disclose ? ['stored' => $existing['name_ar']] : [],
            ));

            return;
        }

        $row['reasons'][] = ImportReasons::make(ImportReasons::EXISTING_STUDENT, 'university_id', $row['resolved']['university_id']);
    }

    /**
     * Whether a name in the file is the stored student's name, written differently or not at all.
     *
     * Compared after ColumnMapper::normalise — hamza and alef forms, alef maqsura, taa marbuta,
     * tatweel, diacritics, spacing and Latin case folded — and against both stored names. Compared
     * raw, "محمد أحمد" and "محمد احمد" were a mismatch, an English-language file flagged every
     * existing student, and the warnings that mattered were lost among the ones that did not.
     *
     * @param  array{name_ar: string, name_en: ?string}  $stored
     */
    public static function sameName(string $given, array $stored): bool
    {
        $given = ColumnMapper::normalise($given);

        foreach ([$stored['name_ar'], $stored['name_en'] ?? ''] as $name) {
            if ($name !== '' && ColumnMapper::normalise($name) === $given) {
                return true;
            }
        }

        return false;
    }

    /**
     * The programme a new section would belong to (FR-090), or null when it cannot be known.
     *
     * In order: the row's own programme code; the course's programme, when it has exactly one; and
     * for a course this import will create, the programme the operator chose for new courses at the
     * mapping step. Never a guess among several.
     *
     * @param  array<string, string>  $raw
     */
    private function programFor(array $raw, string $courseCode): ?int
    {
        $programCode = self::code($raw['program_code'] ?? '');

        if ($programCode !== '') {
            return $this->programs[$programCode] ?? null;
        }

        if (isset($this->courses[$courseCode])) {
            $programIds = $this->courses[$courseCode]['program_ids'];

            return count($programIds) === 1 ? $programIds[0] : null;
        }

        $chosen = (int) ($this->job->options['new_course_program_id'] ?? 0);

        return in_array($chosen, $this->programs, true) ? $chosen : null;
    }

    private function instructorId(string $email): ?int
    {
        if (! array_key_exists($email, $this->instructors)) {
            /*
             * withTrashed: a deactivated account still holds its email (FR-005), so it is still the
             * person the file names. Treating them as "no account" would offer to create a second
             * account the unique index then refuses.
             */
            $id = User::query()->withTrashed()->where('email', $email)->value('id');
            $this->instructors[$email] = $id === null ? null : (int) $id;
        }

        return $this->instructors[$email];
    }

    /**
     * @param  list<array<string, mixed>>  $staged
     * @return array<string, int> section key => id
     */
    private function existingSections(array $staged): array
    {
        $candidates = array_filter($staged, static fn (array $r): bool => $r['status'] !== 'error' && $r['resolved']['course_id'] !== null);

        if ($candidates === []) {
            return [];
        }

        $sections = Section::query()
            ->withoutGlobalScopes()
            ->where('term_id', $this->job->term_id)
            ->whereIn('branch_id', array_unique(array_column(array_column($candidates, 'resolved'), 'branch_id')))
            ->whereIn('course_id', array_unique(array_column(array_column($candidates, 'resolved'), 'course_id')))
            // No section_number filter: stored numbers are compared after sectionNumber(), so "01"
            // in the database still matches "1" in the file. A term's sections of one course in one
            // branch are a handful, so this widens the read very little.
            // A rejected section has released its number (FR-176); it is not the section a roster means.
            ->where('status', '!=', 'rejected')
            ->get(['id', 'branch_id', 'course_id', 'section_number']);

        $byKey = [];

        foreach ($sections as $section) {
            $byKey[$section->branch_id.'|'.$section->course_id.'|'.self::sectionNumber((string) $section->section_number)] = (int) $section->id;
        }

        return $byKey;
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function sectionKey(array $resolved): string
    {
        return $resolved['branch_id'].'|'.$resolved['course_id'].'|'.$resolved['section_number'];
    }

    /**
     * @param  list<array<string, mixed>>  $staged
     * @return array<string, array{id: int, name_ar: string, name_en: ?string, trashed: bool}>
     */
    private function existingStudents(array $staged): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (array $r): string => $r['status'] === 'error' ? '' : (string) $r['resolved']['university_id'],
            $staged,
        ))));

        if ($ids === []) {
            return [];
        }

        $byId = [];

        $found = Student::query()->withoutGlobalScopes()->withTrashed()
            ->whereIn('university_id', $ids)
            ->get(['id', 'university_id', 'name_ar', 'name_en', 'deleted_at']);

        foreach ($found as $student) {
            $byId[$student->university_id] = [
                'id' => (int) $student->id,
                'name_ar' => (string) $student->name_ar,
                'name_en' => $student->name_en,
                'trashed' => $student->trashed(),
            ];
        }

        return $byId;
    }

    /**
     * @param  list<array<string, mixed>>  $staged
     * @return array<string, true> "section|student" => exists
     */
    private function existingEnrollments(array $staged): array
    {
        $sectionIds = array_values(array_unique(array_filter(array_map(static fn (array $r): mixed => $r['resolved']['section_id'] ?? null, $staged))));
        $studentIds = array_values(array_unique(array_filter(array_map(static fn (array $r): mixed => $r['resolved']['student_id'] ?? null, $staged))));

        if ($sectionIds === [] || $studentIds === []) {
            return [];
        }

        $pairs = [];

        foreach (SectionEnrollment::query()->withoutGlobalScopes()->whereIn('section_id', $sectionIds)->whereIn('student_id', $studentIds)->get(['section_id', 'student_id']) as $enrollment) {
            $pairs[$enrollment->section_id.'|'.$enrollment->student_id] = true;
        }

        return $pairs;
    }

    /**
     * Add a reason and raise the row's status to at least the given severity.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $reason
     */
    private function add(array &$row, string $severity, array $reason): void
    {
        $row['reasons'][] = $reason;

        if ($severity === 'error' || ($severity === 'warning' && $row['status'] === 'valid')) {
            $row['status'] = $severity;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function tally(array $row): void
    {
        $this->counts[match ($row['status']) {
            'valid' => 'valid',
            'warning' => 'warnings',
            default => 'errors',
        }]++;

        if ($row['status'] === 'error') {
            return;
        }

        $resolved = $row['resolved'];

        if ($this->mode === SectionMode::class) {
            $this->distinct['matched_sections'][(string) $resolved['section_id']] = true;
            $this->tallyStudent($resolved);

            return;
        }

        // T344 — a row naming a programme that does not exist is skipped unless the operator chose
        // to create it; otherwise it creates or matches its course, and creates the new programmes.
        if ($this->mode === CourseMode::class) {
            $creatingPrograms = $resolved['new_programs'] !== [] && $this->job->option('create_missing_programs');
            $attachable = $resolved['program_ids'] !== []
                || ($this->job->option('associate_inactive_programs') && $resolved['inactive_program_ids'] !== []);

            // Skipped: an unknown programme with "create" off, or a NEW course that would leave with
            // no programme at all (Resolver::commitCourse).
            if (($resolved['new_programs'] !== [] && ! $creatingPrograms)
                || ($resolved['course_id'] === null && ! $attachable && ! $creatingPrograms)) {
                $this->counts['skips']++;

                return;
            }

            $resolved['course_id'] === null
                ? $this->distinct['new_courses'][$resolved['course_code']] = true
                : $this->distinct['matched_courses'][$resolved['course_code']] = true;

            foreach ($resolved['new_programs'] as $label) {
                $this->distinct['new_programs'][self::nameKey($label)] = true;
            }

            return;
        }

        // T346 — every row that is not an error creates one account.
        if ($this->mode === UserMode::class) {
            $this->distinct['new_accounts'][$resolved['email']] = true;

            return;
        }

        if ($this->mode === RegistrarMode::class) {
            // An ambiguous course with no choice made: the row is skipped at commit (FR-100).
            if ($resolved['course_id'] === null) {
                $this->counts['skips']++;

                return;
            }

            $resolved['section_id'] === null
                ? $this->distinct['new_sections'][$resolved['section_key']] = true
                : $this->distinct['matched_sections'][(string) $resolved['section_id']] = true;

            $this->tallyStudent($resolved);

            return;
        }

        $newCourse = $resolved['course_id'] === null;
        $newInstructor = $resolved['section_id'] === null && ($resolved['instructor_id'] ?? null) === null;

        if (($newCourse && ! $this->job->option('create_missing_courses'))
            || ($newInstructor && ! $this->job->option('create_missing_instructors'))) {
            $this->counts['skips']++;

            return;
        }

        if ($newCourse) {
            $this->distinct['new_courses'][$resolved['course_code']] = true;
        }

        if ($newInstructor) {
            $this->distinct['new_instructors'][$resolved['instructor_email']] = true;
        }

        if ($resolved['section_id'] === null) {
            $this->distinct['new_sections'][$resolved['section_key']] = true;
        } else {
            $this->distinct['matched_sections'][(string) $resolved['section_id']] = true;
        }

        $this->tallyStudent($resolved);
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function tallyStudent(array $resolved): void
    {
        if ($resolved['student_id'] === null) {
            $this->distinct['new_students'][$resolved['university_id']] = true;
        } else {
            $this->distinct['matched_students'][(string) $resolved['student_id']] = true;
        }

        $resolved['enrollment_exists'] ? $this->matchedEnrollments++ : $this->newEnrollments++;
    }
}
