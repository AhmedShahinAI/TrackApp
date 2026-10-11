# Validation record — quickstart scenarios 1–10 (feature 001)

**Task:** T285 · **Date:** 2026-10-09 · **Build:** working tree on `001-foundation-access-roster`
(Phases 1–11), all gates green (T284).

**How each scenario was walked.** By the test harness and browser automation, not by a person clicking.
Every step below is backed by a named test that performs it against the running application, and each
of those tests was run on this build. Where a step had no single test, a walk was written and run
(scenario 5's PRD gate: `tests/Performance/PrdGateRosterTest.php`). A person's click-through on
staging is still worth doing before go-live; the sign-off table at the end is for that.

Legend: **PASS** — performed and met; **N/A** — the phase that delivers it is not built yet.

## Scenario 1 — accounts and sign-in (US1 → SC-001) — PASS

| Step | Evidence | Result |
|---|---|---|
| Super Admin signs in; creates a coordinator (branch required) and an instructor | `CreateUserTest`, `tests/Authorization/UserEndpointsTest.php`, `us1.spec.ts` (create dialog) | PASS |
| First sign-in shows a dismissable banner; the second does not | `FirstLoginTest`, `us1.spec.ts` "first-sign-in banner" (both locales, users 3 and 12) | PASS |
| Password change signs out other sessions; reusing the same password is refused | `PasswordChangeTest` | PASS |
| Six wrong passwords → generic message, then a 60-second block with countdown | `LoginThrottleTest`, `us1.spec.ts` "lockout countdown" | PASS |
| Deactivated account refused with the inactive message; name stays on records | `DeactivateUserTest`, `us1.spec.ts` "deactivated refusal" | PASS |
| `/register` is 404 | `AbsentRoutesTest` | PASS |
| 60-minute idle → sign-in prompt | `LoginTest` "an hour of inactivity ends the session": 59 minutes idle stays signed in, 61 more is sent to sign-in (clock travel, database sessions, a fresh store per request) | PASS |

## Scenario 2 — scoping (US2 → SC-002, SC-016) — PASS

| Step | Evidence | Result |
|---|---|---|
| Coordinator sees only their branch's records and totals | `CoordinatorBranchScopeTest` | PASS |
| Another branch's section by id, page and API → **404, not 403** | `OutOfScopeReturns404Test` | PASS |
| Export has no other-branch rows | `ExportRespectsScopeTest` | PASS |
| Instructor sees only own sections; a granted one is labelled shared | `InstructorSectionScopeTest`, `AccessGrantEndpointTest`, `us2.spec.ts` "sections-shared" | PASS |
| Missing permission → 403 page and an `access.denied` entry | `MissingPermissionReturns403Test`, `tests/Authorization/*` deny cases | PASS |
| Branch change applies without signing in again | `ScopeChangeTakesEffectImmediatelyTest` | PASS |

## Scenario 3 — audit trail (US3 → SC-003, SC-004) — PASS

| Step | Evidence | Result |
|---|---|---|
| Creation, role change and refusal each write one complete entry (actor, action, entity, before/after, time, IP, agent) | `ActionCoverageTest`, `OldAndNewValuesTest` | PASS |
| Filter by person and by date (Riyadh days) | `AuditEndpointsTest`, `ExportSafetyTest` date cases | PASS |
| No edit or delete anywhere | `ImmutabilityTest` layers 1–2 (layer 3 needs MySQL — runbook §4) | PASS (layer 3 pending staging) |
| Audit write shares the action's transaction | `TransactionalWriteTest`, `AuditWritesInsideCallerTransactionTest` | PASS |
| 1,000,000 rows filtered in < 2 s | `tests/Performance/AuditLogFilterTest.php` | PASS |

## Scenario 4 — academic structure (US4 → SC-011, SC-012, SC-018) — PASS

| Step | Evidence | Result |
|---|---|---|
| Only one current term | `SingleCurrentTermTest` | PASS |
| Section 123 in two branches allowed; a duplicate in one branch refused, API included | `SectionUniquenessTest` | PASS |
| One student, two sections → one record, two enrollments | `SingleStudentRecordTest` | PASS |
| Withdrawal needs a reason; the record survives | `WithdrawEnrollmentTest`, `DialogReturnsWithErrorsTest` | PASS |
| A branch with sections cannot be deleted, only deactivated | `NoHardDeleteTest` (feature and architecture) | PASS |
| Instructor change moves access, records history, keeps authors | `ChangeInstructorTest` | PASS |

## Scenario 5 — central roster import (US5 → SC-005 … SC-009, **PRD gate**) — PASS

Walked end to end by `PrdGateRosterTest` on a `track:make-roster-fixture --rows=5000 --with-defects` file:

| Step | Measured | Result |
|---|---|---|
| Upload → `uploaded`, no public URL | status `uploaded`; private disk has no route (`CsrfCoverageTest`) | PASS |
| Validation leaves the core tables unchanged (FR-060) | `CoreCounts::snapshot()` identical before and after | PASS |
| Commit imports ≥ 97 % (SC-006), no manual repair (SC-005) | **5,008 rows, 7 rejected, 99.9 % imported**, 14.6 s | PASS |
| Error report lists every rejected row (SC-009) | report rows = rejected rows, exactly | PASS |
| Re-upload flagged, zero new records (SC-008) | `duplicate_of` = first job; 0 imported; counts unchanged | PASS |
| Mapping refused until required targets mapped; preview shows every defect reason | `MappingTest`, `PreviewTest`, `ValidationCategoriesTest` | PASS |
| Resume after a chunk failure | `ResumeAfterChunkFailureTest` | PASS |
| 10,000 rows in < 5 minutes (SC-007) | `ImportThroughputTest`: **~20 s** | PASS |
| Worker stopped → "waiting", not an error | `QueueUnavailableTest`, `us5.spec.ts` waiting state | PASS |

## Scenario 6 — instructor section import (US6 → SC-010) — PASS

| Step | Evidence | Result |
|---|---|---|
| New student created, existing linked with a name warning, duplicate rejected by row | `SectionModeImportTest`, `LinkExistingStudentTest`, `InFileDuplicateRejectedTest` | PASS |
| Nothing saved before confirmation | `NoWritesBeforeConfirmTest` | PASS |
| Another instructor's section → 404 and `access.denied` | `InstructorCannotImportForeignSectionTest` | PASS |
| No sections → empty state naming whom to contact | `InstructorWithNoSectionsTest`, `us4.spec.ts` instructor empty state | PASS |

## Scenario 7 — bilingual and accessible (US7 → SC-013 … SC-015) — PASS

| Step | Evidence | Result |
|---|---|---|
| Arabic mirrored, English LTR, on every screen | `EveryScreenRendersBothDirectionsTest`, `rtl-mirroring.spec.ts` | PASS |
| No untranslated text; badges carry labels | `NoUntranslatedOutputTest`, `NoHardcodedStringsTest`, `StatusBadgeRequiresLabelTest` | PASS |
| Field errors on blur and submit, beside the field, in the page's language | `ValidationStatesTest`, `DialogReturnsWithErrorsTest` | PASS |
| Confirmation restates impact | `ConfirmationRequiredTest` | PASS |
| Keyboard only | `keyboard.spec.ts` | PASS |
| 1280 / 1440 / 1920 / 390 | `screens-all.spec.ts` + story specs, 8 projects | PASS |
| Western digits, DD/MM/YYYY | `FormatTest`, `DateColumnsSortTest` | PASS |
| First paint < 2 s | `PageLoadTest` (server p95 ≈ 0.1 s), `fcp.spec.ts` (slowest ≈ 1.8 s, throttled) | PASS |

## Scenario 8 — design-system fidelity (SC-013, SC-014) — PASS

| Step | Evidence | Result |
|---|---|---|
| Tokens and no hard-coded visual values | `TokenParityTest`, `NoHardcodedVisualValuesTest` | PASS |
| Components ≤ 1 % at 1440, both locales | `components.spec.ts` | PASS |
| Every screen × 2 locales × 4 widths | `screens-all.spec.ts` (one recorded exception: the first-sign-in banner, photographed at 1440 in each locale, since signing in consumes the account) | PASS |
| Zero AA violations | `axe.spec.ts`, `axe-all.spec.ts` (screens, dialogs, refused states, toasts, drawer) | PASS |
| Gallery beside the kit; only the darkened colours differ | Human review — the walkthrough with the design author is T283 (pending) | Pending T283 |

## Scenario 9 — programmes, registrar import, bulk import (Amendment 1) — N/A

Delivered by Phases 13–16 (programme screens, registrar import mode, bulk course and staff import),
which are not built yet. Programme *schema* exists (sections require a programme), and
`CentralModeRejectsRegistrarFileTest` proves the central mode refuses a registrar file — the registrar
mode itself is Phase 15.

## Scenario 10 — EAV capacity spike (FR-087/088 → SC-017) — PASS on SQLite; MySQL run pending

`EavSpikeSeeder` (1,500,000 values) and `EavSpikeTest` (p95 of 10 fresh-connection runs per query),
2026-10-09 — all five thresholds pass, with and without the measurement indexes; tightest margin 59×
(warm-cache runs, measured after the Phase 12 review corrected the queries and the method). **Provisional.**
Details and the index recommendation: `specs/001-foundation-access-roster/spike-eav-results.md`. The
same test must be run once on MySQL / MariaDB (`EAV_SPIKE_MYSQL_DATABASE=<schema>`) and recorded there
before Phase 2 is planned (FR-088).

## PRD Phase 1 gate checks

| Gate (PRD) | Result | Evidence |
|---|---|---|
| The Super Admin creates coordinators and instructors | **MET** | Scenario 1 |
| A real roster of ≥ 5,000 rows imports with an error report | **MET** | Scenario 5, `PrdGateRosterTest` |
| Coordinator and Instructor see only their scoped data | **MET** | Scenario 2 |
| All authorization tests pass | **MET** | `tests/Authorization` + `tests/Scope` + `EveryRouteAuthorizedTest`, in the T284 run |

## Constitution Definition of Done

| # | Item | Result |
|---|---|---|
| 1 | Every PRD acceptance criterion demonstrably met | **Met for US1–US7.** Amendment 1 (US8, programmes, registrar mode) and the EAV spike are later phases (scenarios 9–10). |
| 2 | Authorization tests, allowed and denied, on every new endpoint | **Met** — `EveryRouteAuthorizedTest` gates both "has a check" and "is tested". |
| 3 | Audit entries for every sensitive action | **Met** — `ActionCoverageTest` against the closed action list. |
| 4 | Arabic and English complete and verified | **Met** — scenario 7. |
| 5 | Performance budget measured | **Met** — `PageLoadTest`, `fcp.spec.ts`, `ImportThroughputTest`, `AuditLogFilterTest`, `PrdGateRosterTest`. |
| 6 | Empty, loading, error and success states | **Met** — screen sweeps and `DialogReturnsWithErrorsTest`; the §10 matrix is pinned by the story specs. |
| 7 | High-impact actions behind a confirmation that restates the impact | **Met** — `ConfirmationRequiredTest`. |

## Open before go-live (not defects in this build)

- T282 — vector logo originals requested (`docs/requests/C-06-logo-vector-originals.md`), needed before
  Phase 3's PDF exports.
- T283 — design author's acknowledgement of the nine palette corrections
  (`docs/requests/C-17-accessibility-acknowledgement.md`).
- On the servers: a DBA applies the audit `REVOKE` once (the deploy's `track:audit-grants --strict`
  fails until then); set `SENTRY_LARAVEL_DSN`, `ALERT_EMAIL` and the scheduler cron (runbook §2.3, §7).

## Sign-off (person's click-through on staging)

| Scenario | Walked by | Date | Result |
|---|---|---|---|
| 1–8 | | | |
