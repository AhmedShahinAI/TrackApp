# Contracts — Import Wizard (P0-F004)

Conventions, status-code meanings, and the mandatory test matrix: [README.md](./README.md).
Pipeline design, staging, and resumability: [research.md R-03](../research.md).

**Five** modes share every endpoint below except upload. Modes 3–5 were added by Amendment 1
(2026-09-27).

| Mode | Started by | Permission | Columns | Target |
|---|---|---|---|---|
| `central` | Super Admin | `import.central.run` | Term, Branch, Gender, Course Code, Course Name, Section Number, Instructor Name, Instructor Email, Student University ID, Student Name | Creates or links Branch → Section → Instructor → Student → Enrollment |
| `section` | Instructor | `import.section.run` | University ID, Student Name | One section only, which must be the caller's |
| `registrar` | Super Admin | `import.registrar.run` | **Metadata block** above the header: campus, degree, course, activity, section number. **Data columns**: sequence, university id, student name, academic status | One section resolved from the metadata; term and instructor supplied by the operator |
| `course` | Super Admin | `import.course.run` | Course Code, Name AR, Name EN, Programs | Creates or links Course ↔ Programs |
| `user` | Super Admin | `import.user.run` | Name AR, Name EN, Email, Role, Branches | Creates staff accounts, each unusable until its password is reset |

**The registrar mode exists because the registration unit's actual export does not match the
`central` column contract** — it carries four data columns and a metadata block rather than ten flat
columns. See [ui-conflicts.md](../ui-conflicts.md) C-09. The reference file is
`design-system/uploads/filedownloadservlet(21).xlsx`.

**The central guarantee of this contract**: no `academic_terms`, `branches`, `courses`,
`sections`, `students`, or `section_enrollments` row is created or changed by any endpoint before
`POST /imports/{id}/commit` is called (FR-060). Everything up to that point writes only
`import_jobs` and `import_rows`. A Pest test asserts this by snapshotting core table counts
across steps 1–4.

---

## Step 1 — Upload

### POST /api/v1/imports

Central upload. Permission `import.central.run`.

**Request** (`multipart/form-data`): `file` (required, `.xlsx` or `.csv`), `term_id` (required)

**201**: `{ "data": { "id", "mode": "central", "status": "uploaded", "original_filename", "total_rows", "duplicate_of": null } }`

`duplicate_of` carries a previous `import_jobs.id` when this file's SHA-256 hash matches an
earlier import **for the same term** — the client shows the warning before letting the user
continue, and proceeding yields zero new records (FR-067).

**422**: over 20 MB, over 50,000 rows, or an unsupported type — each with a localized message
naming the limit or the accepted types (FR-055). Validation is on extension **and** MIME **and**
magic bytes; a `.xlsx` renamed from `.exe` is rejected. The file is refused before storage.

The stored file lands on the private disk, outside the public root, reachable only through an
authenticated route.

**Audited**: `import.uploaded` with the filename.

### POST /api/v1/imports/registrar *(Amendment 1 — FR-096 to FR-102)*

Registrar-export upload. Permission `import.registrar.run`.

**Request** (`multipart/form-data`): `file` (required), `term_id` (required — the export carries no
term), `instructor_id` (required — it carries no instructor)

**201**:

```json
{ "data": { "id": 44, "mode": "registrar", "status": "uploaded",
            "file_metadata": { "campus": "المجمعة (طلاب)", "degree": "الدبلوم المتوسط",
                               "course": "برمجة 2", "activity": "محاضرة", "section": "2298" },
            "resolved": { "branch_id": 1, "course_id": 6, "section_id": 19 },
            "duplicate_of": null } }
```

The upload step parses the metadata block and attempts resolution immediately, so the operator sees
what the file was understood to mean **before** mapping.

**422** specific to this mode:
- the file has no metadata block above the header row — "this does not look like a registration-unit
  export", naming the other modes
- the campus value resolves to **no** branch, or to **more than one** (FR-099)
- the course name matches **no** course (FR-100); a match against several courses is **not** a 422 —
  the candidates are returned in `resolved.course_candidates` and the operator chooses at the
  **confirmation step** (below)

**Confirmation step** *(2026-10-10 — replaces "at preview")*. Every import choice is made at
`PUT /imports/{id}/mapping`, and commit refuses `options`, so the registrar's two choices are made
there too, as `options`:

- `course_id` — one of `resolved.course_candidates`, when the name matched several. Omitted, every
  row is flagged `import.ambiguous_course` at preview and **skipped** at commit, listed in the report.
- `program_id` — for a section this import will **create**: one of the course's **active**
  programmes. Omitted, the course's single active programme is used; with none or several, the rows
  are `import.unresolvable_section`. A deactivated programme never receives a new section.

Both are validated again when the check runs, so a programme deactivated in between is refused.

**Re-import**: an existing enrollment has only `academic_status` and `roster_sequence` written, and
only when they differ; `status`, `withdrawn_at` and `withdraw_reason` are never written (FR-111). The
write bypasses the model's audit trait — `enrollment.updated` is not a contract key — and the
replaced values are kept on the import row as `resolved.previous_academic_status` /
`resolved.previous_roster_sequence`. `import.committed` for this mode also carries the block and any
**notices**: a degree or activity the settings list lacks (a new section takes the list's first
value), or an existing section that keeps its own differing one. Notices are shown on the preview.

**Audited**: `import.uploaded` with the filename and the parsed metadata.

### POST /api/v1/imports/courses *(Amendment 1 — FR-103)*

Course bulk upload. Permission `import.course.run`. **Request**: `file`.
**201**: as the common shape with `mode: "course"`. **Audited**: `import.uploaded`.

*As built (Phase 16, 2026-10-11):* columns `course_code`, `name_ar`, `programs` (required) and
`name_en` (optional — the Arabic name stands in). `programs` lists several, separated by a comma or
the Arabic comma, each named by code or either name. No term: the job's `term_id` is null and
FR-067's "same file" is judged within the organization. An existing code is **matched**: its stored
names stand and the row's programmes are **added** to its associations, never removed. Mapping
`options`: `create_missing_programs` (bool — create an unknown programme, or skip the row),
`new_program_unit` (`admin`|`tech`, for programmes created), `associate_inactive_programs` (bool —
the spec's "associated only on confirmation"). A programme created here takes the name as its code
when the name is a valid code, otherwise a generated `IMP-` code the Super Admin may correct.
Templates: `GET /imports/new/courses/template` (a screen route).

### POST /api/v1/imports/users *(Amendment 1 — FR-104, FR-105)*

Staff account bulk upload. Permission `import.user.run`. **Request**: `file`.
**201**: as the common shape with `mode: "user"`. **Audited**: `import.uploaded`.

Every account this mode commits is created with an unusable password hash and `first_login_at` null,
so sign-in is refused until a Super Admin reset sets a real hash (FR-105, mirroring FR-059).

*As built (Phase 16, 2026-10-11):* "unusable" is FR-059's: the hash of 64 random characters nobody
is told — not a sentinel string, which Laravel's bcrypt verification would answer with an exception
rather than a refusal. Columns `name_ar`, `name_en`, `email`, `role` (required) and `branches`
(required for `BRANCH_COORDINATOR`, ignored — not even resolved — otherwise; each written as a campus
with its gender, «<campus> (طلاب)», so two same-named branches are never guessed between). `role`
accepts the role name or its label in either language, and only the staff roles
`BRANCH_COORDINATOR` and `INSTRUCTOR` *(review, 2026-10-11)*: any other, `SUPER_ADMIN` included, is
`import.unknown_role` — a Super Admin is created deliberately, on the form. Re-uploading the same
course or staff file is flagged (`duplicate_of`) but never committed link-only: both modes are
idempotent by construction, so a second run creates only what the first did not. The job takes **no** mapping `options` (422): every defect is
an error. Accounts are created in the operator's organization. No term, as for courses.
Template: `GET /imports/new/users/template`.

### POST /api/v1/sections/{id}/imports

Instructor section upload. Permission `import.section.run`, and the section must be one the
caller teaches or has a grant for.

**Request** (`multipart/form-data`): `file` (required)

**201**: as above with `mode: "section"` and `section_id` set.

**404**: the section is not in the caller's scope — an instructor cannot learn that another
instructor's section exists (FR-054, and the attempt is audited as `access.denied`).

**403**: the section is in scope but the caller may not import into it — they lack
`import.section.run`, or they neither teach it nor hold a grant for it (a coordinator, typically).
A Super Admin may import into any section. *(Implemented 2026-10-09, SectionPolicy::importRoster.)*

The job's term is the section's term; the file is read as two columns (`university_id`,
`student_name`) and cannot name another section. A two-column template, headed in the reader's
language, is served at `GET /sections/{id}/import/template` (a screen route, same authorization).

Further section-mode rules *(2026-10-09)*:

- **Every later step re-checks the section.** Mapping, validate, preview, commit and resume require
  the owner to still pass `importRoster` on the job's section (ImportJobPolicy::run), and each commit
  chunk asks again; a chunk that finds the authority gone stops the import as `failed` and writes
  `access.denied` (reason `import_authority_lost`).
- **"Previously imported" (FR-067) compares within the same section.** Two instructors may upload
  identical lists for different sections.
- **The stored name of an existing student is quoted only if they are already in the job's section.**
  For anyone else the name-mismatch reason says only that the name differs: the lookup is unscoped,
  and quoting the name would let an instructor read any student's name off the preview.
- **A soft-deleted student is refused** (`import.student_unavailable`), never restored by a section list.
- **An archived or deleted term takes no section list** (409).
- **`options` is refused** on a section job's mapping (422): a section list creates no course or
  account.

**Audited**: `import.uploaded`.

---

## Step 2 — Column mapping

### PUT /api/v1/imports/{id}/mapping

Permission `import.central.run` or `import.section.run` matching the job's mode; the caller must
own the job.

**Request**:

```json
{
  "mapping": { "university_id": "Student_ID", "student_name": "Name", "section_number": "Sec" },
  "options": { "create_missing_courses": false, "create_missing_instructors": false }
}
```

**200**: the job with `status: "mapped"`, the stored mapping, and `suggested_mapping` echoed for
comparison. The suggestion comes from normalized header matching computed at upload.

**422**: a required target field is unmapped (FR-056), or a mapped source column is not present
in the file's header row.

`options` records the user's choices for unrecognised courses and unregistered instructors
(FR-058); they take effect at commit, not before.

**Added in implementation (Phase 8, 2026-10-08)** — two things FR-090 forced that FR-052 predates:

- an optional eleventh target, `program_code`. Every section belongs to exactly one programme, and
  the ten central columns name none. A new section takes its course's programme when the course has
  exactly one; otherwise the row's `program_code` decides it.
- `options.new_course_program_id` (nullable, an active programme). A course the import creates has
  no programme yet, so "create unknown courses" is only meaningful if the operator also says which
  programme those courses join. Without it — and without a `program_code` on the row — a section
  that would have to be created is reported as `import.unresolvable_section` rather than attached to
  a programme chosen by guess.
- **Section numbers are compared normalised** (2026-10-09): trimmed, Arabic-Indic digits folded,
  upper-cased, leading zeros dropped from all-digit numbers. "١", "1" and "01" are one section, and a
  file's "131" links to an existing section stored as "0131".
- **The wizard state lives in its own migration** (`2026_10_08_000001_add_wizard_state_to_import_jobs_table`):
  `summary`, `link_only`, `preview_fetched_at`, `commit_requested_at`, `queued_at`, `heartbeat_at`.

---

## Step 3 — Validation (queued)

### POST /api/v1/imports/{id}/validate

Permission as above; caller owns the job.

**202**: `{ "data": { "id", "status": "validating" } }` — a `ValidateImportJob` is dispatched and
the request returns immediately (FR-063). The screen polls `GET /imports/{id}` every 5 seconds.

**409**: the job is not in `mapped` state.

The job streams the file, writing one `import_rows` row per source row with a status of `valid`,
`warning`, or `error` and a `reasons` array. The seven categories it must detect (FR-057):

| Category | Row outcome | Localized reason key |
|---|---|---|
| Duplicate row within the file | `error` | `import.duplicate_in_file` (names the first row number) |
| University ID not matching the pattern | `error` | `import.invalid_university_id` (names the expected format) |
| Missing required value | `error` | `import.missing_required` (names the column) |
| Unknown branch | `error` | `import.unknown_branch` |
| Unknown course | `warning` | `import.unknown_course` — user chooses create or skip |
| Instructor has no account | `warning` | `import.unknown_instructor` — user chooses create or skip |
| Existing student, different name | `warning` | `import.name_mismatch` (stored name wins) |
| Section cannot be resolved | `error` | `import.unresolvable_section` (FR-068) |

**Additional categories by mode (Amendment 1):**

| Mode | Category | Row outcome | Reason key |
|---|---|---|---|
| `registrar` | Course name matches several courses | `warning` | `import.ambiguous_course` — the operator picks one at the confirmation step, or the row is skipped (FR-100) |
| `registrar` | Academic status outside regular / withdrawn / excused | `warning` | `import.unknown_academic_status` — stored null, row still imports |
| `registrar` | Sequence missing or non-numeric | `warning` | `import.invalid_sequence` — stored null, row still imports (FR-112) |
| `registrar` | Sequence repeated within the file | `warning` | `import.duplicate_sequence` — kept as given, names the first row *(2026-10-10)* |
| `course` | Duplicate course code within the file | `error` | `import.duplicate_in_file` |
| `course` | Program named does not exist | `warning` | `import.unknown_program` — create or skip, the operator's choice |
| `course` | Program named is deactivated | `warning` | `import.inactive_program` — associated only on confirmation (spec Edge Cases) |
| `user` | Email already held by any account, active or deactivated | `error` | `import.email_taken` (FR-005) |
| `user` | Role is `BRANCH_COORDINATOR` with no branch named | `error` | `import.coordinator_needs_branch` (FR-004) |
| `user` | Role not one of the seeded role names | `error` | `import.unknown_role` |
| `user` | Branch named does not exist | `error` | `import.unknown_branch` — also when a name without a gender matches two branches |
| `course` | Programme name shared by two programmes | `error` | `import.ambiguous_program` — name it by its code *(2026-10-11)* |
| `course`, `user` | A value longer than its column | `error` | `import.too_long` — names the column and limit *(2026-10-11)* |
| `course` | Code already exists | informational | `import.existing_course` — matched, stored names kept *(2026-10-11)* |

---

## Step 4 — Preview

### GET /api/v1/imports/{id}/preview

Permission as above; caller owns the job.

**200**:

```json
{
  "data": {
    "summary": {
      "total_rows": 5000, "valid": 4890, "warnings": 92, "errors": 18,
      "will_create": { "sections": 38, "students": 1412, "enrollments": 1450, "courses": 2, "instructors": 3 },
      "will_match":  { "sections": 12, "students": 3478, "enrollments": 0 }
    },
    "rows": [ { "row_number": 2, "status": "valid", "values": {}, "reasons": [] } ]
  },
  "meta": { "per_page": 100, "total": 5000 }
}
```

`rows` returns the first 100 rows, each with its own status (FR-061). Reasons are localized to
the caller (FR-062).

**409**: the job has not finished validating.

---

## Step 5 — Commit (queued)

### POST /api/v1/imports/{id}/commit

Permission as above; caller owns the job.

**Request**: `confirm` (required, must be `true`). **Changed 2026-10-09:** `options` is no
longer accepted here (422). Validation reads the create-or-skip choices and the preview's counts are
built from them, so a commit on different choices would write what nobody validated or saw.
Changing a choice means mapping again, which re-validates.

**202**: `{ "data": { "id", "status": "committing" } }`. A self-chaining
`CommitImportChunkJob` walks `import_rows` in 500-row chunks, each chunk one transaction, linking
existing records rather than duplicating them (FR-064). Each chunk's transaction advances
`last_committed_row_id` as its final statement, so a failed chunk leaves the checkpoint at the
last successful one and the import resumes from `id > last_committed_row_id` with committed
chunks intact (FR-065).

A row that violates a unique index is recorded as an error reason and the chunk continues — one
bad row does not fail its chunk (research.md R-04).

**409**: the job is not in `validated` state, or the preview was never fetched. The state change is
a compare-and-set in one UPDATE, so of two concurrent commits only one gets 202 and starts a chain.

Whether this is a re-import of an already imported file (FR-067) is decided here, once, and stored on
the job as `link_only`; it does not change if another import of the same file completes meanwhile.
Each chunk takes a row lock on the job before reading the checkpoint, so a duplicated or redelivered
chunk run cannot process the same rows as another, and each chunk re-checks that the owner still
exists, is active, and holds the run permission — otherwise the import stops as `failed`.

**Audited**: `import.committed` with the filename, the final row counts, and who ran it (FR-073),
written when the last chunk completes.

### POST /api/v1/imports/{id}/resume

Resume a failed commit. Permission as above; caller owns the job. Also resumes a commit whose
worker went silent — `committing`, with a heartbeat after the queueing and older than 15 minutes —
because a worker that dies between chunks runs no failure handler and leaves nothing queued.

**202**: `{ "data": { "id", "status": "committing", "resuming_from_row_id": 12500 } }`

**409**: the job is neither `failed` after a commit nor a stalled commit, or another resume claimed
it first (compare-and-set).

**Audited**: `import.resumed`.

---

## Step 6 — Report

### GET /api/v1/imports/{id}

Job status and counts. Permission as above; caller owns the job. Polled every 5 seconds by the
wizard while work is queued (FR-079).

**200**: the job with `status`, `total_rows`, `processed`, `imported`, `duplicates`, `errors`,
`last_committed_row_id`, `failure_reason`, `started_at`, `finished_at`, and a
`progress_percent`. On completion the screen renders the FR-070 counts — for example
"950 processed, 930 imported, 12 duplicates, 8 errors".

When background processing is unavailable the status stays `validating` or `committing` and the
screen says the import is waiting and will be reported when it finishes (spec edge case), rather
than reporting a failure.

### GET /api/v1/imports/{id}/errors/download

Download the error report. Permission as above; caller owns the job.

**200**: an `.xlsx` stream, `Content-Disposition: attachment`, containing one row per rejected
source row with **row number, column, submitted value, and reason** (FR-071), localized to the
caller, Arabic-safe. Materialized from `import_errors`.

**404**: the job has no errors, or is not visible to the caller.

A download writes nothing: the report is streamed from `import_rows` by id and localised as it is
written. `import_errors` is filled once, when the import completes, in the language of the person
who ran it.

**Audited**: `import.error_report_downloaded`.

---

## Notification

When a queued job finishes, a notification row is stored so that the later notification-centre
feature surfaces it without rework (spec Assumptions). In this feature, completion is
communicated by the wizard's own polling and result view (FR-072) — no email is sent and no SMTP
dependency exists.
