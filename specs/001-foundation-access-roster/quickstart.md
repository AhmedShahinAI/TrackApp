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
| Node 20+ | Vite asset builds (RTL and LTR), Playwright visual regression |
| `design-system/` present | The vendored design system, unpacked from `TRACK Design System.zip`. Required — the visual-regression suite renders its reference kit as the baseline |
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
php artisan db:seed --class=UiFixtureSeeder   # the dataset every visual baseline renders against
npm run build                       # produces app-rtl.css and app-ltr.css, copies brand assets
npx playwright install --with-deps chromium   # visual regression + axe-core
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

### Scenario 8 — Design-system fidelity (design-system.md §13 → SC-013, SC-014)

The approved UI is the visual source of truth; these five gates make "matches the design" checkable.

1. Token parity — the vendored tokens and the shipped tokens agree except the 7 documented
   accessibility corrections:

```bash
php artisan test tests/Architecture/TokenParityTest.php
php artisan test tests/Architecture/NoHardcodedVisualValuesTest.php
```

2. Component fidelity — all 24 components against the reference cards:

```bash
npx playwright test tests/Visual/components.spec.ts
```

   **Expect**: ≤ 1% pixel difference at 1440×900 in both locales. On failure, Playwright writes
   diff images; a genuine design difference is fixed in the Blade component, and a deliberate one
   is recorded and the baseline updated **with its reason**.

3. Screen fidelity — all 20 in-scope screens × 2 locales × 4 widths:

```bash
npx playwright test tests/Visual/screens-all.spec.ts
npx playwright test tests/Visual/rtl-mirroring.spec.ts
```

4. Accessibility — zero AA violations:

```bash
npx playwright test tests/Visual/axe-all.spec.ts
```

5. Open the component gallery at `/dev/component-gallery` (local only) beside
   `design-system/ui_kits/web-app/index.html` and compare by eye. **Expect**: same typography,
   same spacing, same radii, same shadows; the only visible differences are the seven darkened
   text and focus colours listed in `design-system/ACCESSIBILITY-DELTA.md`.

### Scenario 9 — Programs, registrar import, and bulk import (Amendment 1)

Added 2026-09-27 for the five accepted UI additions. Full requirements: [spec.md](./spec.md)
FR-089 … FR-113.

**Programs** (FR-089 … FR-095 → SC-019, SC-021)

1. Create a program: code, both names, specialty unit. Create a second with the same code.
   **Expect**: refused, naming the code.
2. Associate one course with three programs and one program with three courses. **Expect**: both work.
3. Create a section. **Expect**: the program picker is **required** — creation is refused without it.
4. Attempt to permanently remove a program holding sections. **Expect**: refused; deactivation offered.
5. Deactivate a program, then open an existing section that used it. **Expect**: the section still
   shows its program; the deactivated program no longer appears in the create picker.
6. After the roster import, query for sections with no program. **Expect**: zero (SC-019).

**Registrar import** (FR-096 … FR-102 → SC-020, SC-022)

```bash
# the real file, unmodified, already in the repository:
# design-system/uploads/filedownloadservlet(21).xlsx
```

7. Upload it through the registrar mode, supplying term and instructor.
   **Expect**: the confirmation panel shows the parsed metadata — campus "المجمعة (طلاب)", degree
   "الدبلوم المتوسط", course "برمجة 2", activity "محاضرة", section "2298" — alongside the resolved
   branch, course, and section, **before** you continue.
8. Complete the wizard. **Expect**: 19 students land in the resolved section, each carrying their
   academic status (منتظم) and roster sequence (SC-020).
9. Withdraw one of them inside TRACK with a reason, then re-import the same file.
   **Expect**: the TRACK withdrawal, its date, and its reason all survive; `academic_status` is
   refreshed from the file; both are shown on the roster as separate columns (SC-022 — this is the
   single most important assertion in this scenario).
10. Upload a file with no metadata block. **Expect**: refused at upload, saying it does not look like
    a registration-unit export and naming the other modes.

**Bulk course and staff import** (FR-103 … FR-107 → SC-023, SC-024)

11. Upload a course file with one new course, one existing code, and one unknown program.
    **Expect**: nothing written before confirmation; the unknown program offers create-or-skip; the
    existing code is matched, not duplicated.
12. Upload a staff file with one new instructor, one duplicate email, and one coordinator with no
    branch. **Expect**: the duplicate and the branch-less coordinator are each rejected with their
    own reason.
13. Attempt to sign in as an account the import created. **Expect**: refused until a Super Admin
    resets its password (SC-024). Verify for **every** created account.
14. As an instructor, attempt either bulk import. **Expect**: refused (FR-107).

```bash
PERF_SUITE=1 php artisan test tests/Performance/BulkImportTest.php   # 40 courses + 30 staff < 10 min
```

### Scenario 10 — Typed EAV capacity spike (FR-087, FR-088 → SC-017)

**Ungated 2026-09-27**: the Product Owner confirmed the PRD's 15 dynamic field types stand and that
the UI's ordered stages are one preset of Yes/No fields ([ui-conflicts.md](./ui-conflicts.md) C-08).
The spike measures the design that will ship.

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

---

# Amendment 2 — student support channel (Scenarios 11–21)

Merged from `002-student-support` on 2026-09-27. These scenarios exercise the platform's only
unauthenticated surface. **Scenario 2 is the one that matters most** — it is the difference between
a support form and a roster-enumeration oracle.

## Scenario 11 — A student raises a request (US9 → SC-025)

1. Open `/support` while signed out entirely. **Expect**: the form renders in Arabic, right-to-left, in a
   standalone layout with **no navigation to any authenticated area** and no sign-in prompt.
2. Submit with a University ID from the fixture that holds an active enrollment in the current term, a
   category, and a description. **Expect**: success in under 2 minutes, and a reference number displayed
   prominently with guidance to keep it.
3. Note the reference. Reload the confirmation page. **Expect**: the reference is **gone** — it is a
   single-render flash, deliberately not in the URL, so it does not land in history or a proxy log.
4. Submit again leaving the description empty. **Expect**: refused, with the reason beside the field.
5. Submit with a 5-digit University ID. **Expect**: refused with a message **naming the expected
   format** — the one deliberate exception to the generic-failure rule.
6. Look for a file-attachment control. **Expect**: none exists.

---

## Scenario 12 — Non-enumeration, the most important scenario here (SC-026, SC-029)

This is the scenario that would let an attacker enumerate the student roster if the implementation is
wrong. A natural implementation fails it.

```bash
php artisan test tests/Feature/Support/Enumeration
```

Manually, capture the full response — body, status, and headers — for each:

| # | Submission with | Expect |
|---|---|---|
| a | A University ID that does not exist | generic 422 |
| b | A University ID that exists with **no** enrollment in the current term | generic 422 |
| c | A University ID whose **only** enrollment is withdrawn | generic 422 |

**Then diff a, b, and c.** They must be **byte-identical** in body, identical in status, and carry no
header that varies. If any differs, roster membership is disclosed and this is a defect, not a
cosmetic issue.

Repeat for retrieval:

| # | Status check with | Expect |
|---|---|---|
| d | A reference that does not exist | generic 422 |
| e | A **valid** reference and a **different** student's University ID | generic 422 |

**Diff d and e.** Identical.

**Timing** — the channel a body-only fix leaves open:

```bash
php artisan test tests/Feature/Support/Enumeration/ResponseTimeFloorTest.php
```

**Expect**: every public response takes at least `support_min_response_ms`, so the one-index-lookup path
and the join path are indistinguishable by stopwatch.

---

## Scenario 13 — The two rate limits are independent (SC-031, SC-032)

Both must hold. A single combined limiter passes one and fails the other, which is the point.

```bash
php artisan test tests/Feature/Support/Throttle
```

1. **One address, many identifiers**: submit repeatedly from one IP, varying the University ID each time.
   **Expect**: refused once `support_throttle_ip` is reached.
2. **One identifier, many addresses**: submit repeatedly for one University ID from varying IPs.
   **Expect**: refused once `support_throttle_identifier` is reached — **independently** of the address
   limit.
3. Inspect either refusal. **Expect**: it says a limit was reached and when to retry, and **never which
   limit** — naming it would confirm the identifier is being processed.
4. Hammer `POST /support/status` from one address. **Expect**: refused at the tighter
   `support_status_throttle_ip`.
5. While driving sustained abuse, measure the authenticated platform's response times.
   **Expect**: unaffected (SC-031). The limiter, not capacity, is the control.

---

## Scenario 14 — The challenge fails closed (FR-128, FR-129)

1. Submit with no challenge solution. **Expect**: refused.
2. Submit the same solution twice. **Expect**: the second is refused — challenges are single-use.
3. Submit a solution older than 10 minutes. **Expect**: refused.
4. Complete the hidden honeypot field. **Expect**: refused.
5. Submit within the minimum elapsed time. **Expect**: refused.
6. **Stop Redis**, then submit. **Expect**: refused with a generic message — **not** allowed through.
   Failing open would silently remove a stated protection.

---

## Scenario 15 — Reference numbers are not derivable (SC-027)

```bash
php artisan test tests/Feature/Support/ReferenceGeneratorTest.php
```

**Expect**, across 1,000 generated references: no sequence, no timestamp correlation, no growing shared
prefix, and none of the characters `I`, `L`, `O`, `U` — excluded so a student reading a reference aloud
is not misheard.

---

## Scenario 16 — Staff handle a request (US10 → SC-030, SC-033)

1. As Super Admin, open the queue. **Expect**: each row shows the student, their branch, the category,
   the state, and **how long it has been waiting**, oldest first.
2. Open a `new` request. **Expect**: it becomes `in progress`, recorded against your name.
3. Write a response and resolve it. **Expect**: under 3 minutes from opening the queue.
4. Reject another request **without** a reason. **Expect**: refused; a reason is mandatory.
5. Reject it with a reason. **Expect**: a confirmation step restates the impact first.
6. Attempt `new` → `closed` directly. **Expect**: refused.
7. Close a resolved request, then try to add a response. **Expect**: refused.
8. Inspect a request carrying a student-typed contact. **Expect**: it is **visibly flagged as
   unverified** — nobody confirmed the student owns it.
9. Reassign a request as Super Admin. **Expect**: both the previous and new assignee recorded. Try the
   same as a coordinator. **Expect**: refused.
10. Count the audit entries for steps 2, 3, 4, 5, 9. **Expect**: exactly one each, complete (SC-030).

---

## Scenario 17 — A student finds out what happened (US11 → SC-034)

1. On `/support`, switch to the status tab. Enter the reference from Scenario 1 and the matching
   University ID. **Expect**: the request, its state, and the staff response — on the first attempt.
2. Inspect the page. **Expect**: **no** assignee name, no internal note, no IP, and nothing belonging to
   any other student.
3. Check a request with no response yet. **Expect**: its state with an explanation that it is still being
   handled.

---

## Scenario 18 — Branch scoping (US12 → SC-028, SC-038)

```bash
php artisan test --testsuite=Scope --filter=SupportRequest
php artisan test tests/Authorization/SupportEndpointsTest.php
```

1. Seed requests from students in two branches. As the coordinator of one, open the queue.
   **Expect**: only your branch's requests, and totals counting only those.
2. Take a reference from the other branch and request it directly. **Expect**: **404** — not 403. A 403
   here is a defect: it confirms the request exists. And the attempt writes `access.denied`.
3. Pass another branch's `branch_id` as a filter. **Expect**: your own scope returned, not an error —
   the filter cannot be used to probe.
4. Find a student with active enrollments in **two** branches. **Expect**: their request appears for the
   coordinators of **both**.
5. Sign in as an instructor. **Expect**: no queue, and every support address returns 404 (SC-038).
6. As Super Admin. **Expect**: every request, regardless of branch.

---

## Scenario 19 — Retention and anonymisation (SC-037)

```bash
php artisan test tests/Feature/Support/Retention
```

1. Age a request past `support_retention_months`, run the sweep.
2. **Expect cleared**: the student link, the typed contact, the originating IP. `anonymised_at` stamped.
3. **Expect surviving**: the reference, the category, the state, the full state history, and every
   response.
4. **Expect surviving**: the audit entries — they follow the 5-year audit retention, which is longer.
   Anonymising the request while keeping the accountability record is what lets both obligations be met.
5. As a coordinator, look for the anonymised request. **Expect**: gone — it has no student, so no
   enrollments, so no branch. Visible to the **Super Admin only**. This is a designed consequence, not a
   bug.
6. Retrieve it by its old reference on the public status page. **Expect**: the **same generic failure** as
   a non-existent reference. The student cannot tell it was anonymised rather than never existing.

---

## Scenario 20 — Bilingual and accessible (US13 → SC-035, SC-036)

1. Walk every screen — public form, confirmation, status form, status result, refusal, staff queue,
   request detail — in Arabic then English. **Expect**: complete translation, correct mirroring, no
   untranslated text.
2. On the public pages, switch language **while signed out**. **Expect**: it works — a visitor has no
   stored preference, so the choice must not require a sign-in.
3. Confirm every state badge carries a text label beside its colour.
4. Navigate every screen by keyboard alone. **Expect**: every control reachable and operable.
5. View at desktop and phone width in both languages. **Expect**: correct in all four combinations.

```bash
php artisan test tests/Architecture/LocalizationParityTest.php
npx playwright test tests/Visual/support.spec.ts
npx playwright test tests/Visual/support-axe.spec.ts     # zero AA violations
```

---

## Scenario 21 — The public surface stays bounded

```bash
php artisan test tests/Architecture/PublicSurfaceBoundedTest.php
```

**Expect**: it fails the build if any route is registered outside the authenticated stack other than the
five declared in [contracts/public.md](./contracts/support-public.md) — two that act and three that serve them.
The constitution permits a public endpoint only where it creates no account and exposes no record, and
names these two functions as the only public ones, so a sixth route requires an amendment, not a pull
request.

---

## Full gate before acceptance

```bash
vendor/bin/pint --test          # formatting
vendor/bin/phpstan analyse      # Larastan
php artisan test                # all suites except Performance
PERF_SUITE=1 php artisan test --testsuite=Performance
php artisan test --testsuite=Architecture
npx playwright test             # visual regression (components + screens + RTL) and axe-core

# Amendment 2 — the support channel's own gates:
php artisan test tests/Feature/Support/Enumeration    # byte-identical failures + the timing floor
php artisan test tests/Feature/Support/Throttle       # the two limits, proven independently
php artisan test tests/Feature/Support/Retention      # anonymisation, and what survives it
php artisan test tests/Architecture/PublicSurfaceBoundedTest.php
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
4. Arabic and English both complete — scenario 7, with fidelity and accessibility proven in
   scenario 8.
5. Performance budgets measured, not assumed — the `PERF_SUITE` runs in scenarios 3, 5, 7, 9, 10.
6. Empty, loading, error, and success states present — scenarios 6.6 and 7.4–7.5.
7. High-impact actions gated by a confirmation restating the impact — scenario 7.6.

**Amendment 2 adds four gates of its own**, all in Scenarios 11–21:

8. The **enumeration suite** passes — byte-identical bodies, identical statuses, no varying header, and
   the timing floor (Scenario 12). This is the single most important check in the support channel: the
   difference between a support form and a roster-enumeration oracle.
9. Both rate limits proven **independently** (Scenario 13 steps 1 and 2) — a single combined limiter
   passes one and fails the other while leaving the platform exposed.
10. The challenge proven to **fail closed** (Scenario 14 step 6).
11. Retention verified, including its visibility consequence (Scenario 19).

---

## Troubleshooting

| Symptom | Cause |
|---|---|
| Import stuck at `validating` | Queue worker not running, or Redis unreachable. By design the import waits rather than fails. |
| Arabic renders as disconnected glyphs | `app-rtl.css` not built, or Cairo not loaded from `public/fonts/`. This is a release blocker, not cosmetic. |
| Visual regression fails everywhere at once | Fonts not yet self-hosted (so the reference and the build render in different families), or `UiFixtureSeeder` not run so the two render different data. Check both before chasing individual diffs. |
| Colours differ from the reference by a shade | Expected in exactly seven places — check `design-system/ACCESSIBILITY-DELTA.md` before treating it as a defect. Anywhere else, the token layer has drifted and `TokenParityTest` should have caught it. |
| Bootstrap's blue or radii showing through | `_bootstrap-bridge.scss` not imported before the Bootstrap build in the entry file, so Bootstrap's defaults win. Order in `app-rtl.scss` / `app-ltr.scss` is tokens → bridge → bootstrap → shell → components. |
| 403 where scenario 2 expects 404 | A policy is running before the global scope — the scope is missing from that model or the route is not resolving through it. Defect against constitution I. |
| Audit `REVOKE` migration skipped | The local database user lacks GRANT privileges; the migration no-ops by design. The grant must be verified in staging and production. |
| `SQLSTATE[HY000]: 1366` on Arabic text | The database, table, or connection is not `utf8mb4`. |
