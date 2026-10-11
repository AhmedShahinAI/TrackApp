# Contracts — Academic Structure (P0-F003)

Conventions, status-code meanings, and the mandatory test matrix: [README.md](./README.md).

Every endpoint here is subject to the scope rules in the README: a coordinator sees only their
branches, an instructor only their sections. Out-of-scope ids return **404**, never 403.

---

## Terms

### GET /api/v1/terms

Permission `term.view`. **Query**: `status`, `is_current`, `academic_year`, `page`, `per_page`.

**200**: paginated terms with `status`, `is_current`, section counts. Archived terms are
excluded unless `status=archived` is requested explicitly (the default-view rule).

### POST /api/v1/terms

Permission `term.create`.

**Request**: `name_ar`, `name_en`, `academic_year` (e.g. `2026/2027`), `semester`
(`first`|`second`|`summer`), `start_date`, `end_date` (after `start_date`)

**201**: the term, created in `draft`. **Audited**: `term.created`.

### PUT /api/v1/terms/{id}

Permission `term.edit`. Same fields as create, all optional. **200** / **404**.
**Audited**: `term.updated`.

### PATCH /api/v1/terms/{id}/status

Permission `term.change_status`.

**Request**: `status` (`draft`|`active`|`closed`|`archived`), `reason` (required for a permitted
transition out of `closed`; trimmed; 3–500 characters)

**200**: the term. **422**: `reason` missing, too short or too long, beside the field. **409**:
transition not permitted by the state machine in [data-model.md](../data-model.md) — for example
`draft → archived` or `closed → draft`; a refused transition is never answered as a missing reason.
The 409 message names both states by their translated labels.

The `closed` and `archived` values are accepted and stored here. The write-blocking they imply
(423 on the term's data) is the later term-closure feature; see plan.md Complexity Tracking. No
endpoint in this feature emits 423.

**Audited**: `term.status_changed` with old and new status, plus the reason where given.

### PATCH /api/v1/terms/{id}/set-current

Permission `term.set_current`.

**200**: the term with `is_current: true`; the previously current term's flag is cleared in the
same transaction, so exactly one term is ever current (FR-036).

**409**: the term's status is `closed` or `archived`.

**Audited**: `term.set_current` naming both the previous and the new current term.

---

## Branches

### GET /api/v1/branches

Permission `branch.view`. **Query**: `is_active`, `gender`, `page`, `per_page`.

**200**: paginated branches with `campus_name_ar`, `campus_name_en`, `gender`, `is_active`,
section and student counts. A coordinator receives only their own branches.

### POST /api/v1/branches

Permission `branch.create`.

**Request**: `campus_name_ar`, `campus_name_en`, `gender` (`male`|`female`)

**201**: the branch. Adding one requires no code change (FR-038). **Audited**: `branch.created`.

### PUT /api/v1/branches/{id}

Permission `branch.edit`. Fields as create, plus `is_active` for deactivation.

**200**: the updated branch. Deactivation is the only removal path — a hard delete endpoint
does not exist (FR-049). **Audited**: `branch.updated`, or `branch.deactivated` when
`is_active` flips to false.

---

## Programs *(Amendment 1 — FR-089 to FR-095)*

### GET /api/v1/programs

Permission `program.view`. **Query**: `is_active`, `unit` (`admin`|`tech`), `q` (code or name),
`page`, `per_page`.

**200**: paginated programs with `code`, `name_ar`, `name_en`, `unit`, `is_active`, plus a
`courses_count` and a `sections_count`.

### POST /api/v1/programs

Permission `program.create`.

**Request**: `code` (required, unique per institution, stored uppercase), `name_ar` (required),
`name_en` (required), `unit` (required, `admin`|`tech`), `course_ids` (optional array)

**201**: the program. **422**: duplicate code, naming it. **Audited**: `program.created`.

### PUT /api/v1/programs/{id}

Permission `program.edit`. Fields as create, all optional, plus `is_active` for deactivation.

**200**: the updated program. **422**: duplicate code.

**Audited**: `program.updated`, or `program.deactivated` when `is_active` flips to false.

### GET /api/v1/programs/{id}

Permission `program.view`. **200**: the program with its associated courses and a section count.

### PUT /api/v1/programs/{id}/courses

Replace the program's course associations. Permission `program.edit`.

**Request**: `course_ids` (required array)

**200**: the program with its new associations. **Audited**: `program.courses_changed` with the
previous and new id sets.

### Deletion

There is no `DELETE`. A program holding sections is refused permanent removal by the restrict
foreign key as well as by the absent route; deactivation is the only path (FR-092).

---

## Courses

### GET /api/v1/courses

Permission `course.view`. **Query**: `q` (code or name), `page`, `per_page`. **200**: paginated.

### POST /api/v1/courses

Permission `course.create`. **Request**: `code`, `name_ar`, `name_en`, `program_ids` (optional array
— Amendment 1, FR-091).

**201**: the course with its program associations. **422**: duplicate code within the organization.
**Audited**: `course.created`.

---

## Sections

### GET /api/v1/sections

Permission `section.view`. **Query**: `term_id`, `branch_id`, `course_id`, `program_id`
(Amendment 1, FR-094), `instructor_id`, `section_number`, `degree`, `activity`, `q`, `page`,
`per_page`.

**200**: paginated sections with term, branch (and its `gender`, since section gender is derived
— FR-050), course, **program**, `degree`, `activity`, current instructor, active enrollment count,
and `shared_access: true` for a section the caller reaches only through a grant (FR-026).

An instructor with no sections receives an empty `data` array — the screen renders the
explanatory empty state (FR-054 edge case, spec US6 scenario 6).

### POST /api/v1/sections

Permission `section.create`.

**Request**: `term_id`, `branch_id`, `course_id`, `program_id` (**required** — Amendment 1, FR-090),
`section_number`, `instructor_id` (optional), `degree` (optional, defaults to the first
`section_degrees` setting value), `activity` (optional, defaults likewise)

**201**: the section, with its `id` as the internal unique identity (FR-040).

**422** additionally when: `program_id` is absent or names a program that does not exist; `degree` or
`activity` is outside its settings-defined permitted set (FR-108, FR-109). A **deactivated** program
is refused on create but retained on an existing section (spec Edge Cases).

**422**: `(term_id, branch_id, course_id, section_number)` already exists — localized as
"A section with this number already exists for this course, branch, and term." The unique index
is the authority; the form request only produces the readable message (FR-041).

**409**: no term exists yet (FR-037), with a message saying a term must be created first.

**Audited**: `section.created`.

### PUT /api/v1/sections/{id}

Permission `section.edit`. **Request**: `section_number`, `course_id`, `branch_id`, `term_id`,
`program_id`, `degree`, `activity` — all optional; the same uniqueness rule applies to the resulting
combination, and `program_id` may not be set to null (FR-090).

**200** / **404** / **422**. **Audited**: `section.updated`.

### PATCH /api/v1/sections/{id}/instructor

Change the current instructor. Permission `section.change_instructor`.

**Request**: `instructor_id` (required, must hold the `INSTRUCTOR` role; may be null to unassign)

**200**: the section with its new instructor. In one transaction: `sections.instructor_id` is
updated, the open `section_instructor_history` row is closed with `unassigned_at`, and a new open
row is inserted. Access moves to the new instructor; **no existing record's author is rewritten**
(FR-048).

**422**: the target user does not hold the instructor role.

**Audited**: `section.instructor_changed` with the old and new instructor.

---

## Students

### GET /api/v1/students

Permission `student.view`. **Query**: `university_id` (exact or prefix), `q` (name),
`section_id`, `term_id`, `enrollment_status`, `page`, `per_page`.

**200**: paginated students in scope. A coordinator sees only students enrolled in their
branches' sections; an instructor only students in their own sections.

A student enrolled in several sections appears **once** (FR-043) — the scope join is distinct.

### GET /api/v1/students/{id}

Permission `student.view`. **200**: the student with their enrollments (section, course, branch,
term, status, dates) and an initiative-history placeholder that is empty in this feature.

**404**: the student exists but has no enrollment in the caller's scope — the caller cannot
learn of students outside it (FR-024).

### POST /api/v1/sections/{id}/enrollments

Enroll a student into a section. Permission `enrollment.create`, plus the section must be in
the caller's scope.

**Request**: `university_id` (required, must match the `university_id_pattern` setting),
`name_ar` (required when the student does not yet exist), `name_en` (optional)

**201**: the enrollment. If a student with that University ID already exists anywhere, they are
**linked**, not duplicated, and their stored name is kept even when the submitted name differs —
with a `warnings` array in the response saying so (FR-069, spec US6 scenarios 2 and 3).

**422**: University ID fails the pattern (localized with the expected format); the student is
already enrolled in this section (unique `(student_id, section_id)`).

**404**: the section is outside the caller's scope (FR-054).

**Audited**: `student.created` when new, and `enrollment.created` always.

### PATCH /api/v1/sections/{id}/enrollments/{studentId}/withdraw

Withdraw a student from a section. Permission `enrollment.withdraw`, section in scope.

**Request**: `reason` (required, non-empty)

**200**: the enrollment with `status: "withdrawn"`, `withdrawn_at`, and the reason. The student
record survives and their prior entries remain (FR-047).

**422**: reason missing. **409**: the enrollment is already withdrawn.

**Audited**: `enrollment.withdrawn` with the reason.

**Amendment 1 — the two statuses stay separate.** This endpoint writes **only** `status`,
`withdrawn_at`, and `withdraw_reason`. It MUST NOT read or write `academic_status`, which belongs to
the registration unit and arrives through import. A response therefore carries both, and a student
may legitimately show `status: "withdrawn"` alongside `academic_status: "regular"` — that is not a
conflict to reconcile but two different facts (FR-110, FR-111). The section roster displays both.

---

## Hard delete

No endpoint in this file deletes anything. Removal is deactivation (branches, users),
withdrawal (enrollments), or status change (terms). Permanent removal of a record holding
history is refused at every layer (FR-049), and the absence of a `DELETE` verb on these
resources is asserted by test.

---

## Section approval *(Amendment 4 — FR-170 … FR-185)*

Who created a section decides whether it needs approving. Every endpoint below reuses the existing
`BelongsToBranchScope`, so an out-of-scope section returns **404, never 403**.

### POST /api/v1/sections — amended

Permission `section.create`, now held by `SUPER_ADMIN`, `BRANCH_COORDINATOR`, **and** `INSTRUCTOR`.

**Request**: as before — `term_id`, `branch_id`, `course_id`, `program_id`, `section_number`,
`instructor_id`, `degree`, `activity` — plus an optional `roster` file so a creator can attach students at
the moment of creation (FR-175).

**The initial status is derived from the authenticated user's role and MUST NOT be accepted as a request
field:**

| Creator | Resulting `status` | Approval |
|---|---|---|
| `SUPER_ADMIN` | `active` | None (FR-171) |
| `BRANCH_COORDINATOR` | `pending` | Super Admin only (FR-178) |
| `INSTRUCTOR` | `pending` | Coordinator of the branch, or Super Admin (FR-177) |

An instructor MUST only create a section in a branch they teach in, and a coordinator only in their own
branches — the existing scope applies to creation as it does to reading.

**201**: the section with its `status`, `requires_approval`, and `created_by`.

**422**: a **non-rejected** section already exists for this term, branch, course, and number (FR-176). A
**rejected** one does not block — its number has returned to the pool.

**Audited**: `section.created`, recording the creator's role and the resulting status, plus
`section.submitted_for_approval` when the status is `pending`.

### GET /api/v1/sections — amended

**Query** gains `status` (`pending`|`returned`|`rejected`|`active`) and `created_by`.

**Default behaviour**: `rejected` sections are **excluded** unless `status=rejected` is asked for
explicitly (FR-181). Pending and returned sections **are** included, badged with their state, so a creator
sees their own without a separate screen (FR-183).

### GET /api/v1/sections/approvals

The approval queue. Permission `section.approve`.

**Query**: `branch_id`, `course_id`, `created_by`, `page`, `per_page`. Default ordering is
oldest-waiting-first.

**200**: paginated sections awaiting a decision **that this caller may decide**, each with its creator and
their role, its enrollment count, and how long it has been waiting. A coordinator sees pending sections
created by **instructors** in their branches — not their own, and not another coordinator's (FR-178,
FR-184). A Super Admin sees every pending section.

### PATCH /api/v1/sections/{id}/approval

Approve, return, or reject. Permission `section.approve`.

**Request**: `decision` (required, `approve`|`return`|`reject`), `reason` (**required** for `return` and
`reject`, at least 10 characters)

**200**: the section with its new status, `reviewed_by`, `reviewed_at`, and an appended history row.

**403** when the caller is the section's **creator**, or is a coordinator and the creator is a coordinator
of the same branch. Both are refused as self-approval (FR-178). This is a genuine 403, not a 404: the
caller can see the section, they simply may not decide it.

**409** on a transition the state machine does not permit — notably any decision on an `active` or a
`rejected` section, since `rejected` is terminal.

**422** when `return` or `reject` carries no reason.

**Audited**: `section.approved`, `section.returned`, or `section.rejected`, each with the previous and new
status and the reason where given.

### PATCH /api/v1/sections/{id}/resubmit

Resubmit a returned section. Permission `section.create`, and the caller MUST be the section's creator.

**Request**: optionally the same fields as create, to fix what the return asked for.

**200**: the section back in `pending`.

**409** unless the section is `returned` — a `rejected` section is terminal and a new section is the only
route (FR-180).

**Audited**: `section.resubmitted`.

### GET /api/v1/sections/{id}/approval-history

Permission `section.view`. **200**: the append-only decision trail — each transition with its actor, the
previous and new status, the reason, and the timestamp. Separate from
`GET /sections/{id}/instructor-history`, which tracks teaching rather than approval.

### Integration rule that reaches outside this file

**Initiative targeting selects `active` sections only** (FR-174). A pending, returned, or rejected section
MUST NOT appear in a targeting preview, MUST NOT receive an assignment, and MUST NOT enter a completion
denominator. This is enforced as a scope on the targeting query rather than a filter at each call site,
so a later endpoint cannot forget it.

### Vocabulary

Section **approval** and assignment **review** are different decisions at different levels and MUST stay
distinguishable in every interface and every audit entry (FR-185). Sections are
`pending` / `returned` / `rejected` / `active`; assignments keep their own submission-and-review
vocabulary. Where both appear on one screen, each MUST name its subject — "section approval", "submission
review" — never a bare "approved".
