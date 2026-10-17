# Implementation Plan: Foundation — Access Control & Academic Roster

**Branch**: `001-foundation-access-roster` | **Date**: 2026-09-26 (design-system integration
2026-09-27) | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-foundation-access-roster/spec.md`

## Summary

Stand up the TRACK platform's access-control and academic-data foundation: staff accounts
and session sign-in with no self-registration, permission-based RBAC with automatic branch
and section scoping on every query, a transactional append-only audit trail, the full
academic structure (terms, branches, courses, sections, students, enrollments), and a
six-step queued roster Import Wizard in central and instructor modes — all behind a
bilingual Arabic-RTL / English-LTR shell.

Technical approach: a fresh Laravel application organized as domain modules under
`app/Modules`, with every interactive screen served by Blade + Alpine.js over a versioned
REST API under `/api/v1` on the Sanctum-on-session guard. Scoping is enforced by Eloquent
global scopes resolved from the authenticated user's branch and section assignments, so new
endpoints inherit it; out-of-scope reads surface as 404 through route-model binding that
resolves against the already-scoped query. Auditing is an observer-driven write executed
inside the caller's open transaction, with the application database user denied UPDATE and
DELETE on `audit_logs` so the append-only guarantee holds at the engine level. The import
runs as a chain of queued jobs staging every parsed row into `import_rows` before any core
table is touched, committing in 500-row transactional chunks that record a checkpoint so a
failed chunk is resumable. A closing performance spike seeds 1,500,000 typed EAV values to
prove the Phase 2 storage design against the constitution's timing gates before Phase 2
depends on it. (It was briefly gated on [ui-conflicts.md](./ui-conflicts.md) C-08, since the approved
UI modelled initiative progress as ordered stages; the Product Owner confirmed on 2026-09-27 that the
PRD's dynamic-field model stands, so the spike proceeds as specified.)

**Amendment 1 (2026-09-27)** added academic programs, three further import modes — the registration
unit's own export format, course bulk import, and staff bulk import — and five stored attributes,
all accepted from the approved UI. See "Functional additions accepted from the UI" below.

The frontend is built to the approved **TRACK Design System**, vendored at `design-system/`: its
141 tokens become the canonical CSS custom-property layer, its 27 React reference components are
re-authored as 24 Blade components with Alpine behaviour, and fidelity is enforced by Playwright
visual regression against the reference rather than by review. Bootstrap is retained for the grid,
utilities, and RTL/LTR machinery with its Sass variables driven from the tokens, so the brand
displaces Bootstrap's defaults rather than competing with them.

## Technical Context

**Language/Version**: **PHP 8.4.26** — confirmed at kickoff 2026-10-05. The constitution requires
8.3+; 8.4 is the floor in practice because Pest 5 requires it and Pest 4 cannot coexist with the
PHPUnit and `laravel/pao` versions Laravel 13 ships. Production must match (research.md R-07)

**Primary Dependencies**: Laravel (pin the latest stable major supported on PHP 8.3 at
project initialization — confirm the exact version at kickoff), `laravel/sanctum`,
`spatie/laravel-permission`, `maatwebsite/excel`, `openspout/openspout` (streaming reader
behind Excel reads), Bootstrap 5.3 (separate RTL and LTR builds, visually displaced by the
token bridge — see "UI Design System Integration"), Alpine.js 3.x, Vite

**Design system**: `TRACK Design System_V2.zip` vendored read-only at `design-system/` — 141 CSS
tokens, 27 reference components, 22 brand assets, and a React click-through of this feature. v1 is
retained as the visual-regression baseline.

**v2 reviewed 2026-09-28 — the foundation did not drift.** All 141 tokens, all 27 components, all 22
assets and all 21 guideline cards are byte-identical to v1, and every token the new screens reference
already exists. **Phase 3 (T057–T103) and the seven accessibility corrections are therefore
unaffected.** v2's changes are confined to screens, and five of them conflict with the specification —
see "Blocked by the v2 review" below and [ui-conflicts.md](./ui-conflicts.md) C-19 … C-23. It is the **single source of truth for visual design**; this spec remains the
source of truth for functionality. Fonts are **Cairo** (Arabic + UI) and **Inter** (Latin and
all numerals), self-hosted. Icons are self-hosted Lucide 0.460.0. Full contract in
[design-system.md](./design-system.md); every discrepancy found is registered in
[ui-conflicts.md](./ui-conflicts.md).

**Storage**: MySQL 8.0+ or MariaDB 10.11+, InnoDB, `utf8mb4` / `utf8mb4_unicode_ci`; Redis
for cache, sessions, and queues; private (non-public) filesystem disk for uploads

**Visual fidelity tooling**: Playwright for component- and screen-level visual regression against
the React reference (≤ 1% pixel difference at 1440×900, both locales, 1280/1440/1920/390),
axe-core for automated WCAG 2.1 AA checks, plus a token-parity test and a
no-hard-coded-visual-value architecture test. Rationale and thresholds in
[design-system.md](./design-system.md) §13.

**Testing**: Pest (feature, unit, and authorization suites), Larastan at max practical
level, Pint for formatting; GitHub Actions running all three per pull request

**Target Platform**: Ubuntu 24.04 LTS, Nginx + PHP-FPM 8.4 *(corrected 2026-10-09 from 8.3 — Pest 5 and the locked dependencies require 8.4; research R-07)*, Supervisor-managed queue
workers; hosted in the Kingdom of Saudi Arabia

**Project Type**: Server-rendered bilingual web application with an internal REST API

**Performance Goals**: First contentful render < 2 s p95; non-aggregate API < 500 ms p95;
audit log filtered over 1,000,000 rows < 2 s; 10,000-row import validated and committed
< 5 minutes; typed EAV spike meeting aggregation timings at 1,500,000 values

**Constraints**: No self-registration; no 2FA; PDPL compliance with KSA-region hosting;
Arabic must shape correctly in UI and in every export; soft delete only; audit entries
immutable at the database-grant level; 20 MB / 50,000-row import ceiling; 10 MB attachment
ceiling

**Scale/Scope**: Per term 500 staff, 3,000 sections, 30,000 students, 50 initiatives,
1,500,000 field values; architecture must absorb 10x without redesign; 50 concurrent users
typical, 200 at deadline peaks; this feature covers roughly 29 screens and **50 API endpoints** —
the 39 Phase 1 endpoints listed in PRD section 11.3, plus three the design adds (import resume, audit
log export, per-entity activity), plus eight from Amendment 1 (five for academic programs and three
upload endpoints for the registrar, course, and staff import modes), plus ten from Amendment 2 (five
unauthenticated support routes and five authenticated ones)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| # | Principle | How this plan satisfies it | Gate |
|---|---|---|---|
| I | Scoped Authorization On Every Query | Permissions seeded as named records and grouped into the three roles; a `BelongsToScope` trait applies an Eloquent **global** scope (not per-controller `where`) to `Section`, `Student`, `SectionEnrollment`, `ImportJob` and `AuditLog`, resolved from the authenticated user's coordinator branches, taught sections, and access grants. Route-model binding resolves through the scoped query, so an out-of-scope id raises `ModelNotFoundException` → 404 without a policy ever running. Missing permission → 403 plus an `access.denied` audit row. | PASS |
| II | Append-Only Audit In The Same Transaction | A single `Auditor` service is invoked from model observers and explicit service calls; it writes within whatever transaction is already open, and callers wrap audited actions in `DB::transaction()`, so a failed audit insert aborts the action. A deployment migration `REVOKE UPDATE, DELETE ON audit_logs` from the application user; no model, route, or command exposes update or delete. | PASS |
| III | Completion Is Derived, Never Entered | No completion logic is in scope for this feature. The spike seeds `student_initiative_values` only to measure capacity and exposes no screen, endpoint, or writable completion field. | PASS (not exercised) |
| IV | Configuration Over Code | Branches, terms, courses, sections are tables with no hard-coded campus, gender, or branch constant anywhere — enforced by a Pest test asserting the strings "Zulfi", "Majmaah", "Rumah" appear in no file under `app/`. Roles are permission collections created by seeder. The University ID pattern and upload ceilings live in `settings`, read through a `Settings` facade, never as literals. | PASS |
| V | Historical Integrity Is Preserved | `SoftDeletes` on `users`, `students`, `branches`, `academic_terms`; no `forceDelete` call anywhere (Larastan-banned). Instructor change writes `section_instructor_history` and never rewrites `created_by` / `updated_by` on existing rows. Withdrawal sets status, date, and reason. Closed-term write blocking is deferred (see Complexity Tracking). | PASS with documented deferral |
| VI | Bilingual And Accessible By Default | Two Vite entry points produce `app-rtl.css` and `app-ltr.css` from the design-system tokens plus the Bootstrap 5.3 RTL and LTR sources; a `SetLocale` middleware resolves the user's locale and sets `dir`/`lang` on `<html>`. Every string is a `__()` key present in both `lang/ar` and `lang/en`, verified by a Pest test that diffs the two key sets and fails on any asymmetry, plus a test that greps Blade files for bare user-facing literals. Status renders only through `<x-track-status-badge>`, which cannot omit its label. **The approved palette failed the AA floor in 7 pairings** (green as text and as a focus ring, amber as text and as a badge label, red as a badge label, gray-400 as text); all 7 are corrected by darkening within the same hue, with measured before/after ratios in [design-system.md](./design-system.md) §6 and the diff recorded in `design-system/ACCESSIBILITY-DELTA.md`. axe-core runs against every screen in both locales with zero AA violations required. | PASS **after** the §6 corrections |
| VII | Heavy Work Runs Off The Request | Import validation and commit, and the spike seeding, are queued jobs on Redis with Supervisor workers. Commit runs in 500-row `DB::transaction()` chunks writing a `last_committed_chunk` checkpoint. No aggregate table is needed by this feature. Each stated budget gets a Pest performance test placed in the `performance` group, which phpunit.xml excludes from the default run, so CI stays fast while the numbers are measured before acceptance. | PASS |
| VIII | Authorization Is Tested, Not Assumed | Each of the 60 routes gets an allow-case and a deny-case Pest test, and Amendment 2 adds a second mandatory class — the **enumeration suite**, asserting that distinguishable public responses do not leak. A dedicated `tests/Feature/Scope` suite proves cross-branch denial through API, list, export, and direct-URL paths. Unit tests cover duplicate detection at all four levels and the University ID pattern. A Pest architecture test fails the build if any route lacks an authorization middleware or policy. | PASS |

**Security floor and compliance** (constitution "Technology & Compliance Constraints"):
Argon2id hashing via `config/hashing.php`, session regeneration on login, HttpOnly + Secure
+ SameSite=Lax cookies, 60-minute `SESSION_LIFETIME`, `other_devices` logout on password
change, CSRF on all state-changing routes, a strict CSP through a `ContentSecurityPolicy`
middleware, Eloquent-only queries with no raw concatenation (Larastan-enforced), login
throttle at 5/min per email+IP and API throttle at 120/min per user, upload validation on
extension + MIME + magic bytes, private disk storage with authenticated download routes, and
`/register` absent so it 404s naturally. All satisfied by design; no waiver required.

**Initial gate result**: PASS. One documented deferral (closed-term write enforcement) is
tracked below; it originates in the PRD's own phase ordering, not in this plan.

## Project Structure

### Documentation (this feature)

```text
specs/001-foundation-access-roster/
├── plan.md              # This file
├── research.md          # Phase 0 output — resolved technical decisions
├── data-model.md        # Phase 1 output — tables, indexes, state machines
├── quickstart.md        # Phase 1 output — runnable validation guide
├── design-system.md     # UI integration contract — tokens, component map, screen map, states
├── ui-conflicts.md      # Every UI ↔ spec discrepancy, with status and decision owner
├── contracts/
│   ├── README.md        # Conventions: envelopes, errors, pagination, status codes
│   ├── auth-users.md    # P0-F001 / P0-F002 endpoints
│   ├── academic.md      # P0-F003 endpoints
│   ├── imports.md       # P0-F004 endpoints
│   ├── audit.md         # P0-F010 endpoints
│   ├── support-public.md # Amendment 2 — the 5 unauthenticated routes
│   └── support-staff.md  # Amendment 2 — the 5 authenticated support routes
├── checklists/
│   └── requirements.md  # Spec quality checklist (complete)
├── spike-eav-results.md # Written by the capacity spike (gated — see ui-conflicts.md C-08)
└── tasks.md             # Created by /speckit-tasks
```

### Vendored design system (repository root, read-only)

```text
design-system/                     # unpacked from "TRACK Design System.zip", never edited
├── tokens/                        # 141 CSS custom properties — canonical token source
├── components/                    # 27 React reference components + .d.ts + .prompt.md contracts
├── guidelines/                    # 21 foundation specimen cards
├── assets/                        # 22 brand assets (logos, app icons, imagery, stripes)
├── ui_kits/web-app/               # 17-route click-through of THIS feature — visual reference
├── ui_kits/mobile/                # 2 phone screens — v2 reference only, not built
├── uploads/                       # brand board original + real registrar roster export
├── readme.md                      # brand rules, content register, visual foundations
└── ACCESSIBILITY-DELTA.md         # written by us: the 7 AA corrections and their rationale
```

### Source Code (repository root)

```text
app/
├── Modules/
│   ├── Authentication/        # login, logout, password change, first-login flag
│   │   ├── Http/{Controllers,Requests,Middleware}
│   │   ├── Actions/
│   │   └── Tests/
│   ├── UserManagement/        # account CRUD, deactivate, reset, role & branch assignment
│   ├── Authorization/         # permissions, roles, scopes, policies, access grants
│   │   ├── Scopes/            # BranchScope, SectionScope
│   │   ├── Concerns/          # BelongsToBranchScope, BelongsToSectionScope
│   │   ├── Policies/
│   │   └── Seeders/           # PermissionSeeder, RoleSeeder
│   ├── Audit/                 # Auditor service, observers, audit log query & export
│   ├── AcademicTerms/         # term CRUD, lifecycle, current-term flag
│   ├── AcademicStructure/     # branches, courses, programs (Amendment 1)
│   ├── Sections/              # sections, instructor change + history, access grants; Amendment 4:
│   │                          #   CreateSection, DecideSectionApproval, ResubmitSection, SectionStatus
│   ├── Students/              # students, enrollments, withdrawal
│   ├── Imports/               # 6-step wizard, 5 modes, jobs, readers, validators, error report
│   │   ├── Jobs/              # ValidateImportJob, CommitImportChunkJob
│   │   ├── Pipeline/          # Reader, Mapper, RowValidator, Resolver, Committer
│   │   │                      # + MetadataBlockReader, ResolveBranchFromCampus (Amendment 1)
│   │   ├── Modes/             # central, section, registrar, course, user (Amendment 1)
│   │   └── Exports/           # ImportErrorReportExport
│   ├── Settings/              # Settings facade + seeded values (no admin UI this phase)
│   ├── Support/               # Amendment 2 — public form, status check, staff queue
│   │   ├── Http/Controllers/Public/  # the only unauthenticated controllers in the platform
│   │   ├── Services/          # VerifyStudentIdentity, ReferenceGenerator, Challenge/
│   │   ├── Actions/           # Submit, Respond, Transition, Reassign, AnonymiseExpiredRequests
│   │   └── Jobs/              # AnonymiseExpiredRequestsJob — the daily retention sweep (Phase 25)
│   └── Shared/                # base controller, API envelope, pagination, audit contract
├── Models/                    # Eloquent models (shared across modules)
└── Providers/                 # ModuleServiceProvider registering each module

database/
├── migrations/                # schema per data-model.md, in dependency order
├── seeders/                   # OrganizationSeeder (single college), permissions, roles,
│                              # settings, DemoDataSeeder, EavSpikeSeeder
└── factories/

resources/
├── css/
│   ├── tokens/                     # copied from design-system/tokens/ (+ the 7 AA corrections)
│   │   ├── fonts.css               # rewritten to @font-face — self-hosted, no Google Fonts
│   │   ├── colors.css  typography.css  spacing.css  base.css
│   ├── _bootstrap-bridge.scss      # maps tokens onto Bootstrap Sass variables
│   ├── _shell.scss                 # sidebar 240px, topbar 64px, content, drawer <768px
│   ├── _components.scss            # the 24 component styles, tokens only
│   ├── app-rtl.scss                # entry: tokens + bridge + bootstrap.rtl + shell + components
│   └── app-ltr.scss                # entry: same with the LTR Bootstrap build
├── js/
│   ├── app.js                      # Alpine registration
│   └── components/                 # dialog (focus trap), toast, tabs, data-table,
│                                   # import-wizard + 5s poller, locale-switch, drawer, tooltip
├── views/
│   ├── layouts/app.blade.php       # direction-aware shell: sidebar + topbar + content
│   ├── components/                 # 24 Blade components, x-track-* (design-system.md §5)
│   │   ├── button  icon-button  icon  logo
│   │   ├── input  select  checkbox  switch
│   │   ├── status-badge  alert  toast  dialog  progress  skeleton  empty-state  tooltip
│   │   ├── card  stat-card  data-table
│   │   └── sidebar  topbar  tabs  breadcrumbs  stepper
│   ├── auth/  profile/  home/      # US1 + US7
│   ├── users/                      # US1
│   ├── terms/  branches/  courses/ # US4
│   ├── sections/  students/        # US4 (+ US6 section import entry)
│   ├── imports/                    # US5 six-step wizard
│   ├── audit/                      # US3
│   ├── support/public/  support/staff/   # Amendment 2 — 8 screens
│   └── errors/403.blade.php  404.blade.php
lang/
├── ar/                             # Arabic strings — primary locale
└── en/                             # English strings — equal locale, key parity enforced

public/
├── fonts/                          # self-hosted Cairo + Inter woff2
├── icons/lucide.svg                # self-hosted Lucide 0.460.0 sprite
└── img/brand/                      # build-copied from design-system/assets/

tests/Visual/                       # Playwright: component + screen regression, axe-core
└── baselines/                      # per component, per screen, per locale, per width

routes/
├── web.php                    # Blade screens
├── api_v1.php                 # /api/v1 endpoints
└── public.php                 # Amendment 2 — the entire public surface: 5 routes, no auth

tests/
├── Unit/                      # completion-free logic: validators, duplicate detection
├── Feature/                   # endpoint behaviour per module
│   ├── Scope/                 # cross-branch / cross-section denial matrix
│   └── Authorization/         # allow+deny per endpoint
├── Architecture/              # no-hard-coded-branches, every-route-authorized, i18n parity
└── Performance/               # budget tests, run under --group=performance
```

**Structure Decision**: Single Laravel application with domain modules under `app/Modules`,
as mandated by the constitution's technology section and PRD section 9.2. Blade is
server-rendered from the same codebase, so no separate frontend project exists; the
`/api/v1` layer is internal-facing but versioned from day one so a v2 mobile client needs no
backend rewrite. Eloquent models live in a shared `app/Models` rather than inside each
module because sections, students, and enrollments are read across nearly every module, and
duplicating or cross-importing them per module would create exactly the circular
module dependencies the layout exists to prevent.

**Frontend structure decision**: the design system's 27 React components are **re-authored as 24
Blade components** (`x-track-*`) with Alpine behaviour, not ported and not wrapped. Three chart
components are deferred to Phase 3, which needs them; no Phase 1 screen uses a chart. Views live
under `resources/views/<area>/` rather than inside `app/Modules/*/`, because a single screen
routinely composes data from several modules — the section detail page alone reads sections,
students, enrollments, instructor history, and audit — and module-local views would force either
duplication or cross-module includes. The component library is deliberately flat and global: it is
the design system, which belongs to no single module.

## UI Design System Integration

The approved design system (`TRACK Design System.zip`, 174 files) is vendored read-only at
`design-system/` and governs all visual decisions. The full contract is
[design-system.md](./design-system.md); this section records only the decisions that shape the
plan.

### Authority split

| Domain | Source of truth |
|---|---|
| Colour, type, spacing, radii, shadows, motion, layout geometry, component anatomy, iconography, imagery, Arabic copy register | **design system** |
| Entities, permissions, scoping, endpoints, validation, workflow states, audit obligations, retention | **spec.md, data-model.md, contracts/** |

Anything the UI implies that the spec does not carry is **not** silently adopted. Every such case
is registered in [ui-conflicts.md](./ui-conflicts.md) with a named decision owner.

### Six decisions taken

1. **React is reference, Blade is production.** The kit ships React 18 + Babel-in-browser +
   inline style objects from `unpkg.com`. None of it reaches production. The design system's own
   readme names Blade + Alpine + Bootstrap 5.3 as the target, so this is agreement, not override.
2. **Tokens define; Bootstrap consumes.** The kit uses no Bootstrap, and Bootstrap's defaults
   would fight 141 tokens. Bootstrap is kept for the grid, utilities, and RTL/LTR build machinery
   — what the constitution needs it for — and its Sass variables are overridden from the tokens
   through `_bootstrap-bridge.scss`. No Bootstrap default may override a token.
3. **Cairo replaces IBM Plex Sans Arabic.** The brand board specifies Cairo; PRD §9.1 said IBM
   Plex Sans Arabic. Typography is visual, so the design system wins. **PRD §9.1 is now stale on
   this point** — flagged for the owner in [ui-conflicts.md](./ui-conflicts.md) C-03 rather than
   edited, since the PRD is the owner's document.
4. **Fonts and icons are self-hosted.** The kit loads Cairo and Inter from Google Fonts and
   Lucide from a CDN. Production vendors all three, because the constitution mandates a strict CSP
   and PDPL constrains third-party origins on every page load.
5. **Seven accessibility corrections to the approved palette.** Measuring the tokens against the
   AA floor found 7 failing pairings — green as text (2.54:1) and as a focus indicator, amber as
   text (3.19:1) and as a badge label (2.86:1), red as a badge label (3.95:1), gray-400 as text.
   Each is corrected by darkening within the same hue, so no hue, spacing, radius, or layout value
   moves and `#10B981` remains the brand green for every non-text use. This is the one place the
   UI is overridden, and only because constitution VI makes AA a floor and SC-014 gates on it.
   Before/after ratios in [design-system.md](./design-system.md) §6; the diff is written to
   `design-system/ACCESSIBILITY-DELTA.md` for the design author's review.
6. **Out-of-phase screens are specified now, built in their phase.** The kit covers a 13-panel
   Phase 3 dashboard and the full Phase 2 initiative flow. Their contracts are captured in
   [design-system.md](./design-system.md) §9 so the later phases inherit them, and their sidebar
   entries are stubbed so the shell matches the approved navigation without dead links. Building
   them now would violate the constitution's scope-discipline rule.

### Design system v2 review (2026-09-28) — decided

v2 contained the **authenticated student portal that was explicitly rejected on 2026-09-27**, plus three
further features. On **2026-09-28 the Product Owner confirmed option A: no student authentication in this
release.** The specification stands.

| Item | Outcome |
|---|---|
| **C-19** student authentication · **C-20** student-visible initiative data · **C-21** support as authenticated tickets | **Not in this release.** The public support form stands, the constitution stays at **v1.1.0** with no MUST reversed, FR-114 … FR-169 are unchanged, and Phases 18–25 proceed exactly as written. The fourteen tasks staged for this work are **withdrawn** |
| **C-22** instructor-requested sections | **Still open** — unrelated to authentication. Eight tasks staged as `T-A01`–`T-A08` |
| **C-23** student contact columns | **Still open.** Its motivation shifted: no longer a login credential, but a possible reply channel for support requests, which is the one item spec Assumptions still marks for confirmation. Three tasks staged as `T-C01`–`T-C03` |

What the decision preserved: no constitution amendment, no requirement rewritten, no coverage map
invalidated, and the impersonation risk in spec "Accepted Risks" stays bounded — a public form exposes no
data, whereas v2's design would have put a guessable default credential in front of personal data.

**For the design author**: v2's `ForcePasswordScreen`, `StudentHome`, `StageTrack` and authenticated
`SupportScreens` are not approved for this release and stay in `design-system/` as future reference. What
is needed is the five public screens and three staff screens in
[CR-002 §12](../changes/CR-002-student-support.md).

### Formerly blocked by the v2 review — now resolved or still open

v2 contains the **authenticated student portal that was explicitly rejected on 2026-09-27**, plus three
further features. Under the authority split above — the UI decides visual, the specification decides
functional — all five are functional, so the specification governs and none may be built pending a
Product Owner decision. Full detail: [ui-conflicts.md](./ui-conflicts.md).

| Item | What v2 adds | Why it is blocked |
|---|---|---|
| **C-19** student authentication | `students` gain email, phone, password, `mustChange`, `role:'STUDENT'`; a tabbed sign-in; **initial password = the university ID** | Constitution v1.1.0 states "A student never authenticates"; FR-124 forbids any student credential. And the default credential is the very value that is guessable — nine sequential digits — now guarding personal data |
| **C-20** student-visible initiative data | `StudentHome` shows initiatives with stage percentages, sections, and instructor names | The support channel's scope explicitly excludes displaying initiative data, enrollments, or completion |
| **C-21** support as authenticated tickets | Ticket system with **sequential** `TK-1001` references and no `rejected` state | Replaces the public form; the sequential reference violates FR-122 and SC-027 regardless of the authentication decision |
| **C-22** instructor-requested sections | Instructors request sections; coordinators approve, return, or reject | FR-040/FR-041 give section creation to the Super Admin, and this is a second approval workflow overlapping P0-F008, which is Phase 2 |
| **C-23** `students.email`, `students.phone` | Derived email and generated phone per student | T105 asserts `students` carries no credential or contact column, per the constitution's PDPL minimisation rule |

**C-19, C-20 and C-21 are one decision** — does a student have an account? C-22 and C-23 are separable.

**Adopted from v2 without a decision**: the `programs` icon correction (`library`), four new `nav_*`
translation keys, `StudentsAdminScreen` as an extension of the existing students screens, and two further
real registrar exports as import fixtures.


### Fidelity is measured, not asserted

"Pixel-perfect wherever technically feasible" becomes five gates, detailed in
[design-system.md](./design-system.md) §13: token parity against the vendored source; component
visual regression at ≤ 1% pixel difference; screen visual regression in both locales at 1280 /
1440 / 1920 / 390; a no-hard-coded-visual-value architecture test; and axe-core with zero AA
violations. A deliberate difference is recorded with its reason; an unexplained one is a defect.

### Functional additions accepted from the UI (Product Owner, 2026-09-27)

Five items could not be resolved by the authority split, because the UI implied **new
functionality**. All five were decided on 2026-09-27 and are now specified. Full record in
[ui-conflicts.md](./ui-conflicts.md); requirements in [spec.md](./spec.md) FR-089 … FR-113 and
User Story 8.

| Item | Decision | Effect on this plan |
|---|---|---|
| **C-04 programs** | **Accepted into this feature** | New `programs` table + `course_program` pivot + **required** `sections.program_id`, created non-nullable from the start so no backfill is ever needed; 3 permissions, 5 endpoints, CRUD screen, 4 audit keys, program filter on sections |
| **C-09 registrar file format** | **Accepted as import mode 3** | Reads the metadata block above the header row, resolves branch from the campus string, matches course by Arabic name, takes term and instructor from the operator. FR-052's ten-column central mode is retained for files that do arrive in it |
| **C-05 course + staff import** | **Accepted as import modes 4 and 5** | New **User Story 8** (P8, last — pure convenience, the release is viable without it). Reuses the whole existing pipeline |
| **C-10 extra fields** | **All five accepted** | `sections.degree`, `sections.activity` (both varchar validated against settings, not DB enums, so adding a value needs no migration); `section_enrollments.academic_status` and `roster_sequence`; `users.last_login_at` |
| **C-08 initiative model** | **PRD's 15 dynamic field types stand** | The EAV capacity spike is **ungated** and runs as specified. Ordered stages become one preset of Yes/No fields |

**Two consequences worth stating plainly:**

1. **`status` and `academic_status` are deliberately two columns**, not one. `status` is TRACK's own
   enrollment state, set with a mandatory reason and a full audit trail (FR-047). `academic_status` is
   the registrar's standing, arriving in a file. FR-111 forbids either from overwriting the other,
   because collapsing them would mean an import silently erasing a withdrawal and its reason.

   *Implemented (Phase 15, 2026-10-10):* a registrar re-import writes only those two columns, through
   the query builder and only when they change. The model's audit trait is bypassed on purpose —
   `enrollment.updated` is not a contract key — and the trail is the import itself:
   `import.committed` (with the block and any degree/activity notices), plus each import row's
   `resolved.previous_*` values. The registrar's course and programme choices are made at the
   confirmation (mapping) step rather than at preview, because commit refuses `options`;
   contracts/imports.md records both.
2. **Keeping dynamic fields creates a Phase 2 UI gap.** The design system has no field editor — no
   type picker, no per-type options editor, no flag matrix, no live grid preview, no completion-impact
   warning. Its `StagesEditor` covers a small fraction. This must be commissioned from the design
   author before Phase 2 planning; logged as [ui-conflicts.md](./ui-conflicts.md) C-18.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| Closed-term write enforcement (constitution V) is deferred to the later term-closure feature | PRD section 10 places term closure in Phase 4. Building the enforcement now means building the `term.edit_closed` permission path, the 423 responses across every academic write endpoint, and the read-only banner set — for a state that cannot legitimately occur until a term ends, months after this feature ships | Enforcing it now was rejected only on sequencing, not on principle: it would front-load Phase 4 work into an 8-week Phase 1 with no user able to reach the state. Mitigation: the `status` column and its transitions ship here with the `closed` and `archived` values, a Pest test asserts the enum accepts them, and no seeder or screen sets a term to `closed` in this feature. Accepted risk if a term were closed early: its data would remain writable. Owner confirmation requested in `checklists/requirements.md` |
| Typed EAV tables (`initiative_schema_versions`, `initiative_fields`, `student_initiative_values` and their parents) are created in this feature although initiatives are Phase 2 | Constitution VII names the typed EAV model a known scaling risk, and PRD risk R-03 requires the 1.5M-value spike in Phase 1. A capacity measurement is impossible without the real schema and real indexes | Measuring against a throwaway table was rejected: index composition and column types are exactly what is under test, so a stand-in would prove nothing. Containment: these tables get migrations, factories, and the spike seeder only — no model beyond what the seeder needs, no route, no controller, no screen. A Pest architecture test asserts no route and no Blade view references them in this feature. *Spike result 2026-10-09: PASS on SQLite, provisional pending the MySQL run — [spike-eav-results.md](./spike-eav-results.md); neither measurement index ships unless that run needs one.* |
| The approved design palette is modified in 7 places (constitution VI's visual authority ceded to the design system, except here) | Constitution VI makes WCAG 2.1 AA a floor and SC-014 gates acceptance on it. Seven token pairings measure below AA — `#10B981` at 2.54:1 as text and as a focus indicator, `#D97706` at 3.19:1 as text and 2.86:1 as a badge label, `#DC2626` at 3.95:1 as a badge label, `#9CA3AF` at 2.54:1 as text. Shipping them would fail a stated acceptance gate and, for an institutional platform under PDPL, an accessibility obligation | Shipping the palette unmodified was rejected because AA is a floor, not a preference. Reworking the palette wholesale was also rejected as disproportionate: each correction darkens within the same hue, the brand reads identically, no hue/spacing/radius/layout value moves, and `#10B981` stays the brand green for all non-text use. Containment: exactly 7 values change, each with a measured before/after ratio, recorded in `design-system/ACCESSIBILITY-DELTA.md` and walked through with the design author in a dedicated task |
| The React reference kit is re-authored rather than reused, duplicating 24 component implementations | The kit is React 18 + Babel-in-browser + CDN dependencies + inline style objects; the constitution mandates Blade + Alpine + Bootstrap, server-rendered, under a strict CSP. There is no path by which the kit's code runs in production | Shipping the React kit was rejected outright — it would mean a second frontend stack, a CSP exemption for `unpkg.com`, in-browser transpilation on every page load, and no server-side rendering. Wrapping React components inside Blade was rejected for the same reasons plus hydration complexity for a form-heavy internal tool. The duplication is therefore intentional and one-directional: the kit stays as the visual baseline the regression suite compares against, which converts it from dead weight into the fidelity gate |

| The student support channel (Phases 18–25, 110 tasks) sits inside a feature whose PRD window is 2026-09-27 → 2026-10-08 | The Product Owner directed on 2026-09-27 that the briefly separate `002-student-support` be merged into this feature rather than scheduled after it. The support channel is not in the PRD's MoSCoW table at all — it is v1.1 scope — so the constitution's scope-discipline rule ("Adding scope mid-phase requires an explicit trade recorded in the phase review") applies | Keeping it as a separate feature was recommended and declined. The stated risk: 110 optional tasks now compete for the same two-week window as authentication, RBAC, audit, academic structure, and import — the work without which nothing functions — and the PRD's Phase 1 acceptance gate has four checks, none about students. Mitigation, which is why this is tracked rather than blocking: Phases 18–25 are **strictly additive and last**, depend on Phases 1–17 rather than the reverse, touch exactly **one** pre-existing file (T463, behind a feature flag defaulting to false), and can therefore be deferred wholesale at any point without unpicking anything. The explicit trade is recorded in PRD §10 |


## Post-Design Constitution Re-Check

Re-evaluated after producing `research.md`, `data-model.md`, `contracts/`, and
`quickstart.md`:

- **I, VIII**: The contracts fix 404-for-out-of-scope and 403-for-missing-permission per
  endpoint, and `contracts/README.md` makes the allow/deny test pair a contract requirement
  rather than a convention. No endpoint in the four contract files lacks a stated required
  permission. Still PASS.
- **II**: `data-model.md` states the `audit_logs` grant revocation as a migration step and
  gives the table no `updated_at`, so an ORM-level update has nowhere to land. Still PASS.
- **IV**: `data-model.md` carries no branch, campus, or gender literal; gender is an enum on
  `branches` with values `male`/`female` and is derived for sections through the branch
  relation, never stored on `sections`. Still PASS.
- **V**: Soft-delete columns are present on all four required tables, and no contract exposes
  a hard-delete verb. Still PASS.
- **VI**: `quickstart.md` makes bilingual verification and the language-key parity test part
  of the acceptance run, not an afterthought. Still PASS.
- **VII**: `research.md` fixes the chunk-checkpoint design and the spike's measurement
  method and thresholds, so each budget has a defined way to be proven. Still PASS.
- **III**: Unchanged — not exercised by this feature.

**Post-design gate result**: PASS, with the same two entries in Complexity Tracking and no
new violations introduced by the design.

### Re-check after design-system integration (2026-09-27)

Re-evaluated after producing `design-system.md` and `ui-conflicts.md` and amending this plan:

- **IV (Configuration Over Code)**: strengthened. The no-hard-coded-visual-value test now bans hex
  colours, px radii, px shadows, and transition durations outside `resources/css/tokens/`, which
  extends the principle from academic structure to the visual layer. Still PASS.
- **VI (Bilingual And Accessible)**: now PASS **conditional on** the seven §6 corrections being
  applied. Without them the feature fails its own SC-014 gate. The condition is discharged by a
  task that runs before any screen work, so no screen can be built on a failing palette.
  `x-track-status-badge` cannot render without a label, which makes FR-077 structural rather than
  reviewed.
- **VII (Heavy Work Off The Request)**: unchanged. The EAV spike proceeds as specified following the
  C-08 decision. The three new import modes run through the same queued, 500-row chunked, resumable
  pipeline, so they inherit the principle rather than needing their own treatment.
- **Scope discipline (Development Workflow)**: the kit's Phase 2 and Phase 3 screens remain specified
  but not built. Amendment 1 grows this feature's scope by ~70 tasks, which was the Product Owner's
  explicit decision against the stated cost, not scope creep. Still PASS.
- **VIII**: extended — visual regression and axe-core join the authorization suite as build gates,
  so fidelity and accessibility are proven the same way access control is.

**Integration gate result**: PASS, with three entries in Complexity Tracking.

### Re-check after Amendment 2 (2026-09-27) — student support channel

The support channel was merged in from the briefly separate feature `002-student-support`. It is the
first part of the platform to expose an **unauthenticated** surface, so it is checked against
constitution v1.1.0's "Public endpoints" subsection as well as the eight principles.

- **Public endpoints (v1.1.0)**: all seven rules carried into FR-124 … FR-131 and
  [contracts/support-public.md](./contracts/support-public.md) — no account/credential/session; roster
  **and** active-enrollment verification; rate limiting on **two independent axes**; the
  person-versus-script challenge, which **fails closed**; no enumeration in body, status, header, or
  timing; the IP recorded; no record of any other person exposed. `routes/public.php` holds all five
  public routes and an architecture test fails the build on a sixth. **PASS**.
- **I (Scoped Authorization)**: `SupportRequest` carries the **existing** `BelongsToBranchScope`
  resolved through the student's active enrollments — no new scope type. The public routes carry no
  scope because they expose no record. An anonymised request has no student, therefore no branch, and
  becomes Super Admin-only; that is a designed consequence of the nullable student link, recorded in
  [data-model.md](./data-model.md) Group 7. **PASS**.
- **II (Audit in the same transaction)**: reuses the `Auditor` unchanged. A publicly created request is
  audited with a **null actor** plus `channel: public` and the IP — the `user_id` column is already
  nullable, so the entry is honest about having no authenticated author rather than inventing a system
  user. **PASS**.
- **I (Branch scope), Phase 23**: the support queue's branch column and filter are shown to the Super
  Admin and to coordinators of several branches (narrowing within their own), hidden for a
  single-branch coordinator — a widening of T442's "Super Admin-only", recorded in
  contracts/support-staff.md. **PASS**.
- **IV (Configuration over code)**: nine new settings keys — categories, the description limit, three
  throttle thresholds, the timing floor, the retention period, and (Phases 19–20) the challenge
  difficulty, the `public` group's flood floor and the form's minimum elapsed time. `status` stays a database enum
  because it is a workflow contract the audit log depends on; `category` does not, because it is a
  label the Super Admin may extend. **PASS**.
- **V (Historical integrity)**: retention **anonymises** rather than deletes, clearing exactly three
  columns while the request, its state history, and all responses survive. Responses are append-only —
  no `updated_at`, no update path. **PASS**.
- **VI (Bilingual and accessible)**: the public pages offer the language choice **without a sign-in**,
  since a visitor has no stored preference. **PASS**.
- **VII (Heavy work off the request)**: nothing in the request path is heavy; the retention sweep is a
  scheduled queued job. **PASS**.
- **VIII**: ten new routes, so ten new allow/deny pairs, plus the **enumeration suite** — the class of
  test this amendment introduces and the one most likely to be implemented wrongly.

**Amendment 2 gate result**: PASS on every principle. **One entry added to Complexity Tracking** — see
the schedule trade below, which is a scope decision rather than a principle violation.

### Re-check after Amendment 1 (2026-09-27)

- **I (Scoped Authorization)**: `programs` is institution-wide reference data with no branch
  dimension, so it takes **no** scope trait — a coordinator may read programs but sees only their own
  branches' sections within them. Deliberate and recorded, not an omission. The three new import
  upload endpoints are Super Admin only and their jobs remain scoped through `ImportJob`. Still PASS.
- **IV (Configuration Over Code)**: strengthened. `degree` and `activity` are `varchar` validated
  against the `section_degrees` and `section_activities` settings rather than database enums, so
  adding a degree or an activity needs no migration. A DB enum would have violated the principle.
- **V (Historical Integrity)**: `programs` gets `SoftDeletes`, a restrict foreign key from
  `sections`, no `DELETE` route, and deactivation as its only removal path. `academic_status` and
  `status` are kept separate precisely so an import cannot erase a TRACK withdrawal or its reason,
  which would have broken FR-047's audit trail. Still PASS.
- **VI (Bilingual)**: the two new enum-like value sets carry AR/EN labels in the settings payload
  rather than in code, so both locales stay first-class. Still PASS.
- **VIII**: 8 new endpoints, so 8 new allow/deny test pairs; `programs` additionally needs the
  "cannot delete a program with sections" test and the registrar mode needs its ambiguous-campus and
  ambiguous-course cases. Still PASS.

**Amendment 1 gate result**: PASS. No new Complexity Tracking entry — `programs` is ordinary
academic-structure CRUD, and the three import modes reuse the existing pipeline rather than adding a
parallel one.
