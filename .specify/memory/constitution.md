<!--
SYNC IMPACT REPORT — amendment to 1.1.0 (temporary; remove before committing)

Version change: 1.0.0 -> 1.1.0  (MINOR — additive only)

Bump rationale: two subsections were added and existing guidance was materially expanded.
No principle was removed, renamed, or redefined, so this is not MAJOR; the additions carry
new binding rules rather than clarifying wording, so it is not PATCH.

Modified principles: NONE. All eight principles (I-VIII) are byte-identical to 1.0.0.

Explicitly preserved verbatim, by instruction:
- Security floor: "No self-registration. Accounts are created only by the SUPER_ADMIN, and
  `/register` MUST return 404. No 2FA in v1 (owner decision)."
  This remains literally true under the amendment, because the student support form creates
  no account and registers nobody. Had it required reversal, this would have been 2.0.0.

Added sections:
- Technology & Compliance Constraints > Security floor > "Public endpoints (added v1.1)"
  Seven MUST rules plus rationale: no account/password/session; roster + active-enrollment
  verification; dual-axis rate limiting (per IP and per University ID); CAPTCHA; no
  enumeration; IP recorded; no record of any other person exposed. Also constrains any
  FUTURE public endpoint to require an amendment rather than a design decision.
- Technology & Compliance Constraints > Compliance > "Support request retention (added v1.1)"
  2 years then anonymise, with the reason it differs from the 5-year audit retention and the
  indefinite term retention.

Removed sections: NONE

Dates: Ratified unchanged at 2026-09-26 (original adoption). Last Amended 2026-09-27.

Deferred items / follow-up TODOs: none.

Source of amendment: specs/changes/CR-002-student-support.md section 9, decided by the
Product Owner on 2026-09-27. The corresponding PRD v1.1 amendment (six sections) is already
applied.

Downstream artifacts that read this constitution and may need review:
- specs/002-student-support/ (not yet created — /speckit-specify is the next step, and it
  will be written against this amended constitution)
- specs/001-foundation-access-roster/ requires NO change; CR-002 section 10 verifies this,
  including that task T111's `/register` 404 assertion still holds
-->

# TRACK Platform Constitution

## Core Principles

### I. Scoped Authorization On Every Query (NON-NEGOTIABLE)

Authorization is enforced at two layers on every request, and neither layer may be
skipped: a permission check (granular permissions such as `initiative.create`,
`assignment.approve`, `term.edit_closed`, grouped into roles) and a data scope filter
applied to the query itself (branch IDs for BRANCH_COORDINATOR, assigned section IDs
for INSTRUCTOR, unfiltered for SUPER_ADMIN).

Rules that MUST hold:

- Access is denied by default. A route without an explicit authorization policy is a defect.
- Scope filtering MUST be applied through global query scopes, not per-controller
  `where` clauses, so that dashboards, reports, exports, and global search inherit it
  automatically.
- A request for an entity outside the caller's scope MUST return 404, never 403, so that
  the existence of out-of-scope records is not disclosed.
- A missing permission returns 403 and writes an `access.denied` audit entry.
- Role or scope changes take effect on the caller's next request.

**Rationale:** TRACK holds student personal data across six gender-segregated branches
under PDPL. Cross-branch leakage is the highest-impact failure the system can have, and
controller-level filtering has no safety net when a new endpoint is added.

### II. Append-Only Audit In The Same Transaction (NON-NEGOTIABLE)

Every sensitive action — initiative and field/schema changes, student add/edit/withdraw,
value edits, assignment status transitions, imports, user create/edit/deactivate/role
change/password reset, section instructor changes, term status changes, denied access —
MUST write exactly one audit entry recording actor, action, entity type, entity ID, old
values (JSON), new values (JSON), timestamp, IP address, and user agent.

Rules that MUST hold:

- The audit write happens inside the same database transaction as the action. If the audit
  write fails, the whole transaction rolls back and the action does not complete.
- No UI route, API route, or application code path may update or delete an audit entry.
  The application's database user MUST lack UPDATE and DELETE on the audit table.
- Old and new values are stored for every edit of a student initiative value.
- Audit entries are retained for at least 5 years.

**Rationale:** The audit log is the institution's accountability and accreditation
evidence. An entry written outside the transaction can silently diverge from what actually
happened, which makes the whole log untrustworthy rather than merely incomplete.

### III. Completion Is Derived, Never Entered

Completion is computed from stored data. No UI screen, API endpoint, import path, or
administrative tool may accept a completion percentage as input.

Rules that MUST hold:

- A student is Complete for an assignment when every active field flagged
  `required_for_completion` holds a valid value. Yes/No and Checkbox fields MUST be Yes or
  checked; all other types MUST be non-empty and type-valid.
- Section completion = complete active targets / active targets.
- Branch and initiative completion MUST be student-weighted: total complete students /
  total active targets. Averaging section percentages is prohibited, and a unit test MUST
  demonstrate that the two differ when section sizes differ.
- Withdrawn targets leave both the numerator and the denominator, while the original
  target count is reported separately and never mutated by post-launch enrollment change.
- An initiative with no field flagged `required_for_completion` reports
  "N/A — no completion criteria defined" rather than 0% or 100%.

**Rationale:** The platform exists because manually consolidated percentages were not
trustworthy. A single writable completion field would reintroduce exactly the problem
TRACK was built to eliminate.

### IV. Configuration Over Code

Academic structure and initiative data shape are data, not code. Adding a branch, a
course, an initiative, a field, or a role MUST NOT require a code change or a deploy.

Rules that MUST hold:

- Branches, terms, courses, and sections are database records. No branch, campus, or
  gender value may be hard-coded in application logic, views, or migrations.
- Initiative fields are defined at runtime across the 15 supported types, each carrying its
  required, default, options, order, `visible_to_instructor`, `editable_by_instructor`,
  `show_in_reports`, and `required_for_completion` flags.
- Field definitions belong to an immutable schema version. Changing fields on a launched
  initiative MUST publish a new version; existing values are never rewritten or dropped.
- Changing the type of a field that has stored values is prohibited. A new field MUST be
  created instead.
- New roles are created by seeding permissions, with no change to existing policies.
- Initiative data is stored in the typed EAV tables and NEVER in the `students` table.

**Rationale:** The PRD commits to reusing the platform for any future initiative without
developer involvement. Hard-coding any part of the academic structure or field set turns
every new initiative into a release.

### V. Historical Integrity Is Preserved

Academic history is append-only in effect. Data is superseded, closed, or withdrawn — not
erased.

Rules that MUST hold:

- Users, students, branches, and terms use soft delete. Hard delete of any record with
  history is prohibited.
- A student has exactly one row in `students` regardless of enrollment or initiative count,
  keyed by University ID.
- Withdrawing a student sets enrollment status, date, and reason; the student record and
  their prior entries survive.
- Changing a section's instructor moves access forward but preserves the original author on
  every existing entry, and records the change in instructor history.
- Closing a term makes all of its data read-only: write endpoints MUST return 423 for
  callers lacking `term.edit_closed`, and every edit made under that permission is
  audit-logged. Closed and archived term data stays readable in reports and history.
- Deactivating a user preserves attribution on their historical entries and approvals.

**Rationale:** Term data is accreditation evidence with indefinite retention. A deletion
that looks harmless at the time destroys evidence that cannot be reconstructed later.

### VI. Bilingual And Accessible By Default

Arabic (RTL) and English (LTR) are equal first-class interfaces, not a base language plus a
translation layer.

Rules that MUST hold:

- Every user-facing string lives in `lang/ar` and `lang/en`. No literal user-facing text in
  Blade templates, controllers, or JavaScript.
- Layout, navigation, tables, grids, icons, and charts MUST mirror correctly in RTL.
- Arabic MUST render with correct letter shaping and RTL ordering in the UI, in Excel and
  CSV exports (UTF-8 with BOM), and in PDF exports. An Arabic PDF that renders as
  disconnected or reversed glyphs is a release blocker.
- WCAG 2.1 AA is the accessibility floor. The instructor grid MUST be fully keyboard
  navigable.
- Color is never the sole carrier of meaning. Every status color is accompanied by a text
  label.
- Numbers use Western Arabic digits (0-9) in both languages; dates use Gregorian
  DD/MM/YYYY.
- Arabic search matching MUST normalize Alef variants, Taa Marbuta versus Haa, and Yaa
  versus Alef Maqsura.

**Rationale:** The primary users work in Arabic, and the exports are what reach leadership
and accreditation reviewers. Arabic rendering defects in a report are institution-visible
failures, not cosmetic bugs.

### VII. Heavy Work Runs Off The Request

Long-running and bulk work MUST execute on queue workers, never inside an HTTP request.

Rules that MUST hold:

- Imports (validation and commit), initiative launches, completion recalculation, large
  exports, and notification and reminder delivery run as queued jobs.
- Bulk database work runs in chunks inside transactions (imports: 500 rows per chunk). A
  failed chunk MUST leave committed chunks intact and be resumable.
- Queued operations expose status to the UI and notify the user on completion.
- Dashboards read from the aggregate summary tables, refreshed by queued jobs; they do not
  compute aggregates over raw values on request.
- These performance budgets are acceptance gates, not aspirations: page first contentful
  render < 2 s p95; non-aggregate API < 500 ms p95; dashboard aggregates < 3 s p95 at
  30,000 students; instructor grid of 200 students x 15 fields < 2 s; 10,000-row import
  < 5 minutes; global search < 500 ms; full recalculation of 30,000 records < 2 minutes; a
  saved change reflected in aggregates within 60 s.

**Rationale:** Term-boundary and deadline peaks are exactly when imports and launches run.
Synchronous bulk work would make the platform unusable at the moments it matters most, and
the typed EAV model makes on-request aggregation a known scaling risk.

### VIII. Authorization Is Tested, Not Assumed

Access control correctness is proven by tests that accompany the endpoint, not reviewed by
inspection.

Rules that MUST hold:

- Every API endpoint MUST carry authorization tests covering both an allowed role and a
  denied role. An endpoint without them is incomplete and MUST NOT merge.
- Cross-scope denial MUST be tested through each access path: UI, API, global search,
  export, and direct URL.
- Invalid workflow transitions (for example Not Started to Approved) MUST have tests
  asserting rejection.
- Completion arithmetic, duplicate detection at all four levels (University ID, section
  key, Student + Section, Student + Assignment), and schema-version value preservation MUST
  have unit tests.
- CI MUST run Pint (lint), Larastan (static analysis), and Pest (tests) on every pull
  request. A red pipeline blocks merge.

**Rationale:** Scoping bugs are invisible in normal use — they surface only when the wrong
person sees the wrong branch's students. Tests are the only mechanism that keeps this true
as endpoints accumulate.

## Technology & Compliance Constraints

**Mandated stack** (owner decision; changing any item requires a constitution amendment):

- Backend: PHP 8.3+ with Laravel (latest supported major), organized as domain modules
  under `app/Modules`.
- Database: MySQL 8.0+ or MariaDB 10.11+, InnoDB, `utf8mb4`.
- Cache, sessions, and queues: Redis, with Supervisor-managed workers.
- Frontend: server-rendered Blade with Bootstrap 5.3 (separate RTL and LTR builds),
  Alpine.js for interactivity, Tabulator for the editable instructor grid, ECharts or
  Chart.js for charts.
- Libraries: `spatie/laravel-permission` (RBAC), `maatwebsite/excel` (Excel), `mpdf/mpdf`
  (Arabic-capable PDF).
- API: versioned REST under `/api/v1`, session-guard authenticated via Sanctum on the same
  origin, backing all interactive screens so a future mobile client needs no rewrite.

**Data model constraints:**

- Initiative values use the typed EAV model (`value_text`, `value_number`, `value_date`,
  `value_bool`, `value_json`, `attachment_id`) bound to a field within a schema version.
- Uniqueness MUST be enforced by database indexes, not application checks alone:
  `sections(term_id, branch_id, course_id, section_number)`, `students(university_id)`,
  `section_enrollments(student_id, section_id)`,
  `initiative_sections(initiative_id, section_id)`,
  `initiative_targets(assignment_id, student_id)`,
  `student_initiative_records(assignment_id, student_id)`,
  `student_initiative_values(record_id, field_id)`.
- Section number alone is never a unique key.
- The organization hierarchy tables exist from v1 with a single college record, so
  multi-college activation later requires no schema redesign. The architecture MUST support
  10x the stated per-term volume without redesign.

**Security floor:**

- Argon2id password hashing (bcrypt fallback); minimum 8 characters with a letter and a
  digit; no plaintext password in any database column or log line.
- Session authentication with session ID regeneration on login; HttpOnly, Secure,
  SameSite=Lax cookies; 60-minute inactivity expiry; password change or reset terminates
  the user's other sessions.
- CSRF tokens on all state-changing requests; escaped Blade output plus a strict Content
  Security Policy; Eloquent or parameterized queries only, never string-concatenated SQL.
- Rate limiting: login 5 attempts/minute per email and IP; API 120 requests/minute per user.
- Uploads validated on extension, MIME type, and content signature; 10 MB attachments,
  20 MB import files; stored outside the public web root and served only through
  authenticated, authorized routes.
- No self-registration. Accounts are created only by the SUPER_ADMIN, and `/register` MUST
  return 404. No 2FA in v1 (owner decision).

**Public endpoints** (added v1.1):

A public, unauthenticated endpoint is permitted **only** where it creates no account and exposes no
record. The student technical support form and its status check are the only such endpoints in the
platform. Any further public endpoint requires a constitution amendment, not a design decision.

Rules that MUST hold for every public endpoint:

- It MUST NOT create an account, a password, or a session. A student never authenticates.
- It MUST verify the University ID against the roster **and** against an active enrollment in the
  current term before accepting anything.
- It MUST be rate-limited on **two independent axes**: per originating IP address and per University
  ID. Neither a single network nor a single identifier may be used to flood or to probe.
- It MUST carry a CAPTCHA on submission.
- It MUST NOT enumerate. A failed verification returns one generic message that never reveals whether
  the identifier exists, whether it is enrolled, or which check failed — the same discipline principle
  I applies to authenticated scope and FR-009 applies to staff sign-in.
- It MUST record the originating IP address on every accepted request.
- It MUST expose no record belonging to anyone. A student may retrieve only the status of their own
  request, and only by presenting both a reference number and the University ID.

**Rationale:** The roster holds no second identifying factor, so a password would not have prevented
impersonation — it would only have added an account store, an activation flow, and a reversal of the
no-self-registration rule above, in exchange for nothing. Constraining public endpoints to
zero-account, zero-disclosure keeps the attack surface proportionate to the single capability being
offered. The accepted residual risk is a falsely attributed request, never a disclosure.

**Compliance:**

- Saudi Personal Data Protection Law (PDPL) and university information security policy
  apply to all student data handling.
- Data MUST be hosted within the Kingdom of Saudi Arabia.
- Student data is limited to academic identifiers and initiative data. Adding any personal
  field not required by an initiative is prohibited.
- AES-256 encryption at rest for database and backups; TLS 1.2+ in transit (1.3 preferred).
- Daily backups with 30-day retention plus a snapshot on every term closure; RPO 24 hours,
  RTO 4 hours.
- **Support request retention** (added v1.1): a student support request contains free text the student
  wrote and is therefore personal data under PDPL, unlike the academic identifiers the rest of the
  platform stores. Requests MUST be retained for **2 years** and then anonymised — the request, its
  category, and its responses are kept for trend analysis while the link to the student is severed.
  This is deliberately shorter than the 5-year audit retention and the indefinite term retention,
  because a resolved support request has no accreditation value and PDPL requires that personal data
  not be kept longer than its purpose needs.

## Development Workflow & Quality Gates

**Branching and delivery:**

- Feature branches follow the `NNN-feature-name` prefix.
- Every change arrives through a pull request. Direct pushes to the default branch are
  prohibited.
- CI runs Pint, Larastan, and Pest on every pull request; a failing pipeline blocks merge.
- Deployment to production is zero-downtime and runs from a green pipeline only.

**Definition of Done** — a feature is done only when all of the following hold:

1. Every acceptance criterion in the PRD for that feature is demonstrably met.
2. Authorization tests exist for both allowed and denied roles on every new endpoint.
3. Audit entries are written for every sensitive action the feature introduces.
4. Both the Arabic (RTL) and English (LTR) interfaces are complete and verified.
5. The applicable performance budget from Principle VII is measured, not assumed.
6. Empty, loading, error, and success states exist per the PRD's system-wide UX rules.
7. Destructive or high-impact actions (launch, withdraw, return, close term, deactivate
   user) are gated by a confirmation modal that restates the impact.

**Phase gates:** Each implementation phase ends with the validation criteria stated in PRD
section 10 and requires Product Owner acceptance before the next phase begins.

**Scope discipline:** MoSCoW priority is binding. P2 work begins only after all P0 and P1
work for the release is accepted. Adding scope mid-phase requires an explicit trade
recorded in the phase review.

## Governance

This constitution supersedes conflicting practices, conventions, and prior decisions. Where
a PRD requirement and this constitution conflict, the conflict MUST be raised and resolved
explicitly rather than silently resolved in code.

**Authority.** The Product Owner (Dr. Ahmed Ismail Shahin) and the Engineering Lead are
jointly required to approve any amendment.

**Amendment procedure.**

1. Propose the change in writing, stating the principle affected and the reason.
2. Obtain approval from both the Product Owner and the Engineering Lead.
3. Record the amendment in this file with a version bump and an updated `Last Amended` date.
4. Where the amendment invalidates existing code, include a migration plan before merge.

**Versioning policy** (semantic versioning of this document):

- MAJOR: a principle is removed or redefined in a backward-incompatible way, or a
  governance rule is materially weakened.
- MINOR: a principle or section is added, or existing guidance is materially expanded.
- PATCH: clarification, wording, or typographical correction with no change in meaning.

**Compliance review.**

- Every pull request review MUST verify compliance with the principles above. A reviewer
  who cannot verify a principle for a change MUST request the evidence rather than approve.
- Any deviation MUST be justified in the pull request description, referencing the specific
  principle, and MUST be approved by the Engineering Lead.
- Complexity that is not required by a stated requirement MUST be removed or justified.
- Constitution compliance is reviewed at each phase gate and at each term closure.
- Agents and contributors read this constitution plus `.specify/` plan and task artifacts
  for runtime development guidance; those artifacts MUST NOT contradict this document.

**Version**: 1.1.0 | **Ratified**: 2026-09-26 | **Last Amended**: 2026-09-27
