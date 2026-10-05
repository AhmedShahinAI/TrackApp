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
  it becomes a row-level decision at preview

**Audited**: `import.uploaded` with the filename and the parsed metadata.

### POST /api/v1/imports/courses *(Amendment 1 — FR-103)*

Course bulk upload. Permission `import.course.run`. **Request**: `file`.
**201**: as the common shape with `mode: "course"`. **Audited**: `import.uploaded`.

### POST /api/v1/imports/users *(Amendment 1 — FR-104, FR-105)*

Staff account bulk upload. Permission `import.user.run`. **Request**: `file`.
**201**: as the common shape with `mode: "user"`. **Audited**: `import.uploaded`.

Every account this mode commits is created with an unusable password hash and `first_login_at` null,
so sign-in is refused until a Super Admin reset sets a real hash (FR-105, mirroring FR-059).

### POST /api/v1/sections/{id}/imports

Instructor section upload. Permission `import.section.run`, and the section must be one the
caller teaches or has a grant for.

**Request** (`multipart/form-data`): `file` (required)

**201**: as above with `mode: "section"` and `section_id` set.

**404**: the section is not in the caller's scope — an instructor cannot learn that another
instructor's section exists (FR-054, and the attempt is audited as `access.denied`).

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
| `registrar` | Course name matches several courses | `warning` | `import.ambiguous_course` — the operator picks one at preview (FR-100) |
| `registrar` | Academic status outside regular / withdrawn / excused | `warning` | `import.unknown_academic_status` — stored null, row still imports |
| `registrar` | Sequence missing or non-numeric | `warning` | `import.invalid_sequence` — stored null, row still imports (FR-112) |
| `course` | Duplicate course code within the file | `error` | `import.duplicate_in_file` |
| `course` | Program named does not exist | `warning` | `import.unknown_program` — create or skip, the operator's choice |
| `course` | Program named is deactivated | `warning` | `import.inactive_program` — associated only on confirmation (spec Edge Cases) |
| `user` | Email already held by any account, active or deactivated | `error` | `import.email_taken` (FR-005) |
| `user` | Role is `BRANCH_COORDINATOR` with no branch named | `error` | `import.coordinator_needs_branch` (FR-004) |
| `user` | Role not one of the seeded role names | `error` | `import.unknown_role` |
| `user` | Branch named does not exist | `error` | `import.unknown_branch` |

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

**Request**: `confirm` (required, must be `true`), `options` (optional; overrides the stored
create-or-skip choices)

**202**: `{ "data": { "id", "status": "committing" } }`. A self-chaining
`CommitImportChunkJob` walks `import_rows` in 500-row chunks, each chunk one transaction, linking
existing records rather than duplicating them (FR-064). Each chunk's transaction advances
`last_committed_row_id` as its final statement, so a failed chunk leaves the checkpoint at the
last successful one and the import resumes from `id > last_committed_row_id` with committed
chunks intact (FR-065).

A row that violates a unique index is recorded as an error reason and the chunk continues — one
bad row does not fail its chunk (research.md R-04).

**409**: the job is not in `validated` state, or the preview was never fetched.

**Audited**: `import.committed` with the filename, the row counts, and who ran it (FR-073).

### POST /api/v1/imports/{id}/resume

Resume a failed commit. Permission as above; caller owns the job.

**202**: `{ "data": { "id", "status": "committing", "resuming_from_row_id": 12500 } }`

**409**: the job is not in `failed` state.

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

**Audited**: `import.error_report_downloaded`.

---

## Notification

When a queued job finishes, a notification row is stored so that the later notification-centre
feature surfaces it without rework (spec Assumptions). In this feature, completion is
communicated by the wizard's own polling and result view (FR-072) — no email is sent and no SMTP
dependency exists.
