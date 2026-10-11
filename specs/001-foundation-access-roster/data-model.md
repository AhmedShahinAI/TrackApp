# Phase 1 Data Model: Foundation — Access Control & Academic Roster

**Feature**: `001-foundation-access-roster` | **Date**: 2026-09-26

Tables, columns, and unique indexes follow PRD section 11.4 verbatim where it specifies them.
All tables: InnoDB, `utf8mb4` / `utf8mb4_unicode_ci`, `id` as `BIGINT UNSIGNED AUTO_INCREMENT`
primary key, `created_at` / `updated_at` timestamps unless stated otherwise.

Migration order follows the dependency chain below; each group depends only on groups above it.

---

## Group 1 — Organization and access

### `organizations`

| Column | Type | Notes |
|---|---|---|
| `type` | enum(`university`,`college`) | |
| `name_ar`, `name_en` | varchar(255) | |
| `parent_id` | FK → `organizations.id` nullable | Self-referencing hierarchy |

Seeded with exactly one `college` record in this feature (constitution: "organization
hierarchy tables exist from v1 with a single college record"). No screen manages this table in
Phase 1.

### `users`

| Column | Type | Notes |
|---|---|---|
| `organization_id` | FK → `organizations.id` | |
| `name_ar`, `name_en` | varchar(255) | |
| `email` | varchar(255) | **UNIQUE**, includes soft-deleted rows so a deactivated email cannot be reused (FR-005) |
| `password` | varchar(255) | Argon2id hash; never logged |
| `locale` | char(2) default `ar` | FR-074 |
| `is_active` | boolean default true | Deactivation flag, distinct from `deleted_at` |
| `first_login_at` | timestamp nullable | Drives the first-sign-in banner (FR-012) |
| `last_login_at` | timestamp nullable | **Amendment 1** — updated on every successful sign-in, shown as "آخر دخول" on the accounts list (FR-113); feeds the PRD §12.2 instructor-activation metric |
| `deleted_at` | timestamp nullable | `SoftDeletes` |

The unique index is on `email` alone, not `(email, deleted_at)` — FR-005 requires that a
deactivated account's email stay taken.

### `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`

Standard `spatie/laravel-permission` schema, published unmodified.

**Seeded permissions** (FR-019; namespaced `<entity>.<action>`):

`user.view`, `user.create`, `user.edit`, `user.deactivate`, `user.reset_password`,
`user.assign_role`, `user.assign_branch`, `term.view`, `term.create`, `term.edit`,
`term.set_current`, `term.change_status`, `term.edit_closed`, `branch.view`, `branch.create`,
`branch.edit`, `course.view`, `course.create`, `course.edit`, `section.view`, `section.create`,
`section.edit`, `section.change_instructor`, `section.access_override`, `student.view`,
`student.create`, `student.edit`, `enrollment.create`, `enrollment.withdraw`,
`import.central.run`, `import.section.run`, `import.view`, `audit.view`, `audit.export`,
`scope.bypass_all`.

**Added by Amendment 1 (2026-09-27)**: `program.view`, `program.create`, `program.edit`,
`import.registrar.run`, `import.course.run`, `import.user.run`, and `dashboard.view` (seeded now for
Phase 3 per [ui-conflicts.md](./ui-conflicts.md) C-12). **Total 42 permissions.**

**Seeded roles** (FR-020):

| Role | Permissions |
|---|---|
| `SUPER_ADMIN` | all of the above, including `scope.bypass_all`, `term.edit_closed`, `section.access_override` |
| `BRANCH_COORDINATOR` | `*.view` for term, branch, course, section, student, import; `audit.view`; no write permissions in this feature (review/approve arrives in Phase 2) |
| `INSTRUCTOR` | `section.view`, `student.view`, `student.create`, `enrollment.create`, `enrollment.withdraw`, `import.section.run`, `import.view` |

A fourth role requires only a seeder entry — no policy or screen change (FR-020).

### `coordinator_branches`

| Column | Type |
|---|---|
| `user_id` | FK → `users.id` cascade |
| `branch_id` | FK → `branches.id` cascade |

**UNIQUE** `(user_id, branch_id)`. Many-to-many; resolves `coordinatorBranchIds()` for
`BranchScope`.

### `section_access_grants`

| Column | Type |
|---|---|
| `section_id` | FK → `sections.id` cascade |
| `user_id` | FK → `users.id` cascade |
| `granted_by` | FK → `users.id` |
| `granted_at` | timestamp |

**UNIQUE** `(section_id, user_id)`. Feeds `accessibleSectionIds()` and the "shared access"
label (FR-026).

---

## Group 2 — Academic structure

### `branches`

| Column | Type | Notes |
|---|---|---|
| `organization_id` | FK → `organizations.id` | |
| `campus_name_ar`, `campus_name_en` | varchar(255) | |
| `gender` | enum(`male`,`female`) | Sections derive gender through this (FR-050) |
| `is_active` | boolean default true | |
| `deleted_at` | timestamp nullable | `SoftDeletes` |

No campus or branch name appears anywhere in application code (constitution IV, verified by
architecture test).

### `academic_terms`

| Column | Type | Notes |
|---|---|---|
| `name_ar`, `name_en` | varchar(255) | |
| `academic_year` | varchar(9) | e.g. `2026/2027` |
| `semester` | enum(`first`,`second`,`summer`) | |
| `start_date`, `end_date` | date | `end_date` must be after `start_date` |
| `status` | enum(`draft`,`active`,`closed`,`archived`) default `draft` | |
| `is_current` | boolean default false | |
| `deleted_at` | timestamp nullable | `SoftDeletes` |

Partial-unique behaviour for `is_current` is not available in MySQL, so FR-036 is enforced in
a `SetCurrentTerm` action that clears the previous flag and sets the new one inside one
transaction, with a Pest test asserting that exactly one row has `is_current = true` after
concurrent attempts.

**State machine** (transitions allowed in this feature):

```
draft ──► active ──► closed ──► archived
  ▲          │           │
  └──────────┘           └──► active   (reopen; Super Admin + mandatory reason)
```

`closed` and `archived` are storable and settable here; the write-blocking enforcement they
imply is the later term-closure feature (see plan.md Complexity Tracking). No seeder sets
`closed` in this feature.

### `courses`

| Column | Type | Notes |
|---|---|---|
| `organization_id` | FK → `organizations.id` | |
| `code` | varchar(20) | **UNIQUE** `(organization_id, code)` |
| `name_ar`, `name_en` | varchar(255) | |

### `programs` *(Amendment 1 — FR-089 to FR-095)*

| Column | Type | Notes |
|---|---|---|
| `organization_id` | FK → `organizations.id` | |
| `code` | varchar(20) | **UNIQUE** `(organization_id, code)`, stored uppercase |
| `name_ar`, `name_en` | varchar(255) | Both required (FR-089) |
| `unit` | enum(`admin`,`tech`) | "الوحدة التخصصية" — administrative or technical |
| `is_active` | boolean default true | Deactivation is the only removal path (FR-092) |
| `deleted_at` | timestamp nullable | `SoftDeletes` |

### `course_program` *(Amendment 1 — FR-091)*

| Column | Type |
|---|---|
| `course_id` | FK → `courses.id` cascade |
| `program_id` | FK → `programs.id` cascade |

**UNIQUE** `(course_id, program_id)`. A course belongs to many programs and a program holds many
courses.

### `sections`

| Column | Type | Notes |
|---|---|---|
| `term_id` | FK → `academic_terms.id` restrict | |
| `branch_id` | FK → `branches.id` restrict | |
| `course_id` | FK → `courses.id` restrict | |
| `program_id` | FK → `programs.id` restrict | **Amendment 1** — required, exactly one program per section (FR-090). Created **non-nullable from the start**: adding it after 3,000 sections exist would require a backfill with no source of truth |
| `section_number` | varchar(20) | Never unique alone (FR-042) |
| `instructor_id` | FK → `users.id` nullable | Current instructor |
| `degree` | varchar(40) default `bachelor` | **Amendment 1** — degree level. **Not** a database enum: the permitted values live in the `section_degrees` setting and are enforced by a validation rule, so adding a degree needs no migration (constitution IV, FR-108) |
| `activity` | varchar(40) default `lecture` | **Amendment 1** — activity type, validated against the `section_activities` setting on the same principle (FR-109) |

**UNIQUE** `(term_id, branch_id, course_id, section_number)` (FR-041) — ⚠️ **replaced by Amendment 4**
(Group 8) with a generated-column index covering non-rejected sections only; a migration must drop this
one. The section key is
unchanged by Amendment 1; `program_id` is **not** part of it, because a program is a property of the
section rather than part of its identity.
Indexes: `(instructor_id)`, `(branch_id, term_id)` for scope queries, `(program_id)` for the
program filter (FR-094).

`id` is the internal unique section identity referred to in FR-040. Gender is **not** stored
here — it is read through `branch.gender` (FR-050).

### `section_instructor_history`

| Column | Type |
|---|---|
| `section_id` | FK → `sections.id` cascade |
| `instructor_id` | FK → `users.id` |
| `assigned_at` | timestamp |
| `unassigned_at` | timestamp nullable |
| `changed_by` | FK → `users.id` |

Append-only in practice: a change closes the open row's `unassigned_at` and inserts a new open
row (FR-048). Existing records' `created_by` / `updated_by` are never rewritten.

### `students`

| Column | Type | Notes |
|---|---|---|
| `university_id` | varchar(20) | **UNIQUE**, validated against the `university_id_pattern` setting (FR-043, FR-044) |
| `name_ar`, `name_en` | varchar(255) | `name_en` nullable — roster exports frequently omit it |
| `deleted_at` | timestamp nullable | `SoftDeletes` |

Index on `university_id` serves both uniqueness and exact/prefix lookup. Arabic-normalized
name search is a Phase 3 concern.

### `section_enrollments`

| Column | Type | Notes |
|---|---|---|
| `student_id` | FK → `students.id` restrict | |
| `section_id` | FK → `sections.id` restrict | |
| `status` | enum(`active`,`withdrawn`) default `active` | |
| `enrolled_at` | timestamp | |
| `withdrawn_at` | timestamp nullable | |
| `withdraw_reason` | varchar(500) nullable | Mandatory when status becomes `withdrawn` (FR-047) |
| `academic_status` | enum(`regular`,`withdrawn`,`excused`) nullable | **Amendment 1** — the **registrar's** standing (منتظم / منسحب / معتذر), arriving in the import. **Deliberately separate from `status`** (FR-110, FR-111) |
| `roster_sequence` | int unsigned nullable | **Amendment 1** — the "تسلسل" column from a registrar export; optional so hand-created enrollments stay valid (FR-112) |

**UNIQUE** `(student_id, section_id)` (FR-046). Index `(section_id, status)` for roster loads.

**Why `status` and `academic_status` are two columns.** They answer different questions and have
different authorities. `status` is **TRACK's own** enrollment state, set by an instructor or
coordinator with a mandatory written reason and a full audit trail (FR-047). `academic_status` is the
**registration unit's** standing, arriving in a file and overwritten by the next import of that file.
Collapsing them would mean either an import silently erasing a TRACK withdrawal and its reason —
breaking the FR-047 audit trail — or a TRACK withdrawal misrepresenting the registrar's record.
FR-111 forbids either direction of overwrite, and the section roster shows both.

---

## Group 3 — Audit

### `audit_logs`

| Column | Type | Notes |
|---|---|---|
| `user_id` | FK → `users.id` nullable | Nullable for pre-authentication events (failed sign-in) |
| `action` | varchar(100) | e.g. `user.created`, `section.instructor_changed`, `access.denied` |
| `entity_type` | varchar(100) nullable | |
| `entity_id` | bigint unsigned nullable | |
| `old_values` | json nullable | |
| `new_values` | json nullable | |
| `ip_address` | varchar(45) | IPv6-capable |
| `user_agent` | varchar(500) nullable | |
| `created_at` | timestamp | **No `updated_at`** — there is no update path to timestamp |

Indexes: `(user_id, created_at)`, `(entity_type, entity_id)`, `(action, created_at)`,
`(created_at)`. These four serve the FR-032 filter set and the SC-004 two-second target over
1,000,000 rows.

**Immutability**, in three layers (constitution II):
1. No `updated_at`, no fillable update path, no `update`/`delete` route or command.
2. A migration issues `REVOKE UPDATE, DELETE ON <db>.audit_logs FROM <app_user>`, guarded to
   no-op where the connection lacks GRANT privileges.
3. A Pest test asserts the grant state in staging and production, and asserts no code path
   updates or deletes the model.

Retention: at least 5 years (FR-034). No pruning job exists in this feature; a retention
policy note goes in the deployment runbook.

---

## Group 4 — Imports

### `import_jobs`

| Column | Type | Notes |
|---|---|---|
| `user_id` | FK → `users.id` | Who ran it |
| `mode` | enum(`central`,`section`,`registrar`,`course`,`user`) | **Amendment 1** extended from two modes to five (FR-096, FR-103, FR-104) |
| `section_id` | FK → `sections.id` nullable | Required when `mode = section` |
| `term_id` | FK → `academic_terms.id` nullable | Required when `mode` is `central` or `registrar` (FR-101 — the registrar export carries no term) |
| `instructor_id` | FK → `users.id` nullable | **Amendment 1** — required when `mode = registrar`, supplied by the operator because the export carries no instructor (FR-101) |
| `file_metadata` | json nullable | **Amendment 1** — the parsed metadata block from a registrar export: campus, degree, course, activity, section number (FR-097) |
| `original_filename` | varchar(255) | Recorded in the audit entry (FR-073) |
| `file_path` | varchar(500) | Private disk, outside the public root |
| `file_hash` | char(64) | SHA-256; drives re-upload detection (FR-067) |
| `mapping` | json nullable | Confirmed column mapping |
| `options` | json nullable | e.g. `create_missing_courses`, `create_missing_instructors` (FR-058) |
| `status` | enum(`uploaded`,`mapped`,`validating`,`validated`,`committing`,`completed`,`failed`) | |
| `total_rows` | int unsigned default 0 | |
| `processed` | int unsigned default 0 | |
| `imported` | int unsigned default 0 | |
| `duplicates` | int unsigned default 0 | |
| `errors` | int unsigned default 0 | |
| `last_committed_row_id` | bigint unsigned nullable | Resume checkpoint (R-03) |
| `failure_reason` | varchar(500) nullable | |
| `started_at`, `finished_at` | timestamp nullable | Throughput measurement (SC-007) |

Index `(file_hash, term_id)` for FR-067; `(user_id, created_at)` for the job list.

### `import_rows` *(staging — not in PRD 11.4; required by FR-060 and FR-065)*

| Column | Type | Notes |
|---|---|---|
| `import_job_id` | FK → `import_jobs.id` cascade | |
| `row_number` | int unsigned | Source row number as the user sees it |
| `raw` | json | Mapped source values |
| `resolved` | json nullable | Foreign keys resolved during validation |
| `status` | enum(`valid`,`warning`,`error`,`committed`,`skipped`) | |
| `reasons` | json nullable | Array of `{column, value, reason_key, params}` |

Indexes: `(import_job_id, status)`, `(import_job_id, row_number)`, and the primary `id`
ordering that the chunk checkpoint walks.

This table is the reason no core row is written before preview confirmation (FR-060) and the
reason preview and the error report are cheap reads rather than a second parse.

### `import_errors`

| Column | Type |
|---|---|
| `import_job_id` | FK → `import_jobs.id` cascade |
| `row_number` | int unsigned |
| `column` | varchar(100) nullable |
| `value` | varchar(500) nullable |
| `reason` | varchar(500) |

Retained as PRD 11.4 specifies it, materialized from `import_rows` at report time so the
downloadable report (FR-071) and any later query have a flat, stable source.

---

## Group 5 — Settings

### `settings`

| Column | Type | Notes |
|---|---|---|
| `key` | varchar(100) | **UNIQUE** |
| `value` | json | |
| `updated_by` | FK → `users.id` nullable | |

Seeded in this feature (no management screen until the later settings feature):
`university_id_pattern` = `^\d{9}$`, `import_max_file_mb` = 20, `import_max_rows` = 50000,
`attachment_max_mb` = 10, `default_locale` = `ar`, `session_timeout_minutes` = 60.

**Added by Amendment 1** so the two new enums stay configuration rather than code (constitution IV):
`section_degrees` = `["bachelor","intermediate_diploma"]` with their AR/EN labels, and
`section_activities` = `["lecture"]` likewise. The Super Admin extends these later through the
settings feature; adding a degree or activity must never require a migration.

Read through a cached `Settings` facade. No literal for any of these appears in application
code (constitution IV).

---

## Group 6 — Typed EAV (schema and spike seeding only)

Created here **solely** so the 1,500,000-value capacity spike measures the real schema
(FR-087, plan.md Complexity Tracking). No controller, route, policy, or Blade view in this
feature references them; an architecture test asserts that.

| Table | Key columns | Unique index |
|---|---|---|
| `initiatives` | `term_id`, `name_ar`, `name_en`, `short_name`, `description`, `objective`, `instructions`, `start_date`, `end_date`, `status`, `current_schema_version_id`, `created_by` | — |
| `initiative_schema_versions` | `initiative_id`, `version_number`, `published_at`, `published_by` | `(initiative_id, version_number)` |
| `initiative_fields` | `schema_version_id`, `field_key`, `label_ar`, `label_en`, `type`, `is_required`, `default_value`, `options` json, `sort_order`, `visible_to_instructor`, `editable_by_instructor`, `show_in_reports`, `required_for_completion`, `is_active` | `(schema_version_id, field_key)` |
| `initiative_sections` | `initiative_id`, `section_id`, `status`, `submitted_at`, `submitted_by`, `reviewed_at`, `reviewed_by`, `return_reason` | **`(initiative_id, section_id)`** |
| `initiative_targets` | `assignment_id`, `student_id`, `origin`, `removed_at`, `removal_reason` | **`(assignment_id, student_id)`** |
| `student_initiative_records` | `assignment_id`, `student_id`, `is_complete`, `completed_at`, `updated_by` | **`(assignment_id, student_id)`** |
| `student_initiative_values` | `record_id`, `field_id`, `value_text`, `value_number`, `value_date`, `value_bool`, `value_json`, `attachment_id`, `updated_by`, `updated_at` | **`(record_id, field_id)`** |

Additional indexes under measurement in the spike: `student_initiative_values(field_id,
value_bool)` for completion scans, and `student_initiative_records(assignment_id,
is_complete)` for per-section counts. Whether these ship is decided by the spike result
(R-06), not in advance.

`attachments`, `assignment_stats`, `initiative_branch_stats`, `notifications`, `reminders`,
and `assignment_status_history` are **not** created in this feature — they belong to the
phases that use them.

---

---

# Group 7 — Student support channel (Amendment 2, 2026-09-27)

Two new tables. **`students` is not altered** — no credential column, no contact column, no
activation column. That constraint is the reason the support channel needed no change to the
Group 1–6 schema.

## `support_requests`

| Column | Type | Notes |
|---|---|---|
| `reference` | char(11) | **UNIQUE**. Ten Crockford base32 characters + a check character, stored canonical (`K7Q2M9XVT3X`) and shown hyphenated (`K7Q2-M9XV-T3X`). Non-derivable — see [research.md](./research.md) R-11. *Phase 19: `char(11)`, not `char(12)` — the earlier example had ten symbols in all, which cannot be ten plus a check* |
| `student_id` | FK → `students.id` **nullable**, restrict | Nullable **only** so retention can anonymise (R-12). Never null on creation |
| `term_id` | FK → `academic_terms.id` restrict | The term in which active enrollment was verified at submission |
| `category` | varchar(40) | Validated against the `support_categories` setting. **Not** a database enum, so adding a category needs no migration (constitution IV) |
| `description` | text | The student's message. Stored raw, escaped on output, never interpreted as markup |
| `contact` | varchar(255) nullable | Typed by the student, **unverified**. Cleared on anonymisation |
| `status` | enum(`new`,`in_progress`,`resolved`,`closed`,`rejected`) | The one fixed vocabulary in this feature — the audit log depends on it |
| `rejection_reason` | varchar(500) nullable | Mandatory when `status` becomes `rejected` (FR-149). Visible to the student |
| `assigned_to` | FK → `users.id` nullable | |
| `submitted_ip` | varchar(45) | IPv6-capable. Recorded on every accepted request (FR-123). Cleared on anonymisation |
| `anonymised_at` | timestamp nullable | Stamped by the retention sweep |

**Indexes**: `reference` unique · `(status, created_at)` for the queue's default ordering ·
`(student_id)` for the branch-scope join · `(category, created_at)` for filtering · `(assigned_to)` ·
`(created_at)` for the retention sweep.

### Why `status` is a database enum while `category` is not

`category` is a label the Super Admin may extend at will, so it lives in settings and is enforced by a
validation rule — constitution IV forbids a migration for that. `status` is a **workflow contract**: the
state machine, the queue filters, the audit entries, and five years of retained audit history all depend
on the exact set. Widening it is a deliberate change with consequences, not a configuration tweak, so the
database enforces it.

---

## `support_responses`

| Column | Type |
|---|---|
| `support_request_id` | FK → `support_requests.id` cascade |
| `user_id` | FK → `users.id` — the responding staff member |
| `body` | text — stored raw, escaped on output |
| `created_at` | timestamp — **no `updated_at`** |

**Index**: `(support_request_id, created_at)`.

**Append-only in practice.** No `updated_at` column, no update or delete route, no fillable update path.
A response is a statement made to a student at a point in time; editing it after the student has read it
would make the status page unreliable as a record. A correction is a new response.

Responses **survive anonymisation** — they carry staff text, not student personal data, and they are what
makes the retained request useful for trend analysis.

---

## State machine

```
        ┌──────────────────────────────┐
        │                              ▼
new ──► in_progress ──► resolved ──► closed
 │            │
 └────────────┴──────► rejected
```

| Transition | Who | Requires |
|---|---|---|
| `new` → `in_progress` | any holder of `support.respond` | Automatic on first open (FR-147), recording who |
| `in_progress` → `resolved` | `support.respond` | At least one response exists |
| `resolved` → `closed` | `support.close` | — |
| `resolved` → `in_progress` | `support.respond` | Reopening, e.g. the fix did not hold |
| `new` → `rejected` · `in_progress` → `rejected` | `support.respond` | A written reason (FR-149) |

Every other transition is refused (FR-148). Notably: `new` → `closed` and `new` → `resolved` are not
permitted, so a request cannot be disposed of without being opened, and `closed` → anything is not
permitted, so a closed request is final and accepts no further response (FR-150).

---

## Permissions

Four new permissions, seeded into feature 001's permission set (which Amendment 1 left at 42, so this
takes it to **46**; Amendment 4's `section.approve` makes the shipped total **47**). They were seeded with
the base set by `PermissionSeeder` and `RoleSeeder` (T045, T473), so Phase 19 added no separate seeder:

| Permission | SUPER_ADMIN | BRANCH_COORDINATOR | INSTRUCTOR |
|---|---|---|---|
| `support.view` | ✅ | ✅ | ✗ |
| `support.respond` | ✅ | ✅ | ✗ |
| `support.assign` | ✅ | ✗ | ✗ |
| `support.close` | ✅ | ✗ | ✗ |

A coordinator can see and answer their branch's requests but cannot reassign one out of their branch or
close it — closing is the record-keeping act, and assignment is a triage decision that needs the
college-wide view. An instructor holds none of the four (FR-142).

---

## Settings

Eight new keys, all read through feature 001's existing cached facade (`SupportSettingSeeder`, T367). None
of these values appears as a literal anywhere in code. A rate limit is **one** row holding
`{max_attempts, decay_minutes}`, read with `Settings::limit()`, so a count and its window cannot be edited
out of step.

| Key | Default | Used by |
|---|---|---|
| `support_categories` | 5 entries with AR/EN labels: incorrect data · sign-in problem · section problem · general enquiry · other | FR-120 |
| `support_description_max` | 2000 | FR-121 |
| `support_throttle_ip` | 5 per hour | FR-126 |
| `support_throttle_identifier` | 3 per hour | FR-127 |
| `support_status_throttle_ip` | 10 per hour | FR-131 |
| `support_min_response_ms` | 400 | [research.md](./research.md) R-10 timing floor |
| `support_retention_months` | 24 | FR-159 |
| `support_challenge_difficulty` | 16 | [research.md](./research.md) R-08 — leading zero bits the proof-of-work must reach. *Added in Phase 19: T370 reads the difficulty "from settings" and no key existed* |

---

## Scoping

`SupportRequest` carries feature 001's existing `BelongsToBranchScope` **unchanged**, resolved through
the student's active enrollments:

> visible when the request's student holds an active enrollment in a section belonging to one of the
> caller's coordinator branches

Consequences, all deliberate:

- A student enrolled in **several** branches makes their request visible to a coordinator of **each**
  (FR-141). The rule widens rather than narrows, because a support request is about the platform, not
  about one section.
- An **anonymised** request has no student, therefore no enrollments, therefore no branch. The scope
  resolves to nothing and the request becomes visible to the **Super Admin only**. Stated here because it
  is a consequence of R-12's nullable `student_id`, not an oversight.
- A request whose student's enrollments later move to another branch follows the student: the new
  branch's coordinator gains access and the old one loses it (spec Edge Cases).
- The scope is **inert when no user is authenticated**, which is what lets the public controllers create
  and read a request without a caller. That behaviour already exists in 001's trait and is relied on
  here rather than re-implemented.

---

## Audit actions

Five new action keys, taking feature 001's set from 38 to **43**:

| Key | Notes |
|---|---|
| `support.request_created` | **Actor is null** and the entry carries `channel: public` plus the IP. The `Auditor`'s `user_id` is already nullable in 001, so this needs no change — the entry is honest about having no authenticated author rather than inventing a system user (FR-157) |
| `support.responded` | |
| `support.status_changed` | Old and new status, plus the reason on rejection |
| `support.reassigned` | Previous and new assignee (FR-152) |
| `support.anonymised` | Written by the retention sweep, actor null, `channel: system` |

`access.denied` — already in 001 — covers a refused attempt to reach an out-of-scope request (FR-158).

---

## Validation rules

| Rule | Source | Enforced at |
|---|---|---|
| University ID matches the configured pattern | FR-117 | 001's shared `UniversityId` rule, reused. A format failure is the **one** specific message on the public form |
| Student exists **and** holds an active enrollment in the current term | FR-118 | `VerifyStudentIdentity` returning a single boolean — one query, no branch on the reason (R-10) |
| Category within the configured set | FR-120 | 001's shared `InSetting` rule against `support_categories` |
| Description present and within the maximum | FR-115, FR-121 | Form request reading `support_description_max` |
| Challenge valid, unconsumed, unexpired | FR-128, FR-129 | Middleware; **fails closed** when the store is unavailable |
| Both rate limits pass | FR-126, FR-127 | `SupportSubmissionThrottle`, two independent limiters |
| Reference and University ID both match the same request | FR-132 | Single query on both columns; a mismatch is indistinguishable from a missing reference |
| Rejection carries a reason | FR-149 | Form request plus a model-level guard refusing `rejected` without `rejection_reason` |
| Transition permitted by the state machine | FR-148 | `TransitionSupportRequest` action |
| Closed request accepts no response | FR-150 | Guard in `RespondToSupportRequest` |
| No markup interpreted in description or response | FR-153 | Blade's default escaping, plus an architecture test banning `{!! !!}` in `resources/views/support/` |

---

---

# Group 8 — Section approval (Amendment 4, 2026-09-28)

Resolves [ui-conflicts.md](./ui-conflicts.md) C-22. No new table: the workflow lives on `sections` plus
one history table, because a section's approval state is a property of the section, not a separate entity.

## `sections` — columns added

| Column | Type | Notes |
|---|---|---|
| `status` | enum(`pending`,`returned`,`rejected`,`active`) default `active` | Default `active` so every section created before this amendment, and every section a Super Admin creates, is valid without a backfill (FR-173) |
| `created_by` | FK → `users.id` | Who created it — this is what decides whether approval is required (FR-171, FR-172) |
| `requires_approval` | boolean, **derived on creation** from the creator's role | Stored rather than computed at read time, so a later role change cannot retroactively alter whether a section needed approving |
| `reviewed_by` | FK → `users.id` nullable | Who approved, returned, or rejected it |
| `reviewed_at` | timestamp nullable | |
| `review_reason` | varchar(500) nullable | Mandatory on return and on reject (FR-179, FR-180) |
| `approval_key` | varchar(20) **stored generated**, nullable | `NULL` when `status = 'rejected'`, otherwise a copy of `section_number`. See below |

## The uniqueness change — the one non-obvious consequence

FR-176 narrows the section key: **at most one non-rejected** section per term, branch, course, and
number, with any number of rejected ones. Without that narrowing a rejected section would hold its number
hostage forever, and nobody could create the section the rejection was meant to send back.

The obvious implementations do not work:

- **Keeping the original four-column unique index** blocks reuse after rejection.
- **Adding `status` to the index** lets two `pending` sections share a number, which is worse — two
  instructors could each request section 123 and both would be accepted.
- **Adding `deleted_at` or `rejected_at` to the index** fails in the opposite direction: MySQL and MariaDB
  treat `NULL`s in a unique index as distinct, so every live row would have `NULL` there and duplicates
  would be *allowed*.

What works is a **stored generated column**:

```
approval_key  =  CASE WHEN status = 'rejected' THEN NULL ELSE section_number END   (STORED)

UNIQUE (term_id, branch_id, course_id, approval_key)
```

- Two non-rejected sections with the same number → both carry the same `approval_key` → refused.
- A rejected section plus a new one with the same number → the rejected row's key is `NULL` → permitted.
- Several rejected sections with the same number → all `NULL`, and `NULL`s do not collide → permitted.

Both MySQL 8.0+ and MariaDB 10.11+ support stored generated columns inside a unique index, so this needs
no application-level guard. **The original four-column unique index from T023 is replaced by this one**,
and a migration must drop it.

FR-042 is untouched: a section number alone is still never unique.

## `section_approval_history`

| Column | Type |
|---|---|
| `section_id` | FK → `sections.id` cascade |
| `from_status` | enum, nullable (null on creation) |
| `to_status` | enum |
| `actor_id` | FK → `users.id` |
| `reason` | varchar(500) nullable |
| `created_at` | timestamp — **no `updated_at`** |

Append-only, like `assignment_status_history` and for the same reason: it is the record of who decided
what, and editing it would make the record worthless. This is **separate from**
`section_instructor_history`, which tracks teaching assignment rather than approval.

## State machine

```
                    ┌──────── returned ────────┐
                    │           ▲              ▼
created ──► pending ┤           └───── resubmit ┘
   │                └──► rejected  (terminal)
   │                └──► active
   └──► active   (Super Admin: no approval)
```

| Transition | Who | Requires |
|---|---|---|
| — → `active` | Super Admin creates | Nothing (FR-171) |
| — → `pending` | Instructor or coordinator creates | Nothing (FR-172) |
| `pending` → `active` | Coordinator of the branch, or Super Admin — but **never the creator** | — |
| `pending` → `returned` | Same | A written reason (FR-179) |
| `pending` → `rejected` | Same | A written reason (FR-180) |
| `returned` → `pending` | The creator, resubmitting | — |
| `returned` → `rejected` | Reviewer | A written reason |

`rejected` is **terminal**: no transition leaves it (FR-180). `active` → anything is not part of this
workflow — an active section is edited, its instructor is changed, or its term is closed, none of which
is an approval decision.

**Who may review** (FR-177, FR-178), and the reason it is not symmetric:

| Section created by | Reviewable by |
|---|---|
| Instructor | A coordinator of that section's branch, or a Super Admin |
| Coordinator | **A Super Admin only** |
| Super Admin | Nobody — it is already active |

A coordinator cannot review their own section, and cannot review a section created by a coordinator of
the same branch. Both would be circular: the point of the approval is a second pair of eyes, and a
same-branch peer is not one when the branch is the unit of responsibility.

## Permissions

Two listed, but only one is new, so the platform goes from 46 to **47** (corrected 2026-10-06 — the
earlier figure of 48 counted `section.create`, which already exists in the base set and which
Amendment 4 regrants rather than adds):

| Permission | SUPER_ADMIN | BRANCH_COORDINATOR | INSTRUCTOR |
|---|---|---|---|
| `section.create` *(existing, regranted)* | ✅ | ✅ **new** | ✅ **new** |
| `section.approve` | ✅ | ✅ (instructors' sections in their branches only) | ✗ |

`section.create` already exists and was Super Admin only; Amendment 4 grants it to coordinators and
instructors. `section.approve` is new. **No `section.request` permission is introduced** — v2's design had
one, but creation and requesting are the same act here, and the distinction is carried by
`requires_approval` rather than by a second permission.

## Audit actions

Five added, taking the set from 48 to **53**:

`section.created` *(existing — now records the creator's role and the resulting status)* ·
`section.submitted_for_approval` · `section.approved` · `section.returned` · `section.rejected` ·
`section.resubmitted`

Each carries the previous and new status, and the reason where one was given (FR-182).

## Scoping and visibility

- The approval queue is scoped by the existing `BelongsToBranchScope` on `sections` — a coordinator sees
  pending sections from their branches, a Super Admin sees all (FR-184). No new scope type.
- **Default section lists exclude `rejected`** (FR-181). Pending and returned sections appear, badged, so
  a creator can see their own state without a separate screen (FR-183).
- **Initiative targeting selects `active` only** (FR-174). This is the most important integration point:
  a pending section must never enter a targeting preview, an assignment, or a completion denominator.

## Validation rules

| Rule | Source | Enforced at |
|---|---|---|
| Creator's role decides the initial status | FR-171, FR-172 | The create action, from the authenticated user's role — never from a request field |
| Return and reject require a reason | FR-179, FR-180 | Form request plus a model-level guard |
| Nobody approves their own section | FR-178 | Policy comparing `created_by` to the actor |
| A coordinator cannot approve a same-branch coordinator's section | FR-178 | Same policy, checking the creator's role and branches |
| `rejected` is terminal | FR-180 | The transition action |
| Only `active` sections are targetable | FR-174 | A global scope on the targeting query, not a per-call filter |
| One non-rejected section per key | FR-176 | The generated-column unique index above |

## Entity relationship summary

```
organizations ──┬─ users ──┬─ coordinator_branches ── branches
                │          ├─ (instructor_id) sections
                │          └─ section_access_grants ── sections
                ├─ branches ── sections
                ├─ courses  ──┬─ sections
                │             └─ course_program ── programs ── sections
                └─ programs ── sections
academic_terms ── sections ──┬─ section_enrollments ── students
                             ├─ section_instructor_history
                             └─ initiative_sections (Phase 2; schema only)
import_jobs ──┬─ import_rows
              └─ import_errors
audit_logs (no FK dependents; references any entity by type + id)
settings (standalone)
```

## Validation rules carried from the spec

| Rule | Source | Enforced at |
|---|---|---|
| Password ≥ 8 chars with a letter and a digit | FR-006 | Form request + a shared `PasswordPolicy` rule used by both change and reset paths |
| New password ≠ current password | FR-014 | Form request rule comparing against the stored hash |
| Email unique across active and deactivated | FR-005 | Unique index on `email` + form request |
| University ID matches `university_id_pattern` | FR-044 | Shared `UniversityId` rule used by manual entry and the import row validator, reading the setting |
| Withdrawal requires a reason | FR-047 | Form request + a model-level guard rejecting `withdrawn` without `withdraw_reason` |
| Section key unique | FR-041 | Unique index + form request producing the readable message |
| One current term | FR-036 | `SetCurrentTerm` transactional action |
| Term must exist before a section | FR-037 | Controller guard returning the explanatory empty state |
| Upload ≤ 20 MB, ≤ 50,000 rows, allowed types | FR-055 | Form request (size, extension, MIME) + magic-byte check + row count during header streaming |
| Program code unique per institution | FR-089 | Unique index `(organization_id, code)` + form request |
| Every section has exactly one program | FR-090 | Non-nullable `program_id` FK with restrict-on-delete |
| A program with sections cannot be deleted | FR-092 | Restrict-on-delete FK + no delete route; deactivation only |
| `degree` and `activity` within the permitted set | FR-108, FR-109 | A shared `InSetting` rule validating against `section_degrees` / `section_activities` — used by the form, the section import, and the registrar import |
| Registrar campus resolves to exactly one branch | FR-099 | A `ResolveBranchFromCampus` service returning a single match, a no-match, or an ambiguous result; the file is rejected on either failure |
| Registrar course name resolves unambiguously | FR-100 | Arabic-normalized name match; several or no matches flag the row for the operator rather than guessing |
| TRACK withdrawal and registrar status never overwrite each other | FR-111 | Two columns, and the import resolver writes only `academic_status` while the withdraw action writes only `status`, `withdrawn_at`, and `withdraw_reason` |
| Import-created account unusable until reset | FR-105 | Account created with an unusable password hash and `first_login_at` null; sign-in refused until a Super Admin reset sets a real hash |
