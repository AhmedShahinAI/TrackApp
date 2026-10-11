<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

use App\Models\Course;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\SectionInstructorHistory;
use App\Models\Student;
use App\Models\User;
use App\Modules\Imports\Modes\CourseMode;
use App\Modules\Imports\Modes\RegistrarMode;
use App\Modules\Imports\Modes\SectionMode;
use App\Modules\Imports\Modes\UserMode;
use App\Modules\Settings\Settings;
use App\Modules\UserManagement\Actions\CreateUser;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * T231 — turning one validated row into records: Branch → Course → Section → Instructor → Student →
 * Enrollment, linking whatever already exists (FR-052, FR-064).
 *
 * ## Resolved again, not trusted from validation
 *
 * Validation ran minutes or hours ago. In between, another import may have created the student, or
 * an earlier row of this one may have created the section. So every lookup is repeated here and the
 * staged `resolved` ids are a hint, not an answer — the unique indexes are the authority (R-04), and
 * a row that loses a race to one becomes an error reason rather than a failed chunk
 * (CommitImportChunkJob catches it).
 *
 * ## Decide everything, then write
 *
 * Every outcome that is not "import this row" — skipped by choice, unresolvable — is settled before
 * the first insert. Writing the course first and deciding afterwards left a skipped row's course in
 * the catalogue: a skip does not throw, so its savepoint committed, and nobody had previewed it.
 *
 * ## One read per chunk, not per row
 *
 * prime() loads the chunk's students and existing enrollments with two `whereIn` queries before the
 * rows are walked. Looked up per row, a 50,000-row commit made about 100,000 extra SELECTs, each one
 * inside a chunk transaction holding locks that ordinary enrolment traffic queues behind.
 *
 * ## What an import never does
 *
 *   - **Change an existing section's instructor.** That moves access, and FR-048 requires the
 *     history row and the audit entry ChangeSectionInstructor writes. A file is not that decision.
 *   - **Rename an existing student.** The stored name wins (FR-069).
 *   - **Reopen a withdrawn enrollment.** A TRACK withdrawal carries a reason someone recorded on
 *     purpose; a roster re-sent by the registration unit is not evidence it was wrong. The row is
 *     counted as matched and the withdrawal stands.
 *
 * ## Created accounts cannot sign in (FR-059)
 *
 * An instructor created here gets a password nobody knows — 64 random characters, hashed and
 * discarded — and `first_login_at` null. The account exists so the section has its instructor of
 * record; it becomes usable only when a Super Admin resets the password, which is the deliberate
 * moment someone hands the person their credentials.
 *
 * ## Approval status of created sections
 *
 * Decided by who ran the import, exactly as SectionController::store decides it for a section made
 * by hand (FR-171, FR-172): an approver's import creates active sections, anyone else's would create
 * pending ones. The central mode is a Super Admin's, so in practice they are active — but the rule is
 * stated once and applied here rather than assumed.
 */
class Resolver
{
    /** @var array<string, int> CODE => course id, including courses this import created */
    private array $courses = [];

    /** @var array<string, int> "term|branch|course|number" => section id, number normalised */
    private array $sections = [];

    /** @var array<string, true> "term|branch|course" groups whose sections have been loaded */
    private array $loadedSectionGroups = [];

    /** @var array<int, int|null> course id => its single programme, or null when it has 0 or several */
    private array $programOf = [];

    /** @var array<string, int> email => user id, including accounts this import created */
    private array $instructors = [];

    /** @var array<string, Student|null> university id => student; null means "known not to exist" */
    private array $students = [];

    /** @var array<string, true> "section|student" pairs known to be enrolled */
    private array $enrollments = [];

    /** @var array<int, true> sections whose enrollments are fully known, so absence means "none" */
    private array $primedSections = [];

    /** @var array<string, array{academic_status: string|null, roster_sequence: int|null}> "section|student" => current registrar values */
    private array $registrarValues = [];

    /** @var array<string, int|null> course mode: normalised programme name => id (null: nothing attachable), as programFor decided it */
    private array $createdPrograms = [];

    /** @var array<string, array{id: int, active: bool}|null>|null course mode: the organization's programmes, loaded once */
    private ?array $programsByName = null;

    /** The one unusable password hash this chunk's created accounts share (unusablePassword). */
    private ?string $unusableHash = null;

    /**
     * Cache entries the current row created, as [cache, key]. A row whose savepoint rolls back takes
     * its course, section, account, student or enrollment with it, and a cache still pointing at them
     * would hand the next row an id that no longer exists.
     *
     * @var list<array{0: 'courses'|'sections'|'instructors'|'students'|'enrollments'|'registrarValues'|'createdPrograms', 1: string}>
     */
    private array $pending = [];

    private readonly int $organisationId;

    private readonly bool $ownerApproves;

    public function __construct(private readonly ImportJob $job)
    {
        $owner = User::query()->withTrashed()->findOrFail($job->user_id);

        $this->organisationId = (int) $owner->organization_id;
        $this->ownerApproves = $owner->can('section.approve') || $owner->can('scope.bypass_all');

        // A section list never reads a course, and at term start there are hundreds of them on the
        // queue at once; loading the whole catalogue for each of their chunks would be pure waste.
        // A staff list never reads a course either.
        if (in_array($job->mode, [SectionMode::NAME, UserMode::NAME], true)) {
            return;
        }

        // Loaded once and keyed the way the validator keys them. A college has hundreds of courses,
        // not thousands, and a lookup per row would be the most-repeated query of the commit.
        foreach (Course::query()->where('organization_id', $this->organisationId)->get(['id', 'code']) as $course) {
            $this->courses[RowValidator::code($course->code)] = (int) $course->id;
        }
    }

    /**
     * Load the chunk's students and their existing enrollments, in two queries.
     *
     * @param  iterable<ImportRow>  $rows
     */
    public function prime(iterable $rows): void
    {
        $universityIds = [];
        $sectionIds = [];

        foreach ($rows as $row) {
            if (in_array($row->status, ['error', 'skipped', 'committed'], true)) {
                continue;
            }

            $universityIds[] = (string) ($row->resolved['university_id'] ?? '');

            if (($row->resolved['section_id'] ?? null) !== null) {
                $sectionIds[] = (int) $row->resolved['section_id'];
            }
        }

        $universityIds = array_values(array_unique(array_filter($universityIds)));

        foreach ($universityIds as $universityId) {
            $this->students[$universityId] = null;
        }

        if ($universityIds !== []) {
            $found = Student::query()->withoutGlobalScopes()->withTrashed()->whereIn('university_id', $universityIds)->get();

            foreach ($found as $student) {
                $this->students[(string) $student->university_id] = $student;
            }
        }

        $sectionIds = array_values(array_unique($sectionIds));
        $studentIds = [];

        foreach ($this->students as $student) {
            if ($student !== null) {
                $studentIds[] = (int) $student->id;
            }
        }

        foreach ($sectionIds as $sectionId) {
            $this->primedSections[$sectionId] = true;
        }

        if ($sectionIds !== [] && $studentIds !== []) {
            $pairs = SectionEnrollment::query()->withoutGlobalScopes()
                ->whereIn('section_id', $sectionIds)
                ->whereIn('student_id', $studentIds)
                ->get(['section_id', 'student_id', 'academic_status', 'roster_sequence']);

            foreach ($pairs as $pair) {
                $this->enrollments[$pair->section_id.'|'.$pair->student_id] = true;

                // The registrar's current values, from the same query: a re-import records what it
                // changed FROM, and skips the write when nothing did (refreshRegistrarValues).
                $this->registrarValues[$pair->section_id.'|'.$pair->student_id] = [
                    'academic_status' => $pair->academic_status,
                    'roster_sequence' => $pair->roster_sequence === null ? null : (int) $pair->roster_sequence,
                ];
            }
        }
    }

    /**
     * Commit one row. Writes; the caller owns the transaction.
     *
     * @return array{outcome: 'imported'|'duplicate'|'skipped'|'error', reasons: list<array<string, mixed>>, resolved: array<string, mixed>}
     */
    public function commit(ImportRow $row): array
    {
        $this->pending = [];

        $raw = $row->raw;
        $resolved = $row->resolved ?? [];
        $reasons = $row->reasons ?? [];

        // T256 — a section list goes into the job's section and nowhere else. No course, section or
        // account is resolved or created from file values, because the file names none.
        if ($this->job->mode === SectionMode::NAME) {
            $resolved['section_id'] = (int) $this->job->section_id;

            return $this->enrol($raw, $resolved, $reasons, (int) $this->job->section_id);
        }

        if ($this->job->mode === RegistrarMode::NAME) {
            return $this->commitRegistrar($raw, $resolved, $reasons);
        }

        if ($this->job->mode === CourseMode::NAME) {
            return $this->commitCourse($resolved, $reasons);
        }

        if ($this->job->mode === UserMode::NAME) {
            return $this->commitUser($resolved, $reasons);
        }

        // ---- Decide. Nothing in this block writes.

        $courseId = $this->courses[$resolved['course_code']] ?? null;

        if ($courseId === null && ! $this->job->option('create_missing_courses')) {
            return $this->skip($reasons, $resolved, 'course_code', $raw['course_code']);
        }

        $sectionId = $courseId === null ? null : $this->sectionId($resolved, $courseId);
        $programId = null;
        $instructorId = null;
        $email = (string) ($resolved['instructor_email'] ?? '');

        if ($sectionId === null) {
            // A new course has no programme yet, so only the validated choice can place its section.
            $programId = $resolved['program_id'] ?? ($courseId === null ? null : $this->singleProgramOf($courseId));

            if ($programId === null || $email === '') {
                $reasons[] = ImportReasons::make(
                    ImportReasons::UNRESOLVABLE_SECTION, 'section_number', $raw['section_number'],
                    ['value' => $raw['course_code'].' / '.$raw['section_number']],
                );

                return ['outcome' => 'error', 'reasons' => $reasons, 'resolved' => $resolved];
            }

            $instructorId = $this->instructorId($email);

            if ($instructorId === null && ! $this->job->option('create_missing_instructors')) {
                return $this->skip($reasons, $resolved, 'instructor_email', $raw['instructor_email']);
            }
        }

        // ---- Write. Every way out above has already been taken.

        if ($courseId === null) {
            $courseId = $this->createCourse($resolved['course_code'], $raw['course_name'] ?? '', (int) $programId);
        }

        if ($sectionId === null) {
            $instructorId ??= $this->createInstructor($email, $raw['instructor_name'] ?? '');
            $sectionId = $this->createSection($resolved, $courseId, (int) $programId, $instructorId);
        }

        $resolved['course_id'] = $courseId;
        $resolved['section_id'] = $sectionId;

        return $this->enrol($raw, $resolved, $reasons, $sectionId);
    }

    /**
     * Link or create the student, then enrol them unless they already are — the part of a row both
     * modes share.
     *
     * @param  array<string, string>  $raw
     * @param  array<string, mixed>  $resolved
     * @param  list<array<string, mixed>>  $reasons
     * @return array{outcome: 'imported'|'duplicate'|'error', reasons: list<array<string, mixed>>, resolved: array<string, mixed>}
     */
    private function enrol(array $raw, array $resolved, array $reasons, int $sectionId): array
    {
        $universityId = (string) $resolved['university_id'];
        $student = $this->student($universityId);

        if ($student === null) {
            $student = Student::query()->create([
                'university_id' => $universityId,
                'name_ar' => $raw['student_name'],
                'name_en' => null,
            ]);

            $this->remember('students', $universityId, $student);
        } elseif ($student->trashed() && $this->job->mode === SectionMode::NAME) {
            // Deleted after validation. An instructor's list does not undo an administrator's
            // deletion (RowValidator::resolveStudent); the row is refused instead.
            $reasons[] = ImportReasons::make(ImportReasons::STUDENT_UNAVAILABLE, 'university_id', $universityId);

            return ['outcome' => 'error', 'reasons' => $reasons, 'resolved' => $resolved];
        } elseif ($student->trashed()) {
            // As EnrollStudent does: a person who appears on this term's roster is a current student.
            // Pending, so a rollback makes the next row ask the table again rather than trust a
            // restore that did not survive.
            $student->restore();
            $this->pending[] = ['students', $universityId];
        }

        $resolved['student_id'] = (int) $student->id;

        $registrar = $this->job->mode === RegistrarMode::NAME;

        if ($this->isEnrolled($sectionId, (int) $student->id)) {
            if ($registrar) {
                $resolved += $this->refreshRegistrarValues($sectionId, (int) $student->id, $resolved);
            }

            return ['outcome' => 'duplicate', 'reasons' => $reasons, 'resolved' => $resolved];
        }

        SectionEnrollment::query()->create([
            'section_id' => $sectionId,
            'student_id' => $student->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ] + ($registrar ? [
            // FR-110, FR-112 — the registrar's standing and order, beside TRACK's own status.
            'academic_status' => $resolved['academic_status'] ?? null,
            'roster_sequence' => $resolved['roster_sequence'] ?? null,
        ] : []));

        $this->remember('enrollments', $sectionId.'|'.$student->id, true);

        return ['outcome' => 'imported', 'reasons' => $reasons, 'resolved' => $resolved];
    }

    /**
     * Forget whatever the last row created. Called when that row's savepoint was rolled back.
     */
    /**
     * T333 — one registrar row into the job's section. The section is the one the metadata block
     * named: found if it exists now, created otherwise, with the instructor the operator chose
     * (FR-101) and the degree and activity the block gave.
     *
     * @param  array<string, string>  $raw
     * @param  array<string, mixed>  $resolved
     * @param  list<array<string, mixed>>  $reasons
     * @return array{outcome: string, reasons: list<array<string, mixed>>, resolved: array<string, mixed>}
     */
    private function commitRegistrar(array $raw, array $resolved, array $reasons): array
    {
        $courseId = isset($resolved['course_id']) ? (int) $resolved['course_id'] : null;

        // An ambiguous course nobody chose (RowValidator): skipped, never guessed (FR-100).
        if ($courseId === null) {
            return $this->skip($reasons, $resolved, 'course', (string) ($this->job->file_metadata['registrar']['course'] ?? ''));
        }

        $sectionId = $this->sectionId($resolved, $courseId);

        if ($sectionId === null) {
            $programId = $resolved['program_id'] ?? $this->singleProgramOf($courseId);

            if ($programId === null || $this->job->instructor_id === null) {
                $reasons[] = ImportReasons::make(
                    ImportReasons::UNRESOLVABLE_SECTION, 'section_number', (string) $resolved['section_number'],
                    ['value' => ($this->job->file_metadata['registrar']['course'] ?? '').' / '.$resolved['section_number']],
                );

                return ['outcome' => 'error', 'reasons' => $reasons, 'resolved' => $resolved];
            }

            $resolution = $this->job->registrarResolution() ?? [];

            $sectionId = $this->createSection(
                $resolved, $courseId, (int) $programId, (int) $this->job->instructor_id,
                $resolution['degree'] ?? null, $resolution['activity'] ?? null,
            );
        }

        $resolved['section_id'] = $sectionId;

        return $this->enrol($raw, $resolved, $reasons, $sectionId);
    }

    /**
     * T344 — one course row: create or match the course, create any programme the operator chose
     * to create, and ADD the row's programmes to the course's associations.
     *
     * An existing course keeps its stored names (CourseMode). A deactivated programme is associated
     * only when the operator confirmed it at mapping. Re-checked here, not trusted from validation:
     * a course another import created since is matched, not duplicated.
     *
     * @param  array<string, mixed>  $resolved
     * @param  list<array<string, mixed>>  $reasons
     * @return array{outcome: string, reasons: list<array<string, mixed>>, resolved: array<string, mixed>}
     */
    private function commitCourse(array $resolved, array $reasons): array
    {
        $newPrograms = $resolved['new_programs'] ?? [];

        if ($newPrograms !== [] && ! $this->job->option('create_missing_programs')) {
            return $this->skip($reasons, $resolved, 'programs', implode('، ', $newPrograms));
        }

        $code = (string) $resolved['course_code'];
        $courseId = $this->courses[$code] ?? null;
        $created = $courseId === null;
        $programIds = $resolved['program_ids'] ?? [];

        if ($this->job->option('associate_inactive_programs')) {
            $programIds = [...$programIds, ...($resolved['inactive_program_ids'] ?? [])];
        }

        /*
         * A NEW course must leave with a programme (FR-090 needs one for its every section). One
         * whose programmes were all deactivated, with deactivated ones not confirmed — and no new
         * programme to create — would be created attached to nothing: skipped instead, and listed.
         */
        if ($created && $programIds === [] && $newPrograms === []) {
            return $this->skip($reasons, $resolved, 'programs', '');
        }

        // ---- Write.

        foreach ($newPrograms as $label) {
            $programIds[] = $this->programFor((string) $label);
        }

        $programIds = array_values(array_unique(array_map('intval', array_filter($programIds, static fn (mixed $id): bool => $id !== null))));

        // Every programme it named turned out unattachable (a code reserved by a deleted
        // programme, a name made ambiguous since the check): still never a course with none.
        if ($created && $programIds === []) {
            return $this->skip($reasons, $resolved, 'programs', implode('، ', $newPrograms));
        }

        if ($created) {
            $course = Course::query()->create([
                'organization_id' => $this->organisationId,
                'code' => $code,
                'name_ar' => $resolved['name_ar'],
                'name_en' => $resolved['name_en'],
            ]);

            $courseId = (int) $course->id;
            $this->remember('courses', $code, $courseId);
        }

        // Added, never replaced: a file naming one programme does not say the course left the others.
        $attached = Course::query()->findOrFail($courseId)->programs()->syncWithoutDetaching($programIds)['attached'];

        $resolved['course_id'] = $courseId;
        $resolved['program_ids'] = $programIds;

        return [
            'outcome' => $created || $attached !== [] ? 'imported' : 'duplicate',
            'reasons' => $reasons,
            'resolved' => $resolved,
        ];
    }

    /**
     * A programme the operator chose to create (`create_missing_programs`), created once per name
     * however many rows name it.
     *
     * Its code is the name itself when the name is a valid code (Latin letters, digits, a hyphen —
     * «DS»); otherwise a generated `IMP-` code the Super Admin can correct, which is allowed until the
     * programme holds sections (ProgramController). Its unit is the operator's choice at mapping.
     */
    private function programFor(string $label): ?int
    {
        // The validator's key (Arabic-Indic digits folded too), so a name it matched is the name
        // matched here — compared any other way, a label it found could be "unknown" at commit and
        // be created a second time.
        $key = RowValidator::nameKey($label);

        if (array_key_exists($key, $this->createdPrograms)) {
            return $this->createdPrograms[$key];
        }

        $this->loadPrograms();

        if (array_key_exists($key, $this->programsByName)) {
            /*
             * Named since the check by a programme that now exists — created by another import, or
             * by hand. Used as the validator would have used it: never when two programmes answer
             * to the name, and a deactivated one only when the operator confirmed deactivated
             * programmes at mapping (spec Edge Cases).
             */
            $match = $this->programsByName[$key];
            $id = $match === null || (! $match['active'] && ! $this->job->option('associate_inactive_programs'))
                ? null
                : $match['id'];
        } else {
            $id = $this->createProgram(trim($label));
        }

        $this->createdPrograms[$key] = $id;
        $this->pending[] = ['createdPrograms', $key];

        return $id;
    }

    /**
     * Every programme of the organization, keyed as RowValidator keys them, loaded once per chunk.
     * A name two programmes share maps to null.
     */
    private function loadPrograms(): void
    {
        if ($this->programsByName !== null) {
            return;
        }

        $this->programsByName = [];

        foreach (Program::query()->where('organization_id', $this->organisationId)->get(['id', 'code', 'name_ar', 'name_en', 'is_active']) as $program) {
            $entry = ['id' => (int) $program->id, 'active' => (bool) $program->is_active];

            foreach (array_unique(array_filter([RowValidator::nameKey((string) $program->code), RowValidator::nameKey((string) $program->name_ar), RowValidator::nameKey((string) $program->name_en)])) as $key) {
                $this->programsByName[$key] = array_key_exists($key, $this->programsByName)
                    && ($this->programsByName[$key] === null || $this->programsByName[$key]['id'] !== $entry['id'])
                    ? null
                    : $entry;
            }
        }
    }

    /**
     * Create a programme the operator chose to create, or find the one a concurrent import created
     * first.
     *
     * Its code is the name itself when that is a valid code («DS»), else `IMP-` and 10 hex digits of
     * a hash of the name (40 bits, where 6 digits gave collisions between different names often
     * enough to matter). Two imports creating the same programme at once both reach the insert; the
     * second meets the (organization_id, code) key, inside a savepoint so this row's transaction
     * survives, and takes the programme the first created — matched, not a conflict. A code held by
     * a deleted or deactivated programme is not reused: null, and nothing is attached.
     */
    private function createProgram(string $label): ?int
    {
        $code = preg_match('/^[A-Za-z0-9][A-Za-z0-9-]{0,19}$/', $label) === 1
            ? mb_strtoupper($label)
            : 'IMP-'.mb_strtoupper(substr(hash('sha256', $this->organisationId.'|'.RowValidator::nameKey($label)), 0, 10));

        try {
            return (int) DB::transaction(fn () => Program::query()->create([
                'organization_id' => $this->organisationId,
                'code' => $code,
                'name_ar' => $label,
                'name_en' => $label,
                'unit' => in_array($this->job->options['new_program_unit'] ?? null, ['admin', 'tech'], true)
                    ? $this->job->options['new_program_unit']
                    : 'admin',
                'is_active' => true,
            ])->id);
        } catch (UniqueConstraintViolationException) {
            $existing = Program::query()->where('organization_id', $this->organisationId)->where('code', $code)->first(['id', 'is_active']);

            return $existing !== null && $existing->is_active ? (int) $existing->id : null;
        }
    }

    /**
     * T346 — one staff account, unusable until a Super Admin resets its password (FR-105): a secret
     * nobody is told, as FR-059's instructors get, and `first_login_at` null. Created in the
     * operator's organization, with its role and, for a coordinator, its branches.
     *
     * @param  array<string, mixed>  $resolved
     * @param  list<array<string, mixed>>  $reasons
     * @return array{outcome: string, reasons: list<array<string, mixed>>, resolved: array<string, mixed>}
     */
    private function commitUser(array $resolved, array $reasons): array
    {
        $user = app(CreateUser::class)->execute([
            'organization_id' => $this->organisationId,
            'name_ar' => $resolved['name_ar'],
            'name_en' => $resolved['name_en'],
            'email' => $resolved['email'],
            'password' => $this->unusablePassword(),
            'role' => $resolved['role'],
            'branch_ids' => $resolved['branch_ids'] ?? [],
        ]);

        $resolved['user_id'] = (int) $user->id;

        return ['outcome' => 'imported', 'reasons' => $reasons, 'resolved' => $resolved];
    }

    /**
     * FR-059 / FR-105 — the password every account this import creates is given: the hash of 64
     * CSPRNG characters (Str::random uses random_bytes) that are discarded at once, so no password
     * anyone can type matches it until a Super Admin reset sets a real one.
     *
     * Hashed ONCE per resolver (one per commit chunk) and shared, not once per account. Each hash
     * is a deliberate few hundred milliseconds and tens of MB with production Argon2id; per row,
     * 500 accounts spent most of a chunk's 80-second budget hashing secrets nobody would ever use.
     * Sharing loses nothing: the secret behind the shared hash is as unknown as each separate one
     * would be. The User model's `hashed` cast stores an already-hashed value as it is.
     */
    private function unusablePassword(): string
    {
        return $this->unusableHash ??= Hash::make(Str::random(64));
    }

    /**
     * FR-111 — a re-imported registrar row updates the registrar's two values and NOTHING else.
     *
     * `status`, `withdrawn_at` and `withdraw_reason` are TRACK's, written by a person with a reason
     * (WithdrawEnrollment), and are deliberately absent from this write: a registrar «منتظم» arriving
     * over a TRACK withdrawal leaves the withdrawal and its reason exactly as they were, and both
     * values are shown side by side on the roster (SC-022).
     *
     * A query-builder update, not a model save: the enrollment's audit allowlist names
     * academic_status, and an `enrollment.updated` per re-imported student is an action key
     * contracts/audit.md does not have. The import itself is the accountable act
     * (`import.committed`). A null — the file's value was unreadable — does not erase a value an
     * earlier file gave.
     *
     * What it changed FROM is returned for the row's `resolved` (review M4), so the import's own
     * rows answer "what did this import change, and from what" without an off-contract audit key.
     * A value equal to the stored one is not written at all, so re-importing the same roster costs
     * no UPDATE for the students whose standing did not move.
     *
     * @param  array<string, mixed>  $resolved
     * @return array<string, int|string|null> previous_<field> => the value it replaced
     */
    private function refreshRegistrarValues(int $sectionId, int $studentId, array $resolved): array
    {
        $key = $sectionId.'|'.$studentId;

        // Primed for every pair the chunk knew of; asked directly only for a section created by
        // another import since validation.
        if (! isset($this->registrarValues[$key])) {
            $row = SectionEnrollment::query()->withoutGlobalScopes()
                ->where('section_id', $sectionId)->where('student_id', $studentId)
                ->first(['academic_status', 'roster_sequence']);

            $this->registrarValues[$key] = [
                'academic_status' => $row?->academic_status,
                'roster_sequence' => $row?->roster_sequence === null ? null : (int) $row->roster_sequence,
            ];
        }

        $current = $this->registrarValues[$key];
        $changes = [];

        foreach (['academic_status', 'roster_sequence'] as $field) {
            $value = $resolved[$field] ?? null;

            if ($value !== null && $value !== $current[$field]) {
                $changes[$field] = $value;
            }
        }

        if ($changes === []) {
            return [];
        }

        SectionEnrollment::query()->withoutGlobalScopes()
            ->where('section_id', $sectionId)
            ->where('student_id', $studentId)
            ->update($changes + ['updated_at' => now()]);

        // Pending, like every other cache write here: if this row's transaction rolls back, the next
        // row asks the table again rather than trust a value that was never committed.
        $this->remember('registrarValues', $key, $changes + $current);

        $previous = [];

        foreach (array_keys($changes) as $field) {
            $previous['previous_'.$field] = $current[$field];
        }

        return $previous;
    }

    /** The column a row's conflict is reported against: the row's identity (CommitImportChunkJob). */
    public function identityColumn(): string
    {
        return match ($this->job->mode) {
            CourseMode::NAME => 'course_code',
            UserMode::NAME => 'email',
            default => 'university_id',
        };
    }

    public function discardPending(): void
    {
        foreach ($this->pending as [$cache, $key]) {
            unset($this->{$cache}[$key]);
        }

        $this->pending = [];
    }

    /**
     * @param  list<array<string, mixed>>  $reasons
     * @param  array<string, mixed>  $resolved
     * @return array{outcome: 'skipped', reasons: list<array<string, mixed>>, resolved: array<string, mixed>}
     */
    private function skip(array $reasons, array $resolved, string $column, string $value): array
    {
        $reasons[] = ImportReasons::make(ImportReasons::SKIPPED_BY_CHOICE, $column, $value);

        return ['outcome' => 'skipped', 'reasons' => $reasons, 'resolved' => $resolved];
    }

    /**
     * @param  'courses'|'sections'|'instructors'|'students'|'enrollments'|'registrarValues'|'createdPrograms'  $cache
     */
    private function remember(string $cache, string $key, mixed $value): void
    {
        $this->{$cache}[$key] = $value;
        $this->pending[] = [$cache, $key];
    }

    /**
     * A student from the chunk's preload, or one query when the id was not in it — a key a
     * rolled-back row removed, or a row prime() did not see.
     */
    private function student(string $universityId): ?Student
    {
        if (! array_key_exists($universityId, $this->students)) {
            $this->students[$universityId] = Student::query()->withoutGlobalScopes()->withTrashed()
                ->where('university_id', $universityId)->first();
        }

        return $this->students[$universityId];
    }

    private function isEnrolled(int $sectionId, int $studentId): bool
    {
        if (isset($this->enrollments[$sectionId.'|'.$studentId])) {
            return true;
        }

        // A section prime() did not load — one another import created since validation — is asked
        // directly rather than assumed empty. A wrong "no" would only become a unique violation and a
        // row reported as an error, but the right answer is one query away.
        if (isset($this->primedSections[$sectionId])) {
            return false;
        }

        return SectionEnrollment::query()->withoutGlobalScopes()
            ->where('section_id', $sectionId)
            ->where('student_id', $studentId)
            ->exists();
    }

    private function createCourse(string $code, string $name, int $programId): int
    {
        // The code stands in for a missing name: a course must have both names, and inventing one
        // would put words in the college's catalogue that nobody wrote.
        $course = Course::query()->create([
            'organization_id' => $this->organisationId,
            'code' => $code,
            'name_ar' => $name !== '' ? $name : $code,
            'name_en' => $name !== '' ? $name : $code,
        ]);

        $course->programs()->attach($programId);
        $this->programOf[(int) $course->id] = $programId;

        $this->remember('courses', $code, (int) $course->id);

        return (int) $course->id;
    }

    private function singleProgramOf(int $courseId): ?int
    {
        if (! array_key_exists($courseId, $this->programOf)) {
            // Active programmes only: a deactivated one takes no new section (Course::activeProgramIds).
            $ids = Course::query()->findOrFail($courseId)->activeProgramIds();
            $this->programOf[$courseId] = count($ids) === 1 ? $ids[0] : null;
        }

        return $this->programOf[$courseId];
    }

    /**
     * The existing section for a row, matched after RowValidator::sectionNumber() so "01" in the
     * database is the section a file calls "1". One query per term-branch-course group.
     *
     * @param  array<string, mixed>  $resolved
     */
    private function sectionId(array $resolved, int $courseId): ?int
    {
        $group = $this->job->term_id.'|'.$resolved['branch_id'].'|'.$courseId;

        if (! isset($this->loadedSectionGroups[$group])) {
            $existing = Section::query()->withoutGlobalScopes()
                ->where('term_id', $this->job->term_id)
                ->where('branch_id', $resolved['branch_id'])
                ->where('course_id', $courseId)
                ->where('status', '!=', 'rejected')
                ->get(['id', 'section_number']);

            foreach ($existing as $section) {
                $this->sections[$group.'|'.RowValidator::sectionNumber((string) $section->section_number)] = (int) $section->id;
            }

            $this->loadedSectionGroups[$group] = true;
        }

        return $this->sections[$group.'|'.$resolved['section_number']] ?? null;
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function createSection(array $resolved, int $courseId, int $programId, int $instructorId, ?string $degree = null, ?string $activity = null): int
    {
        $section = new Section;

        // forceFill for the same reason SectionController::store uses it: status and authorship are
        // derived from who is acting, never from a payload — and here the payload is a spreadsheet.
        $section->forceFill([
            'term_id' => $this->job->term_id,
            'branch_id' => $resolved['branch_id'],
            'course_id' => $courseId,
            'program_id' => $programId,
            'section_number' => $resolved['section_number'],
            'instructor_id' => $instructorId,
            // The registrar block's own values where it gave one the settings list knows; otherwise
            // the list's first, as for a central row, which names neither.
            'degree' => $degree ?? Settings::values(Settings::SECTION_DEGREES)[0],
            'activity' => $activity ?? Settings::values(Settings::SECTION_ACTIVITIES)[0],
            'created_by' => $this->job->user_id,
            'status' => $this->ownerApproves ? 'active' : 'pending',
            'requires_approval' => ! $this->ownerApproves,
        ])->save();

        // The founding instructor opens the history, as on the form (FR-048).
        SectionInstructorHistory::query()->create([
            'section_id' => $section->id,
            'instructor_id' => $instructorId,
            'assigned_at' => now(),
            'unassigned_at' => null,
            'changed_by' => $this->job->user_id,
        ]);

        // A new section has no enrollments, so "not in the cache" is a reliable "not enrolled".
        $this->primedSections[(int) $section->id] = true;

        $key = $this->job->term_id.'|'.$resolved['branch_id'].'|'.$courseId.'|'.$resolved['section_number'];
        $this->remember('sections', $key, (int) $section->id);

        return (int) $section->id;
    }

    private function instructorId(string $email): ?int
    {
        if (! isset($this->instructors[$email])) {
            $id = User::query()->withTrashed()->where('email', $email)->value('id');

            if ($id === null) {
                return null;
            }

            $this->instructors[$email] = (int) $id;
        }

        return $this->instructors[$email];
    }

    private function createInstructor(string $email, string $name): int
    {
        $name = $name !== '' ? $name : Str::before($email, '@');

        $user = app(CreateUser::class)->execute([
            // The importing operator's organization — the one this import's courses and sections
            // are created in. The commit runs on the queue with nobody signed in, so CreateUser
            // cannot infer it.
            'organization_id' => $this->organisationId,
            'name_ar' => $name,
            'name_en' => $name,
            'email' => $email,

            // FR-059: a secret nobody is told. See the class docblock.
            'password' => $this->unusablePassword(),
            'role' => 'INSTRUCTOR',
        ]);

        $this->remember('instructors', $email, (int) $user->id);

        return (int) $user->id;
    }
}
