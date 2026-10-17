# Security Evidence Log

`/security-implement` appends one entry per completed phase, using the closeout shape below. Every
entry holds real command output: counts, statuses and IDs. It never contains secret values or real
student names or IDs. Approvals for ⛔ actions are quoted with their date.

```text
Phase:
Date:
Commands:
Evidence:
Findings:
Changes:
Residual risk:
Rollback:
Gate result:
```

## Entries

### 2026-10-17 02:07 UTC - Phase 1: Repository PII containment (in-tree) — IN PROGRESS, gate not yet passed

```text
Phase: 1 (S1, S5) — G-01 in the working tree
Date: 2026-10-17
Gate result: NOT YET PASSED — open: SEC-023 (Linux baselines, A-6) and the deletion of 224 leftover
             files in storage/ (owner approval requested, verification below). SEC-024 is therefore
             recorded as "clean outside storage/", not ticked.
```

**Tooling (outside the repository, `C:\Users\dreng\track-pii-mapping\`).** `build_mapping.py`,
`add_variants.py`, `check_pairs.py`, `redact_xlsx.py`, `apply_mapping.py`, `redact_sqlite.py`,
`scan.py`, `scan_storage.py`, `verify_leftovers.py`, and `mapping.json`, which holds the real values and
so never enters the repo. Every script prints counts and paths only. The original export is backed up
at `C:\Users\dreng\track-pii-originals\design-system\uploads\filedownloadservlet(21).ORIGINAL-tracked.xlsx`.

**SEC-010 — before.** `scan.py .` (git-tracked text files) found 7 files, 113 exact matches and 290
3-word name matches:

| File | Exact | 3-word |
|---|---|---|
| `UiFixtureSeeder.php` | 27 | 83 |
| `_ds_bundle.js` | 40 | 108 |
| `store.js` | 31 | 95 |
| `ImportScreens.jsx` | 1 | 0 |
| `ui-conflicts.md` | 6 | 0 |
| `CR-002-student-support.md` | 6 | 0 |
| `RegistrarRealFileTest.php` | 2 | 4 |

**SEC-011 — mapping.**
- 22 IDs map to `999000001`–`999000022`; this block is unused elsewhere (`git grep 9990000` found 0).
- 22 names map to fictional names with the same number of words.
- 14 spelling variants found in the seeder and kit were added by 3-word overlap, with 0 ambiguous.
  That gives 58 entries.
- `check_pairs.py` cross-checked against the export's own rows: 22 rows paired, 67 ID+name pairs in
  the files checked, 0 mismatched.

**SEC-012 — redacted export.**
- Changed parts: only `xl/sharedStrings.xml` (44 strings replaced) and `docProps/core.xml`.
- **Deviation from the plan, recorded here:** `dc:creator` and `cp:lastModifiedBy` held a two-word,
  12-character value that may be a person's name. Both are now `Registrar`.
- Every other part's content is identical, in the same order. `unzip -t`: no errors.
- New SHA-1 `ef3fa59ed7a8e654d9db1706afe9cfd730294a24` (was `792bcd78…`). It is installed at
  `design-system/uploads/filedownloadservlet(21).xlsx` and `design-system/scratch/roster.xlsx`.

**SEC-013 to SEC-016 — `apply_mapping.py` replacements.**

| File | Replacements |
|---|---|
| Seeder | 38 |
| `_ds_bundle.js` | 54 |
| `store.js` | 44 |
| `ImportScreens.jsx` | 1 |
| `ui-conflicts.md` | 6 |
| `CR-002` | 6 |
| `RegistrarRealFileTest.php` | 2 |

- The `RegistrarRealFileTest` SHA-1 pin was re-pinned to the redacted export.
- Comments in `tests/Support/Registrar.php` and `CentralModeRejectsRegistrarFileTest.php` now say the
  file is redacted.
- `design-system/VENDORED.md` records a data-only exception to "never edited".

**SEC-017 — A-1, approved by the owner on 2026-10-17 ("افعل الافضل بدون التأثير على عمل المشروع").**
- Moved to `C:\Users\dreng\track-pii-originals\`: `design-system-v1/` (175 of 175 files, `diff -rq`
  clean before removal), `design-system/scratch/roster_sample.xlsx`, and
  `design-system/uploads/filedownloadservlet(21)-4d54b7af.xlsx`.
- Everything that references `design-system-v1` is an exclusion only (Vite, PHPStan, Pint,
  `.gitattributes`), apart from the history in `VENDORED.md`.

**SEC-018 — `.gitignore`.**
- `git check-ignore` reports these as ignored: `design-system/uploads/new.xlsx`,
  `design-system/scratch/x.xlsx` and `design-system-v1/a`.
- These are not ignored: both names of the redacted export.

**SEC-019 — `tests/Architecture/NoPersonalDataFixturesTest.php`.**
- It checks four things:
  - the only tracked spreadsheets are the two redacted names, pinned by SHA-1;
  - all 22 IDs fall in `999000xxx`;
  - three drop paths are ignored;
  - the two redacted names are not ignored.
- Mutation check: with the original export put back, **2 of 7 tests fail** (the SHA pin and the ID
  block). After restoring, the SHA-1 is confirmed as `ef3fa59e…`.

**SEC-020 — local databases.**
- `redact_sqlite.py` on `%TEMP%\track-local.sqlite` updated 19 `students` rows, with 0 residual. The
  owner's earlier admin-email change is kept.
- `%TEMP%\track-visual.sqlite` was deleted and then rebuilt by `global-setup.ts`.

**SEC-021 — validation.** PHP is `C:\Users\dreng\php84\php.exe`.

| Suite | Result |
|---|---|
| Architecture | 68/68 |
| Authorization | 145/145 |
| Scope | 77/77 |
| Enumeration | 19/19 |
| Unit | 71/71 |
| Feature, per directory (Audit excluded) | 1,313/1,313 |
| `tests/Feature/SuiteRegisteredTest.php` | 1/1 |
| Audit, files singly | 9 of 10 pass |
| Pint, on the changed PHP | passed |
| PHPStan | 0 errors |

The Feature directories:

| Directory | Result |
|---|---|
| AcademicStructure | 60 |
| Authentication | 67 |
| Authorization | 7 |
| EavSpike | 6 |
| Imports | 236 |
| Localization | 95 |
| Programs | 30 |
| Schema | 6 |
| Screens | 23 |
| Sections | 130 |
| Security | 52 |
| Shared | 36 |
| Students | 40 |
| Support | 313 |
| Terms | 24 |
| UserManagement | 27 |
| Ux | 181 |

The one Audit failure is `NoSecretsInPayloadTest`, at 0/3. It calls `actionPerformers()`, which is
defined in `ActionCoverageTest.php`, so it fails only when run alone. Run together, the pair passes
46/46. This is a pre-existing artefact of running the files singly, not caused by this phase.

**SEC-022 — Windows baselines.**

1. Run 1 on 7 specs × 8 projects with `--update-snapshots=none` (nothing written): 274 passed,
   142 failed, 88 skipped, 48 did not run, 13.0 min.
2. The failures break down as 106 pixel-diff, 32 missing baselines and 4 timeouts. The missing
   baselines and timeouts are the section-approval specs, already open as T486.
3. Attribution: the visual database was served on `:8011` and each failing screen's HTML was fetched
   and checked for replaced values.

| Screen | Replaced values in HTML |
|---|---|
| `/students` | **32** |
| `/students/500` | 4 |
| `/sections/19` (the only section holding the 19 registrar students) | 95 |
| `/sections`, `/courses`, `/imports`, `/audit?action=…`, `/sections/11`, `/sections/13`, `/home`, every support-request page | **0** |

4. The 0-hit screens therefore fail for reasons that predate this phase (uncommitted earlier work).
   They are **not** regenerated.
5. The "section detail" and "student detail" shots open row 0 of their lists: section `123` and a
   non-registrar student. Section 19 is visited only by `us5-registrar`, which has no baselines
   (T328 open).
6. **Approved by the owner on 2026-10-17 ("Regenerate those 16"):** `us4 › the students list` was
   regenerated in all 8 projects, ar and en (16 files). 14 were rewritten on the first pass, and 16
   of 16 pass.
7. The 2 files left unchanged (`ar-390`, `en-390` `ar-students.png`) were passing **within the 1%
   tolerance**. A forced `--update-snapshots=all` rewrote both, so they did differ, almost certainly
   by small name regions. **Lesson:** a passing screenshot does not prove the absence of real names.
   Attribution has to be by content, as above.

**SEC-023 — not applicable.** `ls tests/Visual/baselines` shows only `win32/`, and `git ls-files`
finds 0 non-win32 baselines. There are no Linux baselines that could show real names. Any that a CI
`--update-snapshots` run produces later will render the synthetic seeder.

**SEC-024 — after (partial).**

| Check | Result |
|---|---|
| `scan.py .` (tracked text) | 0 files, 0 exact, 0 3-word |
| `track-local.sqlite` | 0 real values |
| `track-visual.sqlite` | 0 real values |
| `./track` (older dev database) | 0 real values |
| Working-tree spreadsheets outside `storage/` | 2 files, both `real=0` |
| Rendered HTML of the screens above | `real=0` |

**New finding during SEC-024 — `storage/app/private/imports/`.**
- `scan_storage.py` checked 10,440 spreadsheets and found **224 with real values**.
- Read-only verification (`verify_leftovers.py`):
  - all 224 are byte-identical to the original committed export;
  - all were written between 2026-10-08 17:53 and 2026-10-14 23:48, over 7 days;
  - all sit in one folder, `storage/app/private/imports/2026/10/`;
  - 0 rows in any of the three local databases reference them;
  - the 8 unfinished import jobs point at `imports/fixtures/…`, which doesn't exist;
  - the path is gitignored, so the files were never pushed.
- Cause: the registrar import tests upload the export through the real `local` disk, with no
  `Storage::fake` (`RegistrarRealFileTest.php`, `CentralModeRejectsRegistrarFileTest.php`), so every
  run leaves a copy. The roster files are now synthetic, so new runs leave only synthetic copies. The
  remaining ~10,200 leftovers are G-05 in miniature on a developer machine.
- **Proposed follow-up for Phase 6:** fake the disk in import tests, or clean up after them.
- Not verifiable here: the MySQL database named in `.env`. MySQL isn't installed on this machine.
- **Deletion awaits the owner's approval.**

**Owner challenge, 2026-10-17: is the data real? Investigation, read-only.**

The owner states that no real student data was intentionally uploaded. Deleting files, rewriting
history and pushing are all on hold (owner: "STOP… Do not delete files, modify Git history, or push
changes without my explicit approval").

*Verified:*
- **Origin.** The file is in `design-system/uploads/`, which the design-system readme describes as
  files uploaded into the design tool. It has been there since `8c723b6` (the first commit). The kit's
  `store.js` labels it `REAL_FILE` and seeds students 500–518 from it. `UiFixtureSeeder` copied them
  from `80280e0` onward.
- **History.** All 9 commits on both branches (local and origin) contain the values: the two xlsx
  names (44 each), the seeder, `store.js`, `_ds_bundle.js`, `ImportScreens.jsx`, `ui-conflicts.md`
  and `CR-002`. From `204749d`, also `RegistrarRealFileTest.php` and the students baselines.
- **The 224 leftovers.**
  - All are copies of that one file.
  - They are made by `UploadImport::storeAs('imports/Y/m', …)` during `RegistrarRealFileTest`
    (5 uploads per run), which has no `Storage::fake`.
  - Leftover counts by day: 870 / 2,024 / 1,080 / 2,213 / 1,458 / 1,013 / 1,493 files from 10-08 to
    10-14; 26 on 10-15; 263 today.
  - Nothing references them, and they regenerate (as synthetic) on every run.
- **Secrets.** A pattern scan of all commits (app keys, private keys, GitHub, AWS and OpenAI tokens,
  `*PASSWORD/SECRET/TOKEN=` values) found 0. `.env.example` is the only env file ever added.

*Circumstantial, not proof:*
- The filename pattern `filedownloadservlet(21)` points to a web download.
- The layout is the registrar's exact 4-column export with a metadata block.
- The IDs are 9-digit with prefixes `442`×1, `462`×4, `471`×16, `472`×1. The 471 cluster has a
  median gap of 5.
- There are 14 distinct family names in 22, and the names have 6–7 parts.
- `docProps`: the Application is Microsoft Excel. The creator is a two-word Latin name, not the
  owner's, and it differs from `lastModifiedBy`.

*Assumptions now withdrawn as facts:*
- That realistic names and IDs mean real people.
- That earlier documents ("provided by the registration unit") verified it. They did not; they echo
  the kit's label.

*Decision path:* the owner checks the backup against section 2298 (برمجة 2, المجمعة طلاب).

**Files changed by this phase:**
- `.gitignore`
- `database/seeders/UiFixtureSeeder.php`
- `design-system/_ds_bundle.js`
- `design-system/ui_kits/web-app/store.js`
- `design-system/ui_kits/web-app/ImportScreens.jsx`
- `design-system/VENDORED.md`
- `design-system/uploads/filedownloadservlet(21).xlsx`
- `design-system/scratch/roster.xlsx`
- `specs/001-foundation-access-roster/ui-conflicts.md`
- `specs/changes/CR-002-student-support.md`
- `tests/Feature/Imports/RegistrarRealFileTest.php`
- `tests/Feature/Imports/CentralModeRejectsRegistrarFileTest.php`
- `tests/Support/Registrar.php`
- `tests/Architecture/NoPersonalDataFixturesTest.php` (new)
- 16 `tests/Visual/baselines/win32/*/us4.spec.ts/*-students.png`
- `docs/security/SECURITY-STRATEGY.md` (G-01 widened during planning)

**Residual risk.**
- Git history and `origin` still hold the real export, the real seeder, kit and specs, and the old
  baselines until Phase 2 (D-1). Owner: Dr. Ahmed Shahin. Severity: Critical.
- Linux baselines in CI still show real names until SEC-023.
- The 224 storage leftovers remain until they are deleted.

**Rollback.**
- The working-tree changes are uncommitted, so revert them file by file. The originals are kept at
  `C:\Users\dreng\track-pii-originals\`.
- Nothing on the remote changed.


### 2026-10-17 12:04 UTC - Phase 3: Session revocation on the API — GATE PASSED

```text
Phase: 3 (S2, S3) — G-02, G-17
Date: 2026-10-17
Gate result: PASSED
```

**Files changed:**
- `bootstrap/app.php`: the `routes/api_v1.php` group.
- `app/Providers/ModuleServiceProvider.php`: every module's `api.php` group. Both API stacks are
  now `web, auth:sanctum, active, session.auth, throttle:api`.
- `app/Modules/Shared/Routes/web.php`: `POST /locale` gains `active` and `throttle:30,1`.
- `tests/Feature/Authentication/ApiSessionRevocationTest.php` (new, 8 tests).
- `tests/Feature/UserManagement/DeactivateUserTest.php` (+1 test, and 1 test corrected; see Findings).
- `tests/Feature/UserManagement/ResetPasswordTest.php` (+1 test).
- `tests/TestCase.php`: `actingAs()` clears the stored `password_hash_*`.

**SEC-030 — failing first.**
- On the unchanged code: `pest tests/Feature/Authentication/ApiSessionRevocationTest.php` gave
  5 tests, 1 passed and 4 failed.
- What each failure showed:
  - A deactivated account got **200** on `GET /api/v1/auth/me`, where 403 was expected.
  - Its `PATCH /api/v1/users/{id}/deactivate` on another user got **200**, so the write happened.
  - A session from before a password change got **200**, where 401 was expected.
- This reproduced G-02 exactly.

**SEC-031 to SEC-036 — after the fix.**

| Run | Result |
|---|---|
| Revocation tests, `array` session driver | 8/8 |
| Revocation tests, `SESSION_DRIVER=file` | 5/5 (before the locale tests were added) |
| `DeactivateUserTest` + `ResetPasswordTest` | 17/17 |
| `tests/Feature/UserManagement` | 29/29 |

- Guest `POST /api/v1/auth/login` still works (200) with no exclusion needed: `active` and
  `session.auth` both pass a guest through.
- The locale switch, for a deactivated user, redirects to login and leaves the stored locale
  unchanged. A guest can still switch. The 31st call in a minute gets 429.

**SEC-037 — full suite** (`run_all.sh`, every group, Audit singly, `NoSecretsInPayloadTest` paired
with `ActionCoverageTest`).

1. First run: 9 groups failing, about 25 tests, all **401**, or follow-on null-data errors after a
   401.
2. Cause: tests that `actingAs()` two different users in one test share one session. The first
   user's stored password hash meets the second user, and `session.auth` signs them out. That is
   correct for one browser and an artefact for two people.
3. Fix in the test harness only: `Tests\TestCase::actingAs()` forgets `password_hash_<guard>` when a
   test switches user. **The production control is unchanged**, and the revocation tests never use
   `actingAs`.
4. Second run: 1,836 tests passed in 32 groups; 1 failing test (below).
5. After the correction: UserManagement 29/29. Pint passed on the changed files. PHPStan: 0 errors.

**Findings.**
- **Pre-existing test flaw, corrected.** `DeactivateUserTest › an existing session stops working on
  the very next request` passed before this phase only by accident. The admin's API call left the
  default guard on Sanctum and the session's hash mismatched, so `session.auth` refused the target.
  `EnsureUserIsActive`, which the test claims to prove, was never what refused it, because it was
  handed a stale in-memory `$target` that still said "active". The test now uses `$target->fresh()`,
  as a real next request reads from the database.
- Mutation check: with `EnsureUserIsActive` disabled, 6 tests across `DeactivateUserTest` and
  `ApiSessionRevocationTest` fail. Restored, and `git diff` of the middleware is empty.

**Residual risk.**
- `DeactivateUser` and `ResetPassword` still purge session rows only under the `database` driver.
  This is now defence in depth, not the control.
- Redis session data stays readable to anyone with Redis access until it expires (G-L1,
  `SESSION_ENCRYPT=false`). Severity: Low. Owner: operations (Phase 9 runbook).

**Rollback.**
- Revert the three route-stack edits. They are middleware additions only, with no schema or data
  change.
- The `TestCase` change is test-only.


### 2026-10-17 - Phase 4: Sign-in brute force and password ownership — GATE PASSED

```text
Phase: 4 (S2, S9) — G-03, G-04 (D-2 and D-3 decided by the owner on 2026-10-17)
Gate result: PASSED
```

**Owner decisions, quoted:**
- D-2/D-3: "12 chars + forced change (Recommended)".
- D-5: "Hide from coordinators (Recommended)". It is implemented in Phase 6.

**Files changed:**
- `app/Modules/Authentication/Http/Controllers/LoginController.php`:
  - an account limiter (20 failures per 15 minutes per email, from any address);
  - IPv6 /64 address bucketing via `SupportLimits::addressBucket`;
  - a `limit` field on `auth.login_blocked`;
  - constants for both limits.
- `app/Providers/AppServiceProvider.php`: the unused `login` limiter is removed, along with the
  comments claiming an account lockout existed.
- `app/Console/Commands/LoginUnblock.php` (new): `track:login-unblock {email}`, audited as
  `auth.login_unblocked`. The new action is registered in `lang/{ar,en}/audit.php`,
  `contracts/audit.md`, `tests/Pest.php` (`contractActionKeys`) and `ActionCoverageTest`.
- `app/Modules/Shared/Rules/PasswordPolicy.php`: `MIN_LENGTH` 8 → **12**. Also
  `lang/{ar,en}/auth.php` (rule and policy text) and `profile/password.blade.php` (`minlength` now
  comes from the constant).
- `database/migrations/2026_10_17_000001_add_password_change_required_to_users.php` (new, with a
  `down()`), plus the `User` cast and `@property`.
- `CreateUser` and `ResetPassword` set `password_change_required = true`; `ChangePassword` clears it.
- `app/Modules/Authentication/Http/Middleware/EnsurePasswordIsOwn.php` (new, alias `password.own`):
  - It sits on all 10 authenticated web groups and both API stacks, never on `web`, so the public
    routes resolve no user.
  - Allowed while the flag is set: the password form and submit, the API password endpoint, `me`,
    and logout.
  - Otherwise the web gets a redirect to the form, and the API gets 403 with
    `code: password_change_required`.
  - Translated notice: `auth.password_change.required` (ar/en).
- New tests:
  - `tests/Feature/Authentication/AccountLockoutTest.php` (7)
  - `tests/Feature/Authentication/PasswordOwnershipTest.php` (11)
- Test data updated for the 12-character rule, in passwords being **set** only. Fixture accounts
  keep `Track2026`, because the seeder bypasses the rule.
  - `NewPass2027` → `NewPass2027-Own`
  - `Reset2027x` → `Reset2027x-Long`
  - created-account passwords → `Track2026-Own` in `CreateUserTest`, `UserEndpointsTest`,
    `ActionCoverageTest`, `BulkImportReviewCorrectionsTest` and `UserImportUnusableUntilResetTest`
  - `tests/Unit/PasswordPolicyTest.php` boundaries moved to 11/12
  - `PasswordChangeTest` "same as current" uses a 12-character current password
- `LoginThrottleTest` now freezes time. It asserted exactly 60 s against a live clock, and the second
  counter per attempt made crossing a second boundary likely.
- The local site database got the new column (`php artisan migrate`, 1 migration).

**SEC-040 — failing first.** Before the controller change, `AccountLockoutTest` gave 7 tests: 1
passed, 6 failed. Notably, **20 failed guesses from 20 addresses, then the right password, signed in
(200)**, which is G-03 reproduced. IPv6 addresses in one /64 were not grouped (422 where 429 was
expected).

**After.**

| Run | Result |
|---|---|
| `AccountLockoutTest` | 7/7 |
| `PasswordOwnershipTest` | 11/11 |
| `tests/Feature/Authentication` | 93/93 |
| Full `run_all.sh` | **1,885 tests passed, 0 groups failing** (including Enumeration, Authorization, Architecture and Audit) |
| PHPStan | 0 errors |
| Pint | passed |

**Proof of no enumeration regression:** `AccountLockoutTest › both refusals look the same to the
caller`. The account and address blocks return the same JSON keys and the same message, apart from
the seconds. The Enumeration suite is green.

**Residual risk.**
- **Account lock as a nuisance:** anyone who knows a staff email can keep that account locked for
  15 minutes at a time. Mitigation: `track:login-unblock`, which is audited. Severity: Low. Owner:
  operations.
- Fixture and seeded accounts still use 9-character passwords until they are next changed. This is
  local and testing only, because the seeder refuses other environments.
- Production accounts created before this change keep their existing passwords, with no forced
  change. **Recommend** a one-time `password_change_required = true` for all existing accounts at
  go-live; this is the owner's call.
- Visual baselines of the password page change (the hint now says 12). They need regeneration on the
  owner's approval, alongside the Phase 7 error pages.

**Rollback.**
- Revert the commit.
- `php artisan migrate:rollback --step=1` drops the column.
- The limits are constants in `LoginController`.


### 2026-10-17 - Phase 5: Authorization tightening — GATE PASSED

```text
Phase: 5 (S3) — G-08, G-14, G-15, G-09, G-22
Gate result: PASSED
```

**Changes.**
- **G-08 (`ChangeSectionInstructor`):**
  - New `mayTeach()`: the target must be active, in the section's organization, and hold a role in
    `authorization.grants.grantable_roles`. That config is the one the grant endpoint already uses,
    so no role name appears in code (FR-020, `NewRoleNeedsNoCodeChangeTest`).
  - The action enforces this itself, so every caller is held to it.
  - New string: `sections.errors.instructor_invalid` (ar/en).
- **G-14 (`StoreSectionRequest`):** a named `instructor_id` must hold a teaching role.
  - **Deviation from the plan, recorded:** the plan said "an instructor caller may name only
    themselves". `ApprovalReviewRegressionsTest › 1` showed this contradicts Amendment 4, which
    deliberately lets an instructor create a section **for a colleague**, with a coordinator's
    approval as the control. That half was withdrawn. The teaching-role half stands, so a section
    can no longer be created "for" a coordinator or admin.
- **G-15:**
  - `UserRoleController` and `UserBranchController` return 404 for another organization's account,
    as `UserController` does.
  - `branch_ids.*` is scoped to the caller's organization in `UserBranchController` and
    `StoreUserRequest`.
  - `section_ids.*` is scoped through the branches of the organization.
  - `SectionAccessGrantController` refuses another organization's recipient, with the same single
    message.
- **G-09 (`EveryRouteAuthorizedTest`):**
  - "Authorizes internally" is now judged on the route's own **method** body, or a typed
    `FormRequest` parameter, instead of anywhere in the file.
  - A new test holds **every authenticated web route** to the gate, with a named allowlist (`home`,
    `profile`, `profile.preferences`, `profile.password.edit`, `profile.password.update`, `logout`)
    and a reason for each.
- **SEC-054:** a planted `GET __web_architecture_probe` (web + auth, no `can:`) is caught. Every
  allowlisted name exists.
- **G-22, SEC-055 — review of every `withoutGlobalScope` site**, done by a read-only review agent and
  checked:
  - **75 textual occurrences in 37 files: 69 call sites and 6 comments.**
  - The review's verdicts: OK for every site, except two `LEAK?` items, both fixed.
  - **`SupportQueueController::assigneeCandidates`** listed `support.view` holders across every
    branch the student is enrolled in, and with no organization filter.
    - Verified: only `assign` holders (by default the Super Admin, who sees all branches anyway) get
      this list, so the branch part was defence in depth.
    - The **organization** part was a real gap: another college's Super Admin was offered as an
      assignee.
    - Fix: own organization always; for anyone without the bypass, themselves and the staff who
      share their branches (the `assignableUsers()` rule). Test:
      `ReassignTest › never offers staff of another organization`. A mutant without the org filter
      fails it.
  - **Cross-branch import modes:** central and registrar resolve sections in any branch.
    - This was safe only because the seed gives `import.registrar.run` and `import.central.run` to
      the Super Admin alone.
    - `ImportJobPolicy::run` now also requires `scope.bypass_all` for those modes. Test: a custom
      `REGISTRAR_CLERK` role holding them cannot `run` its job.
  - Noted, not changed: `EnrollStudent` echoes a matched student's stored name in its mismatch
    warning. This only happens together with creating the enrollment, which is audited.
- **SEC-056:** new `tests/Architecture/ScopeBypassAllowlistTest.php` pins the textual count per file
  and a reason, for 38 files including the new `PruneImportFiles`. A planted bypass in an unlisted
  file fails it (1 failed, 1 passed), and the plant was removed.

**Site-by-site table (SEC-055):** per file, the count and the reason are in
`ScopeBypassAllowlistTest::REVIEWED_SCOPE_BYPASSES`, which is the durable form of this table.

**Validation.**
- `AccessGrantTargetsTest`: 11/11. Mutation: with `mayTeach` disabled, 3 fail.
- `EveryRouteAuthorizedTest`: 7/7.
- `ScopeBypassAllowlistTest`: 2/2.
- `ReassignTest`: 9/9.
- The full run (below) covers Authorization, Scope, Architecture, Sections and UserManagement.

**Rollback.** Revert. These are validation, policy and test changes only, with no schema change.

**Residual risk.** `StoreUserRequest` still hard-codes the three role names in `Rule::in` (Info,
FR-020). Left unchanged: it is validation data, not branching.

### 2026-10-17 - Phase 6: Data handling — GATE PASSED

```text
Phase: 6 (S4, S5) — G-07, G-05, G-21, G-13 (D-5 decided by the owner)
Gate result: PASSED
```

**Changes.**
- **G-07:**
  - New `app/Modules/Shared/Support/CsvSafe.php` prefixes `'` to any cell starting with `= + - @`,
    tab or CR.
  - `SectionController::export` writes every row through it.
  - The export is audited as **`section.exported`** with its filters and row count.
  - The action is registered in the contract, labels, `contractActionKeys` and the coverage
    performer.
- **G-05:**
  - New `config/imports.php` (`file_retention_days`, default 30; D-6 defaulted).
  - New `App\Modules\Imports\Support\ImportFiles::discard()`, which deletes the file and audits
    **`import.file_discarded`** (system channel, no contents).
  - `CommitImportChunkJob::complete()` deletes the file via `DB::afterCommit`.
  - New command `track:prune-import-files [--dry-run]`, scheduled daily in `routes/console.php`. It
    deletes the files of completed or failed jobs past retention, plus orphans. In-flight jobs are
    never touched.
  - Verified first: only `ValidateImportJob` reads the file. Commit, resume and the error report
    use `import_rows`.
- **G-21:** migration `2026_10_17_000002_restrict_audit_log_user_deletion` changes `user_id` from
  `nullOnDelete` to `restrictOnDelete`, with a `down()`. Checked up, down and up again on SQLite.
  MySQL will be checked in CI.
- **G-13 (D-5):**
  - `AuditLogResource` returns `ip_address` and `user_agent` as null unless the caller holds
    `scope.bypass_all`.
  - `audit/index.blade.php` drops the IP column for them.
  - The export was already Super-Admin-only.

**Validation.**

| Test | Result |
|---|---|
| `CsvExportSafetyTest` (unit cases for all 6 triggers, plus the export and audit) | 13/13 |
| `ImportFileRetentionTest` (on `Storage::fake`, see caution) | 4/4 |
| `AuditAttributionSurvivesUserDeletionTest` | 2/2 |
| `NetworkDetailsVisibilityTest` | 2/2 |
| `ActionCoverageTest` with the two new actions | passing |

- The visibility test inserts rows with a known address. Under test, the Auditor records a
  placeholder, which would make "not shown" pass vacuously. A mutant that always shows the address
  fails it.
- The local site database is migrated (2 migrations).

**Caution recorded.** `track:prune-import-files` deletes **orphaned** files under
`storage/app/private/imports/`. On this developer machine that would include the 224 leftover files
whose deletion the owner has **not** approved. It was therefore **not run** against real storage,
and its tests use a fake disk. It only runs from the scheduler (`schedule:run`), which is not active
locally.

**Residual risk.**
- Backups keep deleted roster files until they age out (runbook §5a).
- The `support.request_created` payload still carries `student_id` and the submitting IP for 5 years
  (002 R-03). That awaits the owner and DPO.

**Rollback.**
- Revert.
- `php artisan migrate:rollback --step=1` for the FK.
- File deletion cannot be undone by design.

**Full run after both phases:** `run_all.sh` — **1,924 tests passed, 0 groups failing**; `pint --test` repo-wide passed; PHPStan 0 errors.


### 2026-10-17 - Phase 7: Header edges and production-safe configuration — code complete, gate OPEN (baselines)

```text
Phase: 7 (S7, S1) — G-06, G-12
Gate result: OPEN — only SEC-075's Windows baselines for the three new error pages remain; creating
             baselines needs the owner's approval (A-6 class). Everything else is verified below.
```

**Changes.**
- **SEC-070/071:**
  - `ContentSecurityPolicy::apply()` is now shared.
  - New global middleware `EnsureSecurityHeaders` gives the same header set to any response without
    a policy: unmatched URLs, `/up`, `/health`. The probes also get `Cache-Control: no-store`.
  - `dev.*` keeps its exemption.
  - The plan's `Route::fallback` was **not** used. A global middleware covers the same responses
    without adding a new public route, which `PublicSurfaceTest` would have had to allow.
- **SEC-072:**
  - New `.env.production.example`: names only, with production values (debug off, `LOG_LEVEL=info`,
    `SESSION_ENCRYPT=true`, a non-root database user, a real mailer, `ALERT_EMAIL` and
    `SENTRY_LARAVEL_DSN` slots) and every secret empty.
  - `.env.example` now opens with a pointer to it.
  - It is not gitignored.
- **SEC-073:** `AppServiceProvider::refuseUnsafeProductionConfiguration()` throws on production or
  staging when `APP_DEBUG` is on, `SESSION_SECURE_COOKIE` is off, or `LOG_LEVEL` is debug.
- **SEC-074:** new `errors/419`, `errors/429` (same layout as 404) and `errors/500` (self-contained:
  no session, user or database). The strings `common.errors.{sessionExpired,tooMany,server,…}` are in
  ar and en.
- **Existing test updated:** `ContentSecurityPolicyTest › the health probe…` pinned "no CSP on
  /health". It now asserts that the probe is still outside `web` **and** carries the policy, which is
  the intent of G-06.

**Validation.**

| Run | Result |
|---|---|
| `EdgeResponseHeadersTest` | 5/5 (unmatched 404 headers; no audit entry for a guest 404; `/up` and `/health` headers and no-store; a page carries exactly one CSP) |
| `ProductionConfigurationTest` | 7/7 |
| `ErrorPagesTest` (3 statuses × ar/en: wording, no exception text, CSP present) | passing |
| `tests/Feature/Security` | 79/79 |
| Architecture + Localization (`NoHardcodedStringsTest`, `NoHardcodedVisualValuesTest`, parity) | 170/170 |
| **Playwright `csp.spec`, `ar-1440` + `en-1440`** | **60/60 passed** (3.6 min), including `refusal-404` |

**Open.** SEC-075: Windows baselines and an axe/CSP browser pass for the new 419, 429 and 500 pages,
which need the owner's approval to record. SEC-077: the gate.

**Rollback.** Revert. These are config, middleware and views only.

### 2026-10-17 - Phase 8: Logging redaction and security alerting — GATE PASSED

```text
Phase: 8 (S10) — G-10, G-20
Gate result: PASSED
```

**Changes.**
- **SEC-080:** new `App\Modules\Shared\Logging\RedactingProcessor` on the `json` and `alerts`
  channels.
  - Masks keys matching password, token, secret, `_hash`, cookie, authorization and api-key, at any
    depth.
  - Replaces a `QueryException` with a normalised form built from the **raw SQL** (placeholders
    only), with quoted values in the driver message masked. Class, code, file and trace are kept.
- **SEC-081:** explicit `dontFlash`: `current_password`, `password`, `password_confirmation`,
  `_token`.
- **SEC-082:** `track:monitor` gains these checks:

| Alert | Fires when |
|---|---|
| `login_blocked` | any in the window |
| `login_failures` | more than `ALERT_LOGIN_FAILURES`, default 50 |
| `access_denied_spike` | one user has more than `ALERT_DENIED_PER_USER`, default 20; carries user ids only |
| `audit_grants_drift` | MySQL/MariaDB, each run, via `AuditGrants::mutationsAllowed` |

  - Counting is done in PHP, not with a raw HAVING (constitution: no raw SQL).
- **SEC-083:**
  - New `track:preflight`: on production or staging it fails when `ALERT_EMAIL` is not an address or
    `MAIL_MAILER` is `log`/`array`, and it warns when Sentry is unset.
  - Added to runbook §2's deploy steps and the §1 table.
  - The §7 alert table lists the new conditions.

**Validation.**

| Test | Result |
|---|---|
| `LogRedactionTest` | 5/5 |
| `MonitoringTest` (+4 security conditions) | passing |
| `PreflightTest` | 4/4 |
| `tests/Feature/Shared` | 45/45 before `PreflightTest` was added |

`LogRedactionTest` covers:
- keys at depth;
- a **real unique-constraint violation** whose message contains the email, which is absent after the
  processor while the `insert into … ?` statement remains;
- both channels written to file;
- `password` never flashed.

**Full run after Phases 7 and 8:** `run_all.sh` passed **1,952 tests with 0 groups failing**.
PHPStan reports 0 errors, after typing four new test helpers. Pint passes repo-wide.

**Residual risk.**
- Stack traces stay in the logs by design (`includeStacktraces`). They hold file paths, not values.
- The `daily` and other non-json channels are not tapped, and are not the default stack.
- Certificate-expiry alerting is the host's job (D-7).

**Rollback.** Revert. These are processor, config and command changes only.


### 2026-10-17 - Phase 9: CI and deploy hardening — code and docs complete, gate OPEN (needs a CI run)

```text
Phase: 9 (S8) — G-11, G-18, 001 R-04/R-05
Gate result: OPEN — SEC-090/SEC-095 can only be proven by a GitHub Actions run, which needs a push;
             the owner has asked that nothing be pushed without explicit approval.
```

**Changes.**
- **SEC-090, `.github/workflows/ci.yml`:**
  - top-level `permissions: contents: read`;
  - `composer audit --locked` in the quality job;
  - `npm audit --audit-level=high` in the visual job;
  - a new `secrets` job running gitleaks over the full history (`fetch-depth: 0`);
  - artifact retention cut from 14 to 7 days;
  - every third-party action pinned to a commit SHA, with its tag in a comment.
- The SHAs were resolved live with `git ls-remote` rather than written from memory:

| Action | Pinned to |
|---|---|
| `actions/checkout` v4 | `11d5960a…` |
| `shivammathur/setup-php` v2 | `eb7c497e…` |
| `actions/cache` v4.3.0 | `0057852b…` |
| `actions/setup-node` v4.4.0 | `49933ea5…` |
| `actions/upload-artifact` v4 | `ea165f8d…` |
| `gitleaks/gitleaks-action` v2 | `ff98106e…` |

  `actions/cache` and `actions/setup-node` publish no moving `v4` tag, so the latest v4 release is
  pinned.
- **SEC-091:**
  - `/health` returns only `{status}` and keeps its 200/503.
  - The component detail needs the `X-Health-Token` header matching `HEALTH_TOKEN`, compared with
    `hash_equals`.
  - With no token configured, production and staging show the status only; a developer machine still
    shows the detail.
  - `HEALTH_TOKEN` is in both env example files.
- **SEC-092 to SEC-094, `docs/runbook-deploy.md` §8 (new):**
  - TLS and HSTS, with HSTS at the server as the primary control;
  - an nginx vhost: root at `public/`, dotfile and backup-extension deny, `client_max_body_size`;
  - an ownership and permission table;
  - secrets provisioning and rotation, including the real `APP_KEY` side effects. The support
    channel's HMACs are keyed on `APP_KEY`, which was verified in the code, and the first draft's
    invented `SUPPORT_*_KEY` row was removed;
  - Redis isolation (001 R-05) and encryption at rest (001 R-04);
  - the release rollback procedure;
  - the D-7 deferral table.
- §1 and §2 gained `track:preflight` (Phase 8), and §7 lists the new alerts.

**Validation.**
- `HealthEndpointTest`: 12/12, including 4 new cases: production without a token, with the right
  token, with a wrong token, and production with no token configured.
- The CI YAML could not be parsed locally (no YAML library on this machine), and gitleaks is not
  installed here. **Both are unverified until the first CI run.**

**Deferred (D-7).** WAF and edge limits (002 R-05), `TRUSTED_PROXIES` values, TLS termination and
certificate expiry, backup location and key custody, and the incident contact. All are listed in
runbook §8.8 and the residual-risk register.

**Rollback.** Revert the workflow, the controller, the configuration and the runbook.

### 2026-10-17 - Phase 10: Backups and restore — GATE PASSED, with the restore drill DEFERRED to M7

```text
Phase: 10 (S10) — G-19
Gate result: PASSED as the plan defines it — the drill is recorded as DEFERRED (no staging host yet),
             deadline M7 2026-11-19, owner Operations; it is not silently passed.
```

**Changes.**
- New `config/backup.php`. It is disabled unless `BACKUP_ENABLED` is set; the production template
  turns it on and the developer template leaves it off.
- **SEC-100, `track:backup [--label=]`:**
  - runs `mysqldump --single-transaction` through `gzip` and `openssl enc -aes-256-cbc -pbkdf2`, under
    **bash with `pipefail`**, so a failed dump cannot produce an empty but valid encrypted file;
  - passes the database password through `MYSQL_PWD` in the process environment, never on a command
    line;
  - copies the private uploads with `rsync`;
  - writes a status marker on success only;
  - prunes daily dumps past 30 days and keeps labelled term-closure snapshots;
  - refuses a missing key file before running anything.
- **SEC-101:** scheduled daily at 23:00 UTC (02:00 Asia/Riyadh). `ChangeTermStatus` queues a
  `--label=term-closure-<id>` snapshot after the commit when a term is closed.
- **SEC-102:** `track:monitor` raises `backup_stale` when the last success is older than 26 hours, or
  when there has never been one. It applies only where backups are enabled.
- **SEC-103, restore drill: DEFERRED.**
  - There is no staging host yet.
  - It needs approval A-8, since it runs against a real database.
  - Deadline M7 (2026-11-19), owner Operations.
  - The procedure is runbook §6, and the item is in the residual-risk register (G-19b).

**Validation.** `BackupTest`: 8/8, with `Process` faked so no real dump runs. It covers:
- the pipeline's contents, with the password **absent** from the command and present in
  `MYSQL_PWD`;
- `rsync`;
- a failed dump exits 1 and leaves the marker untouched;
- a missing key runs nothing;
- pruning keeps snapshots;
- disabled means a no-op;
- the schedule is `0 23 * * *`;
- closing a term queues the snapshot;
- the monitor alerts when stale and stays quiet when fresh.

**Full run after Phases 9 and 10:** `run_all.sh` passed **1,964 tests, 0 groups failing**. Pint passed.
PHPStan reports 0 errors, after moving `BackupTest`'s per-test state out of `$this` and splitting two
chained negative expectations.

**Rollback.** Remove the schedule line. The command and the configuration are additive.

### 2026-10-17 - Phase 11: Release gate — PREPARED, awaiting owner sign-off

```text
Phase: 11 (S11)
Gate result: OPEN — SEC-113 (owner sign-off) and SEC-114 depend on the owner and university IT.
```

- **SEC-110, review commands re-run:**
  - `composer audit --locked` (PHP 8.4): **no advisories**;
  - `npm audit`: **0 vulnerabilities** (info 0, low 0, moderate 0, high 0, critical 0);
  - full Pest suite: 1,964 passed;
  - Playwright `csp.spec`: 60/60 (Phase 7).
  - The full Playwright matrix was not re-run. It takes 5–6 hours locally, and 106 pre-existing
    baseline differences unrelated to this plan were found in Phase 1. Those belong to the owner's
    baseline decisions (T313, T328, T343, T388, T410, T428, T486).
- **SEC-111:** `docs/security/RESIDUAL-RISK-REGISTER.md` written. It covers:
  - open owner decisions (G-01 real or synthetic, the 224 files, D-4, 002 R-02/R-03/R-06, pre-existing
    passwords);
  - risks accepted by design;
  - the D-7 and host deferrals, including the restore drill;
  - approval-gated items;
  - closed gaps.
- **SEC-112:** D-4 (proof of work as the CAPTCHA) is recorded in the register as an open owner
  decision.
- **SEC-113 and SEC-114:** waiting for the owner's sign-off and the university IT approval (due
  2026-11-12).
