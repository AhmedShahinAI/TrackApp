# Quickstart & Validation Guide: Foundation — Access Control & Academic Roster

**Feature**: `001-foundation-access-roster` | **Date**: 2026-09-26

How to run this feature locally and prove it satisfies the spec. Scenarios map to the spec's
success criteria (SC-nnn) and the PRD Phase 1 validation gate. This is a run guide — schema
detail lives in [data-model.md](./data-model.md), endpoint behaviour in
[contracts/](./contracts/), and design rationale in [research.md](./research.md).

---

## Prerequisites

| Requirement | Notes |
|---|---|
| PHP 8.3+ with `pdo_mysql`, `mbstring`, `zip`, `intl`, `gd` | `intl` is required for Arabic collation behaviour; `zip` for `.xlsx` |
| Composer 2 | |
| MySQL 8.0+ or MariaDB 10.11+ | Database created with `utf8mb4` / `utf8mb4_unicode_ci` |
| Redis 6+ | Cache, sessions, and queues |
| Node 20+ | Vite asset builds (RTL and LTR) |
| A roster sample file | `.xlsx` or `.csv` from the registration unit — needed for scenario 5. A synthetic 5,000-row generator is provided as a fallback. |

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# .env must set: DB_*, REDIS_*, QUEUE_CONNECTION=redis, SESSION_DRIVER=redis,
# SESSION_LIFETIME=60, FILESYSTEM_DISK=private, APP_LOCALE=ar

php artisan migrate --seed          # schema + organization, permissions, roles, settings
npm run build                       # produces app-rtl.css and app-ltr.css
```

## Running

Three processes. The queue worker is **not** optional — validation, commit, and the spike all
run on it, and without it imports stay queued by design rather than failing.

```bash
php artisan serve                            # terminal 1
php artisan queue:work --tries=3 --timeout=900  # terminal 2
npm run dev                                  # terminal 3 (omit if you ran npm run build)
```

Seeded starting account (development only; the seeder refuses to run with `APP_ENV=production`):

```
super@example.test / ChangeMe1
```

---

## Validation scenarios

### Scenario 1 — Accounts and sign-in (US1 → SC-001)

1. Sign in as the seeded Super Admin.
2. Create a coordinator: Arabic and English names, a unique email, `ChangeMe1`, role
   `BRANCH_COORDINATOR`, and at least one branch. Expect success in under 2 minutes.
3. Create an instructor the same way, leaving sections unlinked.
4. Sign out. Sign in as the instructor.
   **Expect**: their own landing page and a dismissable first-sign-in password recommendation
   that does not block navigation.
5. Sign out and in again. **Expect**: no recommendation this time.
6. Change the password from the profile screen. **Expect**: success, and any other open session
   for that user is signed out.
7. Retry the change using the new password as both current and new.
   **Expect**: refused, with a message saying it must differ.
8. Attempt sign-in with a wrong password six times inside a minute.
   **Expect**: a generic message each time — never revealing which field was wrong — then a
   60-second block with a countdown on the sixth.
9. As Super Admin, deactivate the instructor, then attempt to sign in as them.
   **Expect**: refused with the inactive-account message. Open any record they created —
   **their name still appears**.
10. Request `/register`. **Expect**: 404.
11. Leave a session idle 60 minutes (or set `SESSION_LIFETIME=1` and wait) and act.
    **Expect**: sign-in required.

### Scenario 2 — Scoping, the one that matters most (US2 → SC-002, SC-016)

Seed two branches with one coordinator each and two instructors with distinct sections.

```bash
php artisan db:seed --class=ScopeFixtureSeeder
```

1. As coordinator A, open sections, students, enrollments, and the audit log.
   **Expect**: branch A records only; totals count only those.
2. Note a section id belonging to branch B. As coordinator A, request it directly —
   `/sections/{id}` and `/api/v1/sections/{id}`.
   **Expect**: **404**, not 403. A 403 here is a defect: it confirms the record exists.
3. Export the section list as coordinator A. **Expect**: no branch B row in the file.
4. As instructor A, open the section list. **Expect**: only their own sections.
5. As Super Admin, grant instructor A access to one of instructor B's sections. Reload as
   instructor A. **Expect**: the section appears, labelled as shared access.
6. As coordinator A, attempt an action they lack permission for.
   **Expect**: 403 with a full-page message and a link back — and an `access.denied` audit entry.
7. As Super Admin, change coordinator A's branches. As coordinator A, act immediately without
   re-signing-in. **Expect**: the new branch set is already in effect.

```bash
php artisan test --testsuite=Scope        # the full denial matrix
php artisan test tests/Feature/Authorization   # allow + deny per endpoint
```

### Scenario 3 — Audit trail (US3 → SC-003, SC-004)

1. Perform exactly one of each: account creation, role change, and a refused access attempt.
2. Open the audit log. **Expect**: exactly three new entries, each carrying actor, action, entity,
   before and after values, timestamp, IP, and user agent.
3. Filter by person and by date range. **Expect**: the entries are found.
4. Confirm no screen or route offers edit or delete for an entry.
5. Prove immutability at the engine level:

```bash
php artisan test tests/Feature/Audit/ImmutabilityTest.php
# asserts: no update path in code, no updated_at column, and — where the connection has
# privileges — that a direct UPDATE on audit_logs is refused for the application user
```

6. Prove the transaction guarantee:

```bash
php artisan test tests/Feature/Audit/TransactionalWriteTest.php
# forces the audit insert to fail mid-action and asserts the business row is absent afterwards
```

7. Timing:

```bash
PERF_SUITE=1 php artisan test tests/Performance/AuditLogFilterTest.php
# seeds 1,000,000 entries; asserts a person + date-range filter returns under 2 s
```

### Scenario 4 — Academic structure (US4 → SC-011, SC-012, SC-018)

1. Create a term; mark it current. Create a second term and mark **it** current.
   **Expect**: exactly one term flagged current.
2. Create two branches differing in gender, and one course.
3. Create section number `123` in branch 1 and again in branch 2.
   **Expect**: both accepted (SC-011a).
4. Create section `123` again in branch 1, same course and term.
   **Expect**: refused, naming the conflict (SC-011b). Repeat against the API directly to confirm
   the database index — not just the form — refuses it.
5. Enroll one student into both sections. Search their University ID.
   **Expect**: exactly one student record, two enrollments (SC-012).
6. Withdraw them from one section without a reason. **Expect**: refused.
   Withdraw with a reason. **Expect**: enrollment marked withdrawn; the student record survives.
7. Attempt to permanently delete a branch holding sections. **Expect**: refused; only
   deactivation offered.
8. On a section holding records, change the instructor.
   **Expect**: access moves to the new instructor, instructor history gains a row, and **every
   existing record still shows its original author** (SC-018).

### Scenario 5 — Central roster import (US5 → SC-005 … SC-009, and the PRD Phase 1 gate)

Build a fixture that deliberately contains every defect category:

```bash
php artisan track:make-roster-fixture --rows=5000 --with-defects
# emits storage/app/fixtures/roster-5000.xlsx containing valid rows plus in-file duplicates,
# malformed University IDs, missing required values, an unknown branch, an unknown course,
# an unregistered instructor, a name mismatch, and an unresolvable section
```

1. Upload it. **Expect**: accepted; the job is `uploaded`; the file is not reachable under a
   public URL.
2. Confirm the proposed column mapping; leave a required target unmapped first.
   **Expect**: refused until every required target is mapped.
3. Start validation. **Expect**: 202 immediately, the screen stays usable, progress polls every
   5 seconds.
4. **Before confirming**, check core tables:

```bash
php artisan track:core-counts    # sections, students, enrollments, courses, branches
```

   **Expect**: unchanged from before the upload — this is FR-060, the contract's central
   guarantee.
5. Open the preview. **Expect**: creation-versus-match counts, warning and error counts, and the
   first 100 rows each with a status. Every defect category above appears as a row-level reason in
   the reader's language.
6. Choose to create the missing course and skip the unregistered instructor. Confirm.
   **Expect**: 202; commit runs in 500-row chunks.
7. On completion: processed / imported / duplicate / error counts shown.
   **Expect**: at least 97% of rows imported (SC-006); the 5,000-row file completes with no
   manual data repair afterwards (SC-005).
8. Download the error report. **Expect**: `.xlsx` with row number, column, submitted value, and
   reason for **every** rejected row, none missing (SC-009).
9. Re-upload the identical file. **Expect**: the previously-imported warning; if continued,
   **zero** new records (SC-008).
10. Resumability:

```bash
php artisan test tests/Feature/Imports/ResumeAfterChunkFailureTest.php
# fails a chunk mid-commit, asserts committed chunks survive, then resumes and completes the rest
```

11. Throughput:

```bash
PERF_SUITE=1 php artisan test tests/Performance/ImportThroughputTest.php
# 10,000 rows validated and committed under 5 minutes (SC-007)
```

12. Stop the queue worker and start an import.
    **Expect**: the screen says the import is waiting and will be reported when it finishes — not
    an error.

### Scenario 6 — Instructor section import (US6 → SC-010)

1. As an instructor, open one of your own sections and upload a two-column file holding one new
   student, one existing student whose name differs, and one duplicated row.
2. **Expect**: the same six steps; nothing saved before confirmation.
3. After commit: the new student is created; the existing student is **linked** with their stored
   name kept and a warning shown; the duplicate row is rejected naming its row number.
4. Add three late-registering students this way. **Expect**: under 5 minutes, unaided (SC-010).
5. Attempt the same upload against a section taught by someone else.
   **Expect**: **404**, and an `access.denied` audit entry.
6. Sign in as an instructor with no sections. **Expect**: an empty state naming whom to contact —
   not a blank page.

### Scenario 7 — Bilingual and accessible (US7 → SC-013, SC-014, SC-015)

1. Set locale to Arabic. Walk every screen from scenarios 1–6.
   **Expect**: all text Arabic; layout, tables, navigation, and the wizard mirrored right-to-left.
2. Switch to English. **Expect**: full translation, left-to-right, identical behaviour.
3. Confirm every status badge carries a text label beside its colour.
4. Submit a form with an invalid field. **Expect**: the reason beside that field, in the active
   language, on blur and again on submit.
5. Trigger any action. **Expect**: the control shows progress and cannot be re-triggered; lists
   show placeholders while loading.
6. Withdraw a student and deactivate an account. **Expect**: a confirmation step restating the
   impact each time.
7. Navigate a full screen by keyboard alone. **Expect**: every control reachable and operable.
8. Check 1280 px and 1920 px, then below 768 px. **Expect**: correct in both languages; tables
   become stacked cards on the narrow width.
9. Confirm digits render 0-9 and dates read DD/MM/YYYY in both languages.

```bash
php artisan test tests/Architecture/LocalizationParityTest.php
# fails on any key present in lang/ar but not lang/en, or the reverse
php artisan test tests/Architecture/NoHardcodedStringsTest.php
# fails on user-facing literals in Blade outside __() / @lang
PERF_SUITE=1 php artisan test tests/Performance/PageLoadTest.php   # first render under 2 s p95
```

### Scenario 8 — Typed EAV capacity spike (FR-087, FR-088 → SC-017)

Must pass before this feature is accepted, because Phase 2 depends on the answer.

```bash
php artisan db:seed --class=EavSpikeSeeder        # ~1,500,000 values; expect 10-20 minutes
PERF_SUITE=1 php artisan test tests/Performance/EavSpikeTest.php
```

Measures the five queries in [research.md R-06](./research.md) as p95 over 10 cold runs and
writes `specs/001-foundation-access-roster/spike-eav-results.md` with row counts, the index set,
`EXPLAIN` output, measured p95s, and PASS/FAIL per threshold.

**On any FAIL**: record the finding and the proposed remedy in that same file before Phase 2
planning starts. The ranked remedy candidates are already listed in research.md R-06 — do not
invent a new one without recording why.

---

## Full gate before acceptance

```bash
vendor/bin/pint --test          # formatting
vendor/bin/phpstan analyse      # Larastan
php artisan test                # all suites except Performance
PERF_SUITE=1 php artisan test --testsuite=Performance
php artisan test --testsuite=Architecture
```

### PRD Phase 1 validation gate — the four checks the Product Owner signs off

| PRD check | Scenario |
|---|---|
| The Super Admin creates coordinators and instructors | 1 |
| A real roster file of at least 5,000 rows imports with an error report | 5 |
| The Coordinator and Instructor see only their scoped data | 2 |
| All authorization tests pass | 2 (`--testsuite=Scope` and `tests/Feature/Authorization`) |

### Constitution Definition of Done

Every item below is covered by the scenarios above; none may be waived at acceptance:

1. PRD acceptance criteria met — scenarios 1–7.
2. Allow + deny authorization tests on every endpoint — scenario 2.
3. Audit entries for every sensitive action — scenario 3 plus the action-key contract in
   [contracts/audit.md](./contracts/audit.md).
4. Arabic and English both complete — scenario 7.
5. Performance budgets measured, not assumed — the `PERF_SUITE` runs in scenarios 3, 5, 7, 8.
6. Empty, loading, error, and success states present — scenarios 6.6 and 7.4–7.5.
7. High-impact actions gated by a confirmation restating the impact — scenario 7.6.

---

## Troubleshooting

| Symptom | Cause |
|---|---|
| Import stuck at `validating` | Queue worker not running, or Redis unreachable. By design the import waits rather than fails. |
| Arabic renders as disconnected glyphs | `app-rtl.css` not built, or the Arabic web font not loaded. This is a release blocker, not cosmetic. |
| 403 where scenario 2 expects 404 | A policy is running before the global scope — the scope is missing from that model or the route is not resolving through it. Defect against constitution I. |
| Audit `REVOKE` migration skipped | The local database user lacks GRANT privileges; the migration no-ops by design. The grant must be verified in staging and production. |
| `SQLSTATE[HY000]: 1366` on Arabic text | The database, table, or connection is not `utf8mb4`. |
