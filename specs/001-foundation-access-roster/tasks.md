---

description: "Task list for Foundation — Access Control & Academic Roster (design-system integrated)"
---

# Tasks: Foundation — Access Control & Academic Roster

**Input**: Design documents from `/specs/001-foundation-access-roster/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md), [design-system.md](./design-system.md), [ui-conflicts.md](./ui-conflicts.md)

**Revision 2 · 2026-09-27** — renumbered to integrate the approved TRACK Design System in correct
execution order. Nothing had been implemented against revision 1, so renumbering costs no work.
215 → 285 tasks.

**Revision 7 · 2026-09-28** — the Product Owner decided **C-22**: an instructor, a coordinator, and a
Super Admin may each create a section, but only the Super Admin's is active immediately; the other two
require approval, and nobody approves their own. Became **Phase 27** (T470–T500), adding FR-170 … FR-185
and SC-039 … SC-043. 469 → **500 executable tasks**; blocked 11 → **3** (C-23 only).

**Revision 6 · 2026-09-28** — the Product Owner decided **against student authentication in this
release**. C-19, C-20 and C-21 are closed: the public support form stands, the constitution stays at
v1.1.0, FR-114 … FR-169 are unchanged, and Phases 18–25 proceed as written. The fourteen `T-S` tasks are
**withdrawn**. Blocked count 25 → **11** (C-22's eight and C-23's three).

**Revision 5 · 2026-09-28** — `TRACK Design System_V2.zip` reviewed. **The foundation did not drift**:
141 tokens, 27 components, 22 assets, 21 guideline cards all byte-identical to v1, so Phase 3 and the
seven accessibility corrections are untouched. Adopted changes became **Phase 26** (T464–T469). Five v2
screen features **conflict with the specification** and are staged as blocked backlogs — `T-S01`…`T-S14`
(student authentication and portal), `T-A01`…`T-A08` (instructor-requested sections), `T-C01`…`T-C03`
(student contact columns). 463 → **469 executable tasks, plus 25 blocked**. See
[ui-conflicts.md](./ui-conflicts.md) C-19 … C-23.

**Revision 4 · 2026-09-27** — the student technical support channel was **merged in** from what was
briefly a separate feature, `002-student-support`, at the Product Owner's direction. It became
**Phases 18–25** (T354–T463), adding FR-114 … FR-169, User Stories 9–13, and SC-025 … SC-038 to
[spec.md](./spec.md). 353 → **463 tasks**. The Product Owner was advised that this places optional
scope inside the 2026-09-27 → 2026-10-08 Phase 1 window and chose the merge; the explicit trade the
constitution requires is recorded in PRD §10.

**Revision 3 · 2026-09-27** — the Product Owner accepted all five previously blocked UI additions
(academic programs, the registrar import mode, course and staff bulk import, five stored attributes)
and confirmed the PRD's dynamic-field model stands. The EAV spike is **ungated**, the four blocked
backlogs became **Phases 13–17**, and a new **User Story 8** was added.
285 → **353 tasks, none blocked**. Requirements: [spec.md](./spec.md) FR-089 … FR-113; decisions:
[ui-conflicts.md](./ui-conflicts.md).

**Tests**: Test tasks are **included and mandatory**. Constitution principle VIII requires an
allow-case and a deny-case authorization test for every endpoint and an out-of-scope 404 test for
every scoped model-bound route. Visual regression and axe-core join them as build gates
([design-system.md](./design-system.md) §13).

**Organization**: Tasks are grouped by the seven user stories in spec.md, preceded by three
foundational phases. Ordering follows the PRD build order — authentication → RBAC/scoping → audit
→ academic structure → import wizard — with the design system standing up before any screen.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete work)
- **[Story]**: US1–US7
- Exact file paths in every task

## ID conventions

`T001`–`T500`, all executable. **3 tasks are blocked** as of revision 7, carrying non-numeric ids so they
cannot be mistaken for executable work: `T-C01`–`T-C03` (student contact columns, C-23). **Do not start any `T-` task** — each needs a Product
Owner decision first.

The fourteen student-authentication tasks revision 5 staged were **withdrawn** (C-19 to C-21 decided
against), and the eight section-approval tasks it staged became **Phase 27** (C-22 decided for).

Phases 1–12 run in the order given. **Phases 13–17 interleave** with earlier phases rather than
following them — each states its insertion point, and the merged order is in "Dependencies &
Execution Order" below. The numbering is sequential-by-document, not sequential-by-execution, because
renumbering a third time would invalidate the cross-references now present in
[plan.md](./plan.md), [quickstart.md](./quickstart.md), and the contracts.

## Path Conventions

Single Laravel application, domain modules under `app/Modules`, shared Eloquent models in
`app/Models`, views under `resources/views/<area>/`, the design system vendored read-only at
`design-system/`. See [plan.md](./plan.md) "Project Structure".

**Three deliberate placements**, each required so earlier stories stay independently testable:

- The **audit write mechanism** is in Phase 2, because US1's account creation must already be
  audited. US3 then delivers audit *viewing*, filtering, export, activity tabs, and the
  immutability and transaction tests.
- **All migrations** are in Phase 2 in data-model.md's dependency order, because a partial schema
  cannot migrate cleanly and later stories share tables.
- The **whole design system** is Phase 3, before any screen exists. A screen built on an
  uncorrected palette or missing components would have to be rebuilt.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization, tooling, design-system vendoring, and the CI pipeline

- [X] T001 Initialize the Laravel application at the repository root with `composer create-project laravel/laravel .`, pinning the latest stable major supported on PHP 8.3, and record the pinned version and PHP version in `README.md` per [research.md](./research.md) R-07.1
- [X] T002 Configure `.env.example` with `DB_*`, `REDIS_*`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`, `SESSION_LIFETIME=60`, `FILESYSTEM_DISK=private`, `APP_LOCALE=ar`, and add the `private` disk (outside the public root) to `config/filesystems.php`
- [X] T003 Set Argon2id as the hashing driver in `config/hashing.php`, and set the database connection in `config/database.php` to `utf8mb4` / `utf8mb4_unicode_ci` with the `InnoDB` engine
- [X] T004 Unpack `TRACK Design System.zip` to `design-system/` at the repository root as read-only reference, add it to version control, and add a `design-system/VENDORED.md` noting the source zip, its 174-file inventory, and the rule that nothing in the folder is ever edited
- [X] T005 [P] Install and configure Pint in `pint.json` using the Laravel preset
- [X] T006 [P] Install Larastan and configure `phpstan.neon` at the highest level that passes on a bare skeleton, with `forceDelete` and raw-query helpers (`DB::raw`, `whereRaw`, `selectRaw`) added to the banned-call list per constitution principle V and the security floor
- [X] T007 [P] Install Pest and register the `Unit`, `Feature`, `Architecture`, `Scope`, `Authorization`, and `Performance` testsuites in `phpunit.xml`, with the `performance` group excluded from the default run
- [X] T008 [P] Install `laravel/sanctum`, `spatie/laravel-permission`, `maatwebsite/excel`, and `openspout/openspout` and publish their configuration
- [X] T009 [P] Install Playwright and axe-core, and configure `playwright.config.ts` with projects for `ar`/RTL and `en`/LTR at viewport widths 1280, 1440, 1920, and 390, screenshot baselines under `tests/Visual/baselines/`
- [X] T010 Create the module directory skeleton under `app/Modules/` for `Authentication`, `UserManagement`, `Authorization`, `Audit`, `AcademicTerms`, `AcademicStructure`, `Sections`, `Students`, `Imports`, `Settings`, and `Shared`, each with `Http/Controllers`, `Http/Requests`, `Actions`, and `Tests` subdirectories
- [X] T011 Create `app/Providers/ModuleServiceProvider.php` registering each module's routes, views, and translations, and register it in `bootstrap/providers.php`
- [X] T012 Create `routes/api_v1.php` with the `/api/v1` prefix on the `web` + `auth:sanctum` middleware stack and a `throttle:120,1` per-user limiter, and register it in `bootstrap/app.php`
- [X] T013 Configure Vite in `vite.config.js` with the entry points `resources/css/app-rtl.scss`, `resources/css/app-ltr.scss`, and `resources/js/app.js`, install Bootstrap 5.3, Alpine.js 3.x, and `sass`, and add the build step that copies `design-system/assets/` to `public/img/brand/`
- [X] T014 [P] Create the GitHub Actions pipeline at `.github/workflows/ci.yml` running Pint (`--test`), Larastan, and Pest against MySQL 8 and Redis service containers on every pull request, with a failing step blocking merge
- [X] T015 [P] Add a second GitHub Actions job to `.github/workflows/ci.yml` running the Playwright visual-regression and axe-core suites, uploading diff images as artifacts on failure
- [X] T016 [P] Add `.gitattributes` marking `tests/Visual/baselines/**` as binary and `design-system/**` as vendored so diffs stay readable

---

## Phase 2: Foundational A — Schema, Access & Audit (Blocking)

**Purpose**: Database schema, seeded permissions, and the audit write mechanism

**⚠️ CRITICAL**: No user story work can begin until Phases 2 and 3 are both complete

### Migrations — in the dependency order from data-model.md

- [ ] T017 Create the `organizations` migration in `database/migrations/` with `type` enum(`university`,`college`), `name_ar` and `name_en` varchar(255), and a nullable self-referencing `parent_id`
- [ ] T018 Create the `users` migration adding `organization_id`, `name_ar` varchar(255), `name_en` varchar(255), `email` varchar(255) **unique on `email` alone so a soft-deleted account's email stays taken**, `password` varchar(255), `locale` char(2) default `ar`, `is_active` boolean default true, `first_login_at` timestamp nullable, and `deleted_at`
- [ ] T019 Publish and run the `spatie/laravel-permission` migrations for `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, and `model_has_permissions`, unmodified
- [ ] T020 [P] Create the `branches` migration with `organization_id`, `campus_name_ar` and `campus_name_en` varchar(255), `gender` enum(`male`,`female`), `is_active` boolean default true, and `deleted_at`
- [ ] T021 [P] Create the `academic_terms` migration with `name_ar`, `name_en`, `academic_year` varchar(9), `semester` enum(`first`,`second`,`summer`), `start_date` and `end_date` dates, `status` enum(`draft`,`active`,`closed`,`archived`) default `draft`, `is_current` boolean default false, and `deleted_at`
- [ ] T022 [P] Create the `courses` migration with `organization_id`, `code` varchar(20), `name_ar`, `name_en`, and **unique `(organization_id, code)`**
- [ ] T023 Create the `sections` migration with `term_id`, `branch_id`, `course_id` (all restrict-on-delete), `section_number` varchar(20), nullable `instructor_id`, **unique `(term_id, branch_id, course_id, section_number)`**, and secondary indexes `(instructor_id)` and `(branch_id, term_id)`. No `gender` column — it is derived through `branch` (FR-050)
- [ ] T024 [P] Create the `coordinator_branches` migration with `user_id` and `branch_id` cascade foreign keys and **unique `(user_id, branch_id)`**
- [ ] T025 [P] Create the `section_access_grants` migration with `section_id` and `user_id` cascade, `granted_by`, `granted_at`, and **unique `(section_id, user_id)`**
- [ ] T026 [P] Create the `section_instructor_history` migration with `section_id` cascade, `instructor_id`, `assigned_at`, nullable `unassigned_at`, and `changed_by`
- [ ] T027 [P] Create the `students` migration with `university_id` varchar(20) **unique**, `name_ar` varchar(255), nullable `name_en` varchar(255), and `deleted_at`
- [ ] T028 Create the `section_enrollments` migration with `student_id` and `section_id` restrict, `status` enum(`active`,`withdrawn`) default `active`, `enrolled_at`, nullable `withdrawn_at`, nullable `withdraw_reason` varchar(500), **unique `(student_id, section_id)`**, and index `(section_id, status)`
- [ ] T029 Create the `audit_logs` migration with nullable `user_id`, `action` varchar(100), nullable `entity_type` varchar(100), nullable `entity_id`, `old_values` json nullable, `new_values` json nullable, `ip_address` varchar(45), nullable `user_agent` varchar(500), `created_at` and **no `updated_at` column**, plus indexes `(user_id, created_at)`, `(entity_type, entity_id)`, `(action, created_at)`, and `(created_at)`
- [ ] T030 Create the audit grant-revocation migration issuing `REVOKE UPDATE, DELETE ON <database>.audit_logs FROM <app_user>`, guarded to no-op when the connection lacks GRANT privileges, per [research.md](./research.md) R-02
- [ ] T031 [P] Create the `settings` migration with `key` varchar(100) **unique**, `value` json, and nullable `updated_by`
- [ ] T032 Create the `import_jobs` migration with `user_id`, `mode` enum(`central`,`section`), nullable `section_id`, nullable `term_id`, `original_filename` varchar(255), `file_path` varchar(500), `file_hash` char(64), `mapping` json nullable, `options` json nullable, `status` enum(`uploaded`,`mapped`,`validating`,`validated`,`committing`,`completed`,`failed`), unsigned int counters `total_rows`/`processed`/`imported`/`duplicates`/`errors` all default 0, nullable `last_committed_row_id`, nullable `failure_reason` varchar(500), nullable `started_at` and `finished_at`, and indexes `(file_hash, term_id)` and `(user_id, created_at)`
- [ ] T033 [P] Create the `import_rows` staging migration with `import_job_id` cascade, `row_number` unsigned int, `raw` json, `resolved` json nullable, `status` enum(`valid`,`warning`,`error`,`committed`,`skipped`), `reasons` json nullable, and indexes `(import_job_id, status)` and `(import_job_id, row_number)`
- [ ] T034 [P] Create the `import_errors` migration with `import_job_id` cascade, `row_number` unsigned int, nullable `column` varchar(100), nullable `value` varchar(500), and `reason` varchar(500)

### Shared models

- [ ] T035 [P] Create `app/Models/Organization.php` and `app/Models/Setting.php` with their relations and casts
- [ ] T036 Create `app/Models/User.php` with `SoftDeletes`, the `HasRoles` trait, an Argon2id-hashed `password` cast, hidden `password`, the `coordinatorBranches` and `taughtSections` relations, the `sectionAccessGrants` relation, and the `coordinatorBranchIds()` and `accessibleSectionIds()` helpers that [research.md](./research.md) R-01 depends on
- [ ] T037 [P] Create `app/Models/Branch.php` (SoftDeletes), `app/Models/AcademicTerm.php` (SoftDeletes), and `app/Models/Course.php` with their relations
- [ ] T038 [P] Create `app/Models/Section.php`, `app/Models/SectionInstructorHistory.php`, and `app/Models/SectionAccessGrant.php`, with `Section::gender()` reading through the `branch` relation and no stored gender attribute
- [ ] T039 [P] Create `app/Models/Student.php` (SoftDeletes) and `app/Models/SectionEnrollment.php` with their relations
- [ ] T040 [P] Create `app/Models/AuditLog.php` with `$timestamps = false` except `created_at`, guarded against mass assignment, and **no update or delete path** — no `update()`, `save()` on an existing row, `delete()`, or `forceDelete()` reachable from application code

### Audit write mechanism (foundational — US1 depends on it)

- [ ] T041 Implement `app/Modules/Audit/Services/Auditor.php` with a `record(string $action, ?Model $entity, array $old, array $new)` method performing a plain insert on the **current** connection — no `afterCommit`, no queue, no second connection — capturing actor, IP, and user agent from the request, and stripping any `password` key from both payloads
- [ ] T042 Implement `app/Modules/Audit/Concerns/Auditable.php` as a model trait wiring `created`, `updated`, and `deleted` observers to `Auditor::record()`, with a per-model `auditedAttributes()` allowlist so no hash or token can enter a payload
- [ ] T043 Create `app/Modules/Shared/Actions/TransactionalAction.php`, the base class that wraps `handle()` in `DB::transaction()` so audited actions inherit the constitution II guarantee rather than each remembering it

### Seeders

- [ ] T044 Create `database/seeders/OrganizationSeeder.php` inserting exactly **one** `college` record, per constitution "organization hierarchy tables exist from v1 with a single college record"
- [ ] T045 Create `database/seeders/PermissionSeeder.php` seeding the 35 named permissions listed in [data-model.md](./data-model.md) Group 1, **plus `dashboard.view`** (36 total) per [ui-conflicts.md](./ui-conflicts.md) C-12 — Phase 3 needs it and seeding it now avoids a later migration. `program.create` is **not** seeded (blocked, C-04)
- [ ] T046 Create `database/seeders/RoleSeeder.php` seeding `SUPER_ADMIN` (all permissions including `scope.bypass_all`, `term.edit_closed`, `section.access_override`), `BRANCH_COORDINATOR` (the `*.view` set plus `audit.view`), and `INSTRUCTOR` (`section.view`, `student.view`, `student.create`, `enrollment.create`, `enrollment.withdraw`, `import.section.run`, `import.view`) exactly as tabulated in data-model.md
- [ ] T047 [P] Create `database/seeders/SettingSeeder.php` seeding `university_id_pattern` = `^\d{9}$`, `import_max_file_mb` = 20, `import_max_rows` = 50000, `attachment_max_mb` = 10, `default_locale` = `ar`, and `session_timeout_minutes` = 60
- [ ] T048 [P] Implement `app/Modules/Settings/Settings.php` as a cached accessor facade for the `settings` table, so no literal for any seeded value appears in application code (constitution IV)
- [ ] T049 [P] Create `database/seeders/DevAdminSeeder.php` creating the development Super Admin, refusing to run when `APP_ENV=production`
- [ ] T050 [P] Create `database/factories/` factories for `User`, `Branch`, `AcademicTerm`, `Course`, `Section`, `Student`, and `SectionEnrollment`
- [ ] T051 [P] Create `database/seeders/UiFixtureSeeder.php` porting the design system's demo dataset from `design-system/ui_kits/web-app/store.js` — 6 branches, 3 terms, 6 courses, 7 users across the three roles including a first-sign-in and a deactivated account, 9 sections including two sharing section number `123` across branches and one with no instructor, 1 access grant, instructor history, 55 students with Arabic names, and enrollments including one withdrawn with a reason. This is the dataset every visual-regression baseline renders against, so the Blade screens and the React reference show identical content

### Architecture tests

- [ ] T052 [P] Create `tests/Architecture/NoHardcodedBranchesTest.php` asserting the strings `Zulfi`, `Majmaah`, and `Rumah` appear in no file under `app/`, and that no gender or campus literal is compared in application logic (constitution IV)
- [ ] T053 [P] Create `tests/Architecture/EveryRouteAuthorizedTest.php` enumerating registered `/api/v1` routes and failing on any route without authorization middleware or a policy, and on any route lacking a corresponding authorization test (constitution VIII)
- [ ] T054 [P] Create `tests/Architecture/LocalizationParityTest.php` recursively diffing the key sets of `lang/ar` and `lang/en` and failing on any key present in one and not the other
- [ ] T055 [P] Create `tests/Architecture/NoHardcodedStringsTest.php` scanning `resources/views/` for user-facing text nodes outside `__()` or `@lang`, with an allowlist for numerals, punctuation, and component slots
- [ ] T056 [P] Create `tests/Architecture/NoHardDeleteTest.php` asserting no `forceDelete` call and no `DELETE` route exists for `users`, `students`, `branches`, or `academic_terms` (constitution V, FR-049)

**Checkpoint A**: Schema migrates, 36 permissions and 3 roles seed, the audit mechanism records inside the caller's transaction, and the UI fixture dataset loads.

---

## Phase 3: Foundational B — Design System (Blocking)

**Purpose**: Stand up the approved design system before any screen is built

**⚠️ CRITICAL**: Every screen task in Phases 4–10 consumes these components. Building a screen
before this phase would mean rebuilding it. The palette corrections (T060) must land before any
screen, or every screen inherits a failing accessibility gate.

### Tokens, accessibility corrections, and the Bootstrap bridge

- [ ] T057 Copy `design-system/tokens/{colors,typography,spacing,base}.css` verbatim to `resources/css/tokens/`, preserving all 141 custom properties and their comments
- [ ] T058 Rewrite `resources/css/tokens/fonts.css` as `@font-face` blocks for self-hosted Cairo (400, 500, 600, 700, 800) and Inter (400, 500, 600, 700) replacing the design system's Google Fonts `@import`, per [design-system.md](./design-system.md) §3 — the strict CSP and PDPL forbid a third-party origin on every page load
- [ ] T059 Download Cairo and Inter as woff2 subsets (Arabic + Latin + numerals) to `public/fonts/` and add `font-display: swap` plus preload hints for the two weights used above the fold
- [ ] T060 Apply the **7 mandatory accessibility corrections** to `resources/css/tokens/colors.css` exactly as tabulated in [design-system.md](./design-system.md) §6: KPI numeral and focus border to `--green-600` `#059669`; green body text to `--green-700` `#047857`; warning text to `--amber-700` `#B45309`; danger badge label to `--red-700` `#B91C1C`; warning badge label to `--amber-800` `#92400E`; `--text-muted` to `--track-gray` `#6B7280`. `#10B981` **stays** the brand green for all non-text use — chart fills, progress bars, the active sidebar indicator, switch fills, badge backgrounds
- [ ] T061 Write `design-system/ACCESSIBILITY-DELTA.md` recording each of the 7 corrections with its measured before ratio, required ratio, corrected value, corrected ratio, and the WCAG success criterion it satisfies, as the record the design author reviews
- [ ] T062 Create `resources/css/_bootstrap-bridge.scss` overriding Bootstrap's Sass variables from the tokens per the mapping table in [design-system.md](./design-system.md) §2 — `$primary`, `$success`, `$danger`, `$warning`, `$secondary`, `$body-bg`, `$body-color`, `$border-color`, `$font-family-sans-serif`, `$font-size-base` 0.875rem, `$h1`–`$h4` at 28/22/18/16px, the four radii at 6/8/12/16px, the three shadows, `$input-btn-focus-box-shadow`, `$spacer` on a 4px base, and `$grid-gutter-width` 14px
- [ ] T063 Create `resources/css/app-rtl.scss` and `resources/css/app-ltr.scss` importing tokens, the bridge, the matching Bootstrap build (`bootstrap.rtl` for Arabic), `_shell.scss`, and `_components.scss` in that order so tokens always win
- [ ] T064 [P] Create `tests/Architecture/TokenParityTest.php` asserting every custom property in `design-system/tokens/*.css` exists in `resources/css/tokens/*.css` with an identical value, except the 7 corrections, which must match `ACCESSIBILITY-DELTA.md` exactly — silent drift fails the build
- [ ] T065 [P] Create `tests/Architecture/NoHardcodedVisualValuesTest.php` failing on any hex colour, px radius, px shadow, or transition duration in a `.blade.php` or `.scss` file outside `resources/css/tokens/` (constitution IV extended to the visual layer)

### Icons and assets

- [ ] T066 Vendor Lucide 0.460.0 as a self-hosted SVG sprite at `public/icons/lucide.svg` containing only the icons in the [design-system.md](./design-system.md) §7 name map plus the component-level icons, with stroke width **1.75** and rounded caps — no CDN request, per C-07
- [ ] T067 [P] Create `resources/views/components/track/icon.blade.php` (`x-track-icon`) rendering a sprite reference by kebab-case name with `size` (default 20) and `color` (default `currentColor`), and a single `config/icons.php` name map so replacing the set later touches one file
- [ ] T068 [P] Wire the Vite build to copy `design-system/assets/` to `public/img/brand/`, and create `resources/views/components/track/logo.blade.php` (`x-track-logo`) exposing the six approved variants primary / reversed / mono / lockup / mark / mark-reversed with a `height` prop
- [ ] T069 [P] Generate the favicon and PWA icon set from `design-system/assets/app-icon-{light,dark,green,black}.png` into `public/` and reference them in the shell layout

### The 24 Blade components

Each task re-authors one design-system component. Read `design-system/components/<group>/<Name>.jsx`
for anatomy and `<Name>.prompt.md` for the behavioural contract — quoted in each task below. Styles
go in `resources/css/_components.scss` using tokens only; behaviour in
`resources/js/components/<name>.js` as an Alpine component.

- [ ] T070 [P] Create `x-track-button` in `resources/views/components/track/button.blade.php` — variants primary / accent / secondary / ghost / danger; sizes sm 32 / md 40 / lg 48; `icon` and `iconEnd` by Lucide name with **`icon` on the right in RTL**; `loading` swaps the icon for a spinner and disables the control **holding its width** so layout does not shift; hover darkens filled to `--blue-800`; press `scale(.98)`; focus `--green-600` border + `--ring-focus`
- [ ] T071 [P] Create `x-track-icon-button` in `resources/views/components/track/icon-button.blade.php` — square; variants ghost / outline / filled; `dot` adds the red notification dot; **`label` is required** and renders as both `aria-label` and `title`
- [ ] T072 [P] Create `x-track-input` in `resources/views/components/track/input.blade.php` — label with required asterisk, optional leading icon, hint, error; sizes sm/md/lg on `--control-h-*`; validates **on blur** and again on submit via `resources/js/components/field-validation.js`; `error` turns the border `--red-600`, replaces the hint, and sets `role="alert"` (FR-078)
- [ ] T073 [P] Create `x-track-select` in `resources/views/components/track/select.blade.php` — native select styled to match Input, accepting string or `{value,label}` options, optional leading icon
- [ ] T074 [P] Create `x-track-checkbox` in `resources/views/components/track/checkbox.blade.php` — square, filled `--track-blue` when on, label beside it
- [ ] T075 [P] Create `x-track-switch` in `resources/views/components/track/switch.blade.php` — toggle, `--track-green` fill when on (non-text use, so `#10B981` is retained per T060)
- [ ] T076 [P] Create `x-track-status-badge` in `resources/views/components/track/status-badge.blade.php` — 6px radius pill covering all **19 statuses** in [design-system.md](./design-system.md) §8 with the fixed AR/EN vocabulary from `lang/`; the component **cannot render without its text label**, which makes FR-077 structural rather than reviewed; uses the T060-corrected `--red-700` and `--amber-800` label colours
- [ ] T077 [P] Create `x-track-alert` in `resources/views/components/track/alert.blade.php` — tones info / success / warning / danger, **always icon + text**, optional title, optional action slot, optional dismiss; used for the first-sign-in recommendation, the lockout countdown, scope notices, the previously-imported-file warning, and the queue-waiting state
- [ ] T078 [P] Create `x-track-toast` in `resources/views/components/track/toast.blade.php` plus `resources/js/components/toast.js` — tones success / error / info, ~4s auto-dismiss, fixed bottom-**start** (bottom-left in RTL), error tone carries a **retry action** (FR-080, FR-081)
- [ ] T079 [P] Create `x-track-dialog` in `resources/views/components/track/dialog.blade.php` plus `resources/js/components/dialog.js` — 16px radius, widths 480 (confirm) / 560 (form), teal 45% + 2px blur scrim, footer on a `--gray-50` band, `inline` variant without the overlay; **traps focus and restores it to the trigger on close** (SC-014)
- [ ] T080 [P] Create `x-track-progress` in `resources/views/components/track/progress.blade.php` — thin bar with percentage, colour auto-shifting green / blue / red by value, `tone` override, `showLabel` toggle
- [ ] T081 [P] Create `x-track-skeleton` in `resources/views/components/track/skeleton.blade.php` — pulsing placeholder with `width` and `height`, honouring `prefers-reduced-motion` (FR-079)
- [ ] T082 [P] Create `x-track-tooltip` in `resources/views/components/track/tooltip.blade.php` plus `resources/js/components/tooltip.js` — dark tooltip on hover **and keyboard focus**, for icon buttons and truncated values
- [ ] T083 [P] Create `x-track-empty-state` in `resources/views/components/track/empty-state.blade.php` — centred icon + title + message + optional action; also the renderer for full-page 403 and 404 (FR-025, FR-024, FR-079)
- [ ] T084 [P] Create `x-track-card` in `resources/views/components/track/card.blade.php` — white, 1px `--border-default`, 12px radius, `--shadow-card`, 20px padding, optional title row with an action slot, `padding=0` variant for cards hosting tabs + table
- [ ] T085 [P] Create `x-track-stat-card` in `resources/views/components/track/stat-card.blade.php` — centred KPI: `--fs-stat` 26px bold **Inter** numeral over an Arabic label, tones blue / green / dark, optional icon and trend; the green tone uses the T060-corrected `--green-600`
- [ ] T086 Create `x-track-data-table` in `resources/views/components/track/data-table.blade.php` plus `resources/js/components/data-table.js` — gray header, row hover `--blue-50`, `onRowClick`, per-column slot renderers, `numeric` columns in Inter with `tabular-nums`, `dense` variant, `empty` message via `x-track-empty-state`, `loading` state rendering `x-track-skeleton` rows, client sort and filter without reload, and **stacked cards below 768px** (FR-085)
- [ ] T087 Create `x-track-sidebar` in `resources/views/components/track/sidebar.blade.php` — fixed 240px, **right in RTL** / left in LTR, `--surface-sidebar` gradient, `x-track-logo` variant reversed at top, items 42px tall at 8px radius with 19px icons, active item `--surface-sidebar-active` with a green indicator, hover white 8%, footer slot for logout, and an off-canvas drawer below 768px
- [ ] T088 Create `x-track-topbar` in `resources/views/components/track/topbar.blade.php` — 64px white with a hairline bottom border, drawer toggle, search field, notifications `x-track-icon-button` with dot, help, user block showing name + role linking to the profile, and an `end` slot for the language switch
- [ ] T089 [P] Create `x-track-tabs` in `resources/views/components/track/tabs.blade.php` plus `resources/js/components/tabs.js` — variants underline (green indicator) and segmented (gray track), optional per-tab counts, full keyboard support with arrow keys
- [ ] T090 [P] Create `x-track-breadcrumbs` in `resources/views/components/track/breadcrumbs.blade.php` — drill-down path above every detail title, **chevron direction following `dir`** (FR-083)
- [ ] T091 [P] Create `x-track-stepper` in `resources/views/components/track/stepper.blade.php` — the 6 import steps; done steps green with a check, active step `--track-blue` with a soft ring, upcoming steps gray; `aria-current="step"` on the active one
- [ ] T092 [P] Create `resources/views/components/track/page-header.blade.php` — breadcrumbs slot → h1 `--text-h1` → optional 14px `--text-secondary` subtitle → right-aligned action cluster, 20px bottom margin, per [design-system.md](./design-system.md) §4
- [ ] T093 [P] Create `resources/views/components/track/panel.blade.php` and `field.blade.php` — the two layout primitives the reference kit uses throughout (`Panel` = bordered white section; `Field` = label + control + error/hint wrapper)

### Shell, locale, and motion

- [ ] T094 Implement `app/Modules/Shared/Http/Middleware/SetLocale.php` resolving locale from `users.locale`, falling back to the `default_locale` setting, and register it on the `web` group
- [ ] T095 Create `resources/views/layouts/app.blade.php` emitting `<html lang dir>` from the active locale, loading the matching stylesheet, composing `x-track-sidebar` + `x-track-topbar` + a scrolling content region at `28px 28px 48px`, plus a **skip link**, the flash-message region, and the first-sign-in banner slot
- [ ] T096 [P] Create `resources/css/_shell.scss` implementing the §4 layout geometry — `--sidebar-width` 240px, `--topbar-height` 64px, 14px grid gaps, and the <768px drawer and stacked-card breakpoints at 1280 / 1920 verification widths
- [ ] T097 [P] Create `resources/js/app.js` registering every Alpine component from `resources/js/components/`, plus the locale switcher and the **5-second poller** for queued work (FR-079)
- [ ] T098 [P] Add the global motion rules to `resources/css/tokens/base.css` — 120 / 180 / 280ms on `cubic-bezier(.2,0,0,1)`, fades, colour, and progress width only, no bounce or parallax, all wrapped in a `prefers-reduced-motion: reduce` guard that disables them
- [ ] T099 [P] Create the `lang/ar/` and `lang/en/` files for `common`, `status`, and `nav`, seeding the fixed status vocabulary from [design-system.md](./design-system.md) §8 and the navigation nouns from the design system's content rules

### Fidelity harness

- [ ] T100 Create `resources/views/dev/component-gallery.blade.php`, a route available only when `APP_ENV=local`, rendering every `x-track-*` component in every variant and state — the surface the component visual-regression suite screenshots
- [ ] T101 Create `tests/Visual/components.spec.ts` screenshotting each of the 24 components in the gallery against the corresponding `design-system/components/**/*.card.html` reference rendered at the same viewport, asserting **≤ 1% pixel difference** at 1440×900 in both locales, with the threshold justified by anti-aliasing and font-hinting differences rather than tolerance for drift
- [ ] T102 [P] Create `tests/Visual/axe.spec.ts` running axe-core over the component gallery in both locales, requiring **zero** WCAG 2.1 AA violations
- [ ] T103 [P] Create `tests/Visual/helpers/render-reference.ts` that boots `design-system/ui_kits/web-app/index.html` with the T051 fixture data and a deterministic locale, so reference screenshots are reproducible rather than depending on `localStorage` state

**Checkpoint B**: The design system is live — 141 tokens (7 corrected), 24 components, the shell in both directions, self-hosted fonts and icons, and a visual-regression harness that fails on drift. Screen work can begin.

---

## Phase 4: User Story 1 - Staff Get Accounts And Sign In (Priority: P1) 🎯 MVP

**Goal**: Super Admin creates coordinator and instructor accounts; those people sign in, see a non-blocking first-sign-in recommendation, change their own password, and are refused when deactivated — with authorship preserved.

**Independent Test**: Create one coordinator and one instructor, sign in as each, change a password, deactivate one and confirm sign-in is refused while their name still appears on records they created.

### Tests for User Story 1

- [ ] T104 [P] [US1] Write `tests/Feature/Authentication/LoginTest.php` covering successful sign-in with session regeneration and role-based landing, the generic failure message that never reveals which field was wrong (FR-009), the inactive-account refusal (FR-011), and 60-minute idle expiry (FR-016)
- [ ] T105 [P] [US1] Write `tests/Feature/Authentication/LoginThrottleTest.php` asserting five failed attempts per email + IP within one minute block the sixth for 60 seconds with a countdown (FR-010)
- [ ] T106 [P] [US1] Write `tests/Feature/Authentication/FirstLoginTest.php` asserting the recommendation appears only when `first_login_at` was null before the request, and not on subsequent sign-ins (FR-012)
- [ ] T107 [P] [US1] Write `tests/Feature/Authentication/PasswordChangeTest.php` covering the policy, the wrong-current-password refusal, the new-equals-current refusal (FR-014), and that other sessions are invalidated while the current one survives (FR-015)
- [ ] T108 [P] [US1] Write `tests/Feature/UserManagement/CreateUserTest.php` covering creation with both name languages and a role, required `branch_ids` for a coordinator (FR-004), the duplicate-email refusal including against a soft-deleted account (FR-005), and that no password or hash appears in the response or the audit payload (FR-007)
- [ ] T109 [P] [US1] Write `tests/Feature/UserManagement/DeactivateUserTest.php` asserting sign-in is refused afterwards, all sessions are invalidated, the row is not erased, and the person's name still renders on records they created (FR-018, FR-049)
- [ ] T110 [P] [US1] Write `tests/Feature/UserManagement/ResetPasswordTest.php` asserting all target sessions are invalidated and the password appears in neither audit payload
- [ ] T111 [P] [US1] Write `tests/Feature/Authentication/AbsentRoutesTest.php` asserting `/register`, `/password/forgot`, `/password/reset`, and any 2FA path each return 404 (FR-001)
- [ ] T112 [P] [US1] Write `tests/Authorization/UserEndpointsTest.php` with an allow case and a deny case for each of the 12 endpoints in [contracts/auth-users.md](./contracts/auth-users.md)
- [ ] T113 [P] [US1] Write `tests/Unit/PasswordPolicyTest.php` asserting the ≥ 8 characters, ≥ 1 letter, ≥ 1 digit rule (FR-006)
- [ ] T114 [P] [US1] Write `tests/Visual/us1.spec.ts` screenshotting login (default, error, lockout-countdown), the first-sign-in banner, the home landing, the users list, the create/edit dialog, and the deactivate/reset confirmations against the `AuthScreens`/`HomeScreen`/`UsersScreen` references, both locales, 1280/1440/1920/390

### Backend implementation for User Story 1

- [ ] T115 [P] [US1] Implement `app/Modules/Shared/Rules/PasswordPolicy.php` as the single rule object used by create, change, and reset, so the three paths cannot drift
- [ ] T116 [US1] Implement `app/Modules/Authentication/Http/Controllers/LoginController.php` with `POST /auth/login` and `POST /auth/logout` per contracts: session regeneration, the generic 422, the inactive 403, `first_login_at` set during the request, and the role landing route
- [ ] T117 [US1] Register the login rate limiter keyed on `email` + IP at 5 per minute in `app/Providers/AppServiceProvider.php`, returning 429 with `retry_after`
- [ ] T118 [P] [US1] Implement `app/Modules/Authentication/Http/Controllers/MeController.php` with `GET /auth/me` returning roles, permissions, and the resolved `scope` object, computed per request so a scope change takes effect immediately (FR-027)
- [ ] T119 [US1] Implement `app/Modules/Authentication/Actions/ChangePassword.php` and `PasswordController.php` with `PUT /auth/password`, invalidating other sessions via `logoutOtherDevices` and auditing `user.password_changed`
- [ ] T120 [US1] Implement `app/Modules/UserManagement/Actions/CreateUser.php` extending `TransactionalAction`, hashing with Argon2id, defaulting `locale` from the `default_locale` setting, attaching roles and coordinator branches, and auditing `user.created` with the password stripped
- [ ] T121 [US1] Implement `app/Modules/UserManagement/Http/Requests/StoreUserRequest.php` and `UpdateUserRequest.php` enforcing unique email across soft-deleted rows, `PasswordPolicy`, and `branch_ids` required when the role is `BRANCH_COORDINATOR`
- [ ] T122 [US1] Implement `app/Modules/UserManagement/Http/Controllers/UserController.php` with `GET /users`, `POST /users`, and `PUT /users/{id}`, never exposing any password field
- [ ] T123 [US1] Implement `app/Modules/UserManagement/Actions/DeactivateUser.php` and `PATCH /users/{id}/deactivate`, invalidating the target's sessions, refusing self-deactivation with 409, and auditing `user.deactivated`
- [ ] T124 [US1] Implement `app/Modules/UserManagement/Actions/ResetPassword.php` and `POST /users/{id}/reset-password`, invalidating **all** target sessions and auditing `user.password_reset`
- [ ] T125 [P] [US1] Implement `app/Modules/UserManagement/Policies/UserPolicy.php` mapping each action to its `user.*` permission, and register it in `app/Providers/AuthServiceProvider.php`
- [ ] T126 [US1] Add `SESSION_LIFETIME=60` enforcement plus HttpOnly, Secure, and `SameSite=Lax` cookie settings in `config/session.php`, and confirm `/register` is absent from `routes/web.php` and `routes/api_v1.php`

### Screens for User Story 1 — built to the design system

Each screen task carries the §10 state matrix as its checklist: default, loading, empty,
validation, error, success, confirmation, focus, hover, press, disabled, motion, RTL/LTR, <768px,
keyboard.

- [ ] T127 [US1] Build `resources/views/auth/login.blade.php` to the `AuthScreens.jsx` reference — split layout with `hero-campus.png` and the teal protection gradient, `x-track-logo` lockup, `x-track-input` for email and password with a leading icon, `x-track-button` primary full-width with `loading`, the language switch, and `x-track-alert` danger for the generic failure and the 60-second lockout countdown
- [ ] T128 [P] [US1] Build `resources/views/components/track/first-login-banner.blade.php` as a dismissable non-blocking `x-track-alert` tone warning with an action linking to the password-change screen, matching the reference's copy "يُنصح بتغيير كلمة المرور الأولية" (FR-012)
- [ ] T129 [P] [US1] Build `resources/views/profile/index.blade.php` and `password.blade.php` to the `ProfileScreen` reference — profile details, last-sign-in display, and the change-password form with `x-track-input` blur validation on all three fields
- [ ] T130 [US1] Build `resources/views/home/index.blade.php` to the `HomeScreen` reference — the role landing page (FR-017) with **6 `x-track-stat-card`** tiles (branches, sections, students, users, enrollments, imports), an `x-track-card` of recent activity via `x-track-data-table` dense, a quick-actions card, and the first-sign-in banner; each role sees only its own in-scope counts
- [ ] T131 [US1] Build `resources/views/users/index.blade.php` to the `UsersScreen` reference — `x-track-page-header` with a create action, filter row of `x-track-select` for role/branch/status plus a search `x-track-input`, and `x-track-data-table` with `x-track-status-badge` for active/inactive, role labels, branch labels, last sign-in, and row actions
- [ ] T132 [US1] Build `resources/views/users/_dialog.blade.php` to the `UserDialog` reference — `x-track-dialog` at 560px, a two-column grid of `x-track-input` for both names and email, `x-track-select` for role, a conditional multi-select of branches shown only for `BRANCH_COORDINATOR`, and inline errors on blur
- [ ] T133 [P] [US1] Build the deactivate and reset-password confirmations as `x-track-dialog` danger variants restating the impact — "سيتم إنهاء جميع جلسات المستخدم" and the preserved-history note (FR-082)
- [ ] T134 [P] [US1] Add the `auth`, `users`, `profile`, and `home` translation keys to `lang/ar/` and `lang/en/`, taking the Arabic strings from `design-system/ui_kits/web-app/i18n.js` so the approved copy register is preserved verbatim

**Checkpoint**: User Story 1 is fully functional, visually matches the reference, and is testable on its own — the MVP.

---

## Phase 5: User Story 2 - Each Role Sees Only Its Own Data (Priority: P2)

**Goal**: Every list, search, export, and directly typed address is limited to the caller's branches or sections; out-of-scope records behave as nonexistent; scope changes apply on the next request.

**Independent Test**: Sign in as each of two coordinators and confirm every access path shows only their own branch, and that the other branch's records return 404 rather than 403.

### Tests for User Story 2

- [ ] T135 [P] [US2] Write `tests/Scope/OutOfScopeReturns404Test.php` asserting **404, never 403** for a cross-branch section, student, enrollment, import job, and audit entry, through the API route, the Blade route, the list endpoint, and the export endpoint (FR-024)
- [ ] T136 [P] [US2] Write `tests/Scope/CoordinatorBranchScopeTest.php` asserting a coordinator's lists and their totals contain only their branches' records
- [ ] T137 [P] [US2] Write `tests/Scope/InstructorSectionScopeTest.php` asserting an instructor sees only sections where they are `instructor_id`, plus granted sections flagged `shared_access`
- [ ] T138 [P] [US2] Write `tests/Scope/ExportRespectsScopeTest.php` asserting no export can contain a record the same caller cannot retrieve individually (FR-023)
- [ ] T139 [P] [US2] Write `tests/Scope/ScopeChangeTakesEffectImmediatelyTest.php` asserting a role or branch change is in force on the target's next request with no re-authentication and no stale cached permission set (FR-027)
- [ ] T140 [P] [US2] Write `tests/Feature/Authorization/MissingPermissionReturns403Test.php` asserting a **visible** record with a missing permission yields 403 plus an `access.denied` audit entry — so 403 and 404 are never conflated
- [ ] T141 [P] [US2] Write `tests/Feature/Authorization/NewRoleNeedsNoCodeChangeTest.php` creating a fourth role by seeding permissions only, and asserting it works with no policy or screen modification (FR-020)
- [ ] T142 [P] [US2] Write `tests/Authorization/AccessGrantEndpointTest.php` with allow and deny cases for `POST /sections/{id}/access-grants`
- [ ] T143 [P] [US2] Write `tests/Visual/us2.spec.ts` screenshotting the full-page 403 and 404, the shared-access badge in the section list, and each role's sidebar against the `RefusalScreen` and `Shell` references, both locales

### Implementation for User Story 2

- [ ] T144 [US2] Implement `app/Modules/Authorization/Scopes/BranchScope.php` as an Eloquent **global** scope constraining `branch_id` directly or through the `section` relation to `coordinatorBranchIds()`, inert for `scope.bypass_all` holders and when no user is authenticated
- [ ] T145 [US2] Implement `app/Modules/Authorization/Scopes/SectionScope.php` constraining to `accessibleSectionIds()` — the union of taught sections and `section_access_grants` — with the same two inert conditions
- [ ] T146 [US2] Implement the `BelongsToBranchScope.php` and `BelongsToSectionScope.php` traits in `app/Modules/Authorization/Concerns/` and apply them to `Section`, `Student`, `SectionEnrollment`, `ImportJob`, and `AuditLog`
- [ ] T147 [US2] Configure route-model binding in `app/Providers/RouteServiceProvider.php` to resolve **through** the scoped query so an out-of-scope id raises `ModelNotFoundException` → 404 before any policy runs, per [research.md](./research.md) R-01
- [ ] T148 [US2] Implement `app/Modules/Authorization/Http/Middleware/AuditDeniedAccess.php` recording `access.denied` on a 403 or on a 404 arising from a scope miss, capturing the attempted route and id
- [ ] T149 [P] [US2] Implement `app/Modules/Authorization/Actions/GrantSectionAccess.php` and `POST /sections/{id}/access-grants`, auditing `section.access_granted`
- [ ] T150 [P] [US2] Add the `shared_access` computed attribute to `app/Models/Section.php` and render it as `x-track-status-badge status="shared"` in the section list (FR-026)
- [ ] T151 [P] [US2] Implement `PUT /users/{id}/roles` and `PUT /users/{id}/branches` in `UserRoleController.php` and `UserBranchController.php`, auditing `user.roles_changed` and `user.branches_changed` with previous and new sets
- [ ] T152 [US2] Build `resources/views/errors/403.blade.php` and `404.blade.php` to the `RefusalScreen` reference — full-page `x-track-empty-state` with a `shield-off` / `file-question` icon, the approved Arabic copy, and a link back to the caller's landing page, exposing no internal detail (FR-025, FR-080)
- [ ] T153 [P] [US2] Implement the per-role navigation map in `config/navigation.php` matching the reference `NAV` exactly — Super Admin: home, dashboard\*, initiatives\*, users, terms, branches, courses, sections, imports, audit · Coordinator: home, initiatives\*, sections, imports, audit · Instructor: home, mysections, imports. Entries marked \* are **stubbed** to a "coming in a later phase" placeholder so the sidebar matches the approved design without dead links. `programs` is **omitted** pending C-04

**Checkpoint**: Stories 1 and 2 work independently. Cross-branch data is unreachable through every access path.

---

## Phase 6: User Story 3 - Every Sensitive Change Is Accountable (Priority: P3)

**Goal**: The audit trail is queryable, filterable, exportable, visible per entity within scope, and provably immutable and transactional.

**Independent Test**: Perform one account creation, one role change, and one refused access attempt; confirm exactly three complete entries exist, that filtering finds them, and that no path edits or removes them.

### Tests for User Story 3

- [ ] T154 [P] [US3] Write `tests/Feature/Audit/TransactionalWriteTest.php` forcing the audit insert to fail mid-action and asserting the business row is **absent** afterwards and the caller is told to retry (FR-030)
- [ ] T155 [P] [US3] Write `tests/Feature/Audit/ImmutabilityTest.php` asserting all three layers independently: no update or delete path in code, no `updated_at` column, and — where the connection has privileges — that a direct `UPDATE` on `audit_logs` is refused for the application user (FR-031)
- [ ] T156 [P] [US3] Write `tests/Feature/Audit/ActionCoverageTest.php` performing each of the 34 action keys tabulated in [contracts/audit.md](./contracts/audit.md) once and asserting exactly one entry each with the correct key (FR-028)
- [ ] T157 [P] [US3] Write `tests/Feature/Audit/NoSecretsInPayloadTest.php` asserting no audit payload contains a `password`, hash, session token, or CSRF token key
- [ ] T158 [P] [US3] Write `tests/Feature/Audit/OldAndNewValuesTest.php` asserting both previous and new values are present for every changed field on an edit
- [ ] T159 [P] [US3] Write `tests/Scope/AuditLogScopeTest.php` asserting a coordinator sees only entries resolving into their branches plus their own actions, and cannot widen `branch_id`
- [ ] T160 [P] [US3] Write `tests/Authorization/AuditEndpointsTest.php` with allow and deny cases for `GET /audit-logs`, `GET /audit-logs/export`, and `GET /{entity}/{id}/activity`
- [ ] T161 [P] [US3] Write `tests/Performance/AuditLogFilterTest.php` seeding 1,000,000 entries and asserting a person + date-range filter returns in under 2 seconds (SC-004), in the `performance` group, excluded from the default run
- [ ] T162 [P] [US3] Write `tests/Visual/us3.spec.ts` screenshotting the audit list with filters applied, the before/after detail expansion, the immutability notice, and an activity tab against the `AuditScreen` reference, both locales

### Implementation for User Story 3

- [ ] T163 [US3] Implement `app/Modules/Audit/Http/Controllers/AuditLogController.php` with `GET /audit-logs` supporting the `user_id`, `action`, `entity_type`, `entity_id`, `date_from`, `date_to`, and `branch_id` filters, served by the T029 indexes
- [ ] T164 [P] [US3] Implement `app/Modules/Audit/Http/Resources/AuditLogResource.php` adding server-localized `action_label` and `entity_label` while keeping the raw `action` key stable for filtering
- [ ] T165 [P] [US3] Implement `app/Modules/Audit/Exports/AuditLogExport.php` and `GET /audit-logs/export` producing an Arabic-safe `.xlsx` whose rows match the list endpoint exactly for the same filters, with a header block carrying the applied filters, timestamp, and requesting user — auditing `audit.exported`
- [ ] T166 [US3] Implement `app/Modules/Audit/Http/Controllers/EntityActivityController.php` with `GET /{entity}/{id}/activity` for terms, branches, sections, and students, returning 404 when the entity is out of scope so it cannot be used to probe (FR-033)
- [ ] T167 [P] [US3] Apply the `Auditable` trait and an `auditedAttributes()` allowlist to `User`, `Branch`, `AcademicTerm`, `Course`, `Section`, `Student`, and `SectionEnrollment`
- [ ] T168 [US3] Build `resources/views/audit/index.blade.php` to the `AuditScreen` + `AuditTable` reference — `x-track-page-header` with an export action, a filter `x-track-card` holding actor/action/entity/date-range `x-track-select` and date inputs, `x-track-data-table` with monospace-numeric timestamps and an expandable before/after JSON diff row, and a persistent `x-track-alert` tone info stating entries cannot be modified or deleted
- [ ] T169 [P] [US3] Build `resources/views/components/track/activity-tab.blade.php` as an `x-track-tabs` panel embedding the scoped activity table, reused on term, branch, section, and student detail pages
- [ ] T170 [P] [US3] Add the `audit` translation keys — one `action_label` per action key in [contracts/audit.md](./contracts/audit.md) — to `lang/ar/audit.php` and `lang/en/audit.php`, taking the Arabic labels from the reference kit's audit strings per [ui-conflicts.md](./ui-conflicts.md) C-11

**Checkpoint**: The audit trail is complete, queryable, and provably immutable before records exist at scale.

---

## Phase 7: User Story 4 - The Academic Structure Is Set Up (Priority: P4)

**Goal**: Terms, branches, courses, sections, students, and enrollments are manageable, with the section key unique, one current term, instructor changes preserving authorship, and soft delete throughout.

**Independent Test**: Create a term, two branches, one course, two sections sharing a section number across branches, one student enrolled in both; withdraw from one and reassign one instructor.

### Tests for User Story 4

- [ ] T171 [P] [US4] Write `tests/Feature/Terms/TermLifecycleTest.php` covering creation in `draft`, the permitted transitions from [data-model.md](./data-model.md), and 409 on a disallowed transition such as `draft → archived`
- [ ] T172 [P] [US4] Write `tests/Feature/Terms/SingleCurrentTermTest.php` asserting exactly one row has `is_current = true` after sequential and concurrent set-current attempts (FR-036)
- [ ] T173 [P] [US4] Write `tests/Feature/Terms/ClosedStatusStorableTest.php` asserting the enum accepts `closed` and `archived` and that **no seeder or screen in this feature sets a term to `closed`**, per plan.md Complexity Tracking
- [ ] T174 [P] [US4] Write `tests/Feature/Sections/SectionUniquenessTest.php` asserting the same `section_number` coexists across differing term, branch, or course, and that a genuine duplicate is refused **at the database index level** as well as by the form request (FR-041)
- [ ] T175 [P] [US4] Write `tests/Feature/Sections/ChangeInstructorTest.php` asserting access moves, `section_instructor_history` gains a closed row and a new open row, and **every existing record keeps its original author** (FR-048, SC-018)
- [ ] T176 [P] [US4] Write `tests/Feature/Students/SingleStudentRecordTest.php` asserting one `students` row per University ID across multiple enrollments, and that a student appears once in a scoped list despite several enrollments (FR-043, SC-012)
- [ ] T177 [P] [US4] Write `tests/Feature/Students/WithdrawEnrollmentTest.php` asserting the reason is mandatory, the date is recorded, the student record survives, and 409 on an already-withdrawn enrollment (FR-047)
- [ ] T178 [P] [US4] Write `tests/Feature/AcademicStructure/NoHardDeleteTest.php` asserting a branch or term holding records refuses permanent removal and offers only deactivation or archiving (FR-049)
- [ ] T179 [P] [US4] Write `tests/Feature/Sections/TermRequiredFirstTest.php` asserting section creation returns 409 with the explanatory message when no term exists (FR-037)
- [ ] T180 [P] [US4] Write `tests/Authorization/AcademicEndpointsTest.php` with allow and deny cases for all 18 endpoints in [contracts/academic.md](./contracts/academic.md)
- [ ] T181 [P] [US4] Write `tests/Unit/UniversityIdRuleTest.php` asserting the rule reads `university_id_pattern` from settings, defaults to `^\d{9}$`, and produces a message naming the expected format (FR-044)
- [ ] T182 [P] [US4] Write `tests/Visual/us4.spec.ts` screenshotting the terms, branches, courses, sections, and students lists, the section detail with its roster and activity tabs, the student detail, and each create/edit/confirm dialog against the `StructureScreens` and `SectionsScreens` references, both locales, including the <768px stacked-card table

### Backend implementation for User Story 4

- [ ] T183 [P] [US4] Implement `app/Modules/Shared/Rules/UniversityId.php` as the single rule used by both manual entry and the import row validator
- [ ] T184 [US4] Implement `app/Modules/AcademicTerms/Actions/SetCurrentTerm.php` extending `TransactionalAction`, clearing the previous flag and setting the new one in one transaction, and auditing `term.set_current` naming both terms
- [ ] T185 [US4] Implement `app/Modules/AcademicTerms/Actions/ChangeTermStatus.php` enforcing the state machine, requiring a reason when leaving `closed`, returning 409 on a disallowed transition, and auditing `term.status_changed`
- [ ] T186 [US4] Implement `app/Modules/AcademicTerms/Http/Controllers/TermController.php` with `GET`, `POST`, `PUT /terms`, `PATCH /terms/{id}/status`, and `PATCH /terms/{id}/set-current`, excluding archived terms from the default list
- [ ] T187 [P] [US4] Implement `app/Modules/AcademicStructure/Http/Controllers/BranchController.php` with `GET`, `POST`, and `PUT /branches`, where `is_active = false` is the only removal path, auditing `branch.created`, `branch.updated`, and `branch.deactivated`
- [ ] T188 [P] [US4] Implement `app/Modules/AcademicStructure/Http/Controllers/CourseController.php` with `GET` and `POST /courses`, refusing a duplicate `(organization_id, code)` with 422
- [ ] T189 [US4] Implement `app/Modules/Sections/Http/Requests/StoreSectionRequest.php` producing the localized message "توجد شعبة بهذا الرقم لنفس المقرر والفرع والفصل", with the unique index as the authority
- [ ] T190 [US4] Implement `app/Modules/Sections/Http/Controllers/SectionController.php` with `GET`, `POST`, and `PUT /sections`, returning branch `gender` as a derived field and `shared_access` where applicable
- [ ] T191 [US4] Implement `app/Modules/Sections/Actions/ChangeSectionInstructor.php` extending `TransactionalAction`: update `instructor_id`, close the open history row's `unassigned_at`, insert a new open row, never rewrite any existing record's author, and audit `section.instructor_changed`
- [ ] T192 [P] [US4] Implement `app/Modules/Students/Http/Controllers/StudentController.php` with `GET /students` (distinct across enrollments) and `GET /students/{id}` returning enrollments plus an empty initiative-history placeholder
- [ ] T193 [US4] Implement `app/Modules/Students/Actions/EnrollStudent.php` linking an existing University ID rather than duplicating, keeping the stored name on a mismatch and returning a `warnings` array, and auditing `student.created` when new plus `enrollment.created` always
- [ ] T194 [US4] Implement `app/Modules/Students/Actions/WithdrawEnrollment.php` requiring a reason, setting `withdrawn_at`, preserving the student record, and auditing `enrollment.withdrawn`
- [ ] T195 [P] [US4] Implement the policies `TermPolicy`, `BranchPolicy`, `CoursePolicy`, `SectionPolicy`, `StudentPolicy`, and `EnrollmentPolicy` under their modules and register them in `app/Providers/AuthServiceProvider.php`

### Screens for User Story 4 — built to the design system

- [ ] T196 [US4] Build `resources/views/terms/index.blade.php` and `_dialog.blade.php` to the `TermsScreen` + `TermDialog` reference — list with `x-track-status-badge` for draft/active/closed/archived plus a `current` badge, section counts, a status-change `x-track-dialog` confirming the transition, and a set-current confirmation restating that the previous current term will be cleared
- [ ] T197 [P] [US4] Build `resources/views/branches/index.blade.php` and `_dialog.blade.php` to the `BranchesScreen` reference — campus name in both languages, a gender `x-track-select`, an active `x-track-switch`, section and student counts, and an `x-track-alert` tone info stating a branch with sections is deactivated rather than deleted
- [ ] T198 [P] [US4] Build `resources/views/courses/index.blade.php` and `_dialog.blade.php` to the `CoursesScreen` reference — code in LTR, both names, section count, and the duplicate-code inline error
- [ ] T199 [US4] Build `resources/views/sections/index.blade.php` to the `SectionsScreen` reference — filter row of term/branch/course/instructor `x-track-select` plus search, `x-track-data-table` showing section number (numeric), course, branch with derived gender, instructor, enrollment count, and the `shared` badge; the instructor variant (`mysections`) drops the branch filter and shows `x-track-empty-state` "لم تُسند إليك أي شعبة بعد" when empty
- [ ] T200 [US4] Build `resources/views/sections/show.blade.php` to the `SectionDetail` + `SectionMeta` + `RosterTable` reference — `x-track-breadcrumbs`, a metadata panel, and `x-track-tabs` for Roster / Instructor history / Activity, with the roster table carrying per-row withdraw actions and the withdrawn rows rendered greyed with their reason
- [ ] T201 [P] [US4] Build `resources/views/sections/_dialog.blade.php` and `_assign-dialog.blade.php` to the `SectionDialog` + `AssignDialog` reference — create/edit with the duplicate-key error, and instructor change as a confirmation restating that access moves while past entries keep their author
- [ ] T202 [P] [US4] Build `resources/views/students/index.blade.php` and `show.blade.php` to the `StudentsScreen` + `StudentDetail` reference — University ID in LTR numerics, both names, enrollment count, and the detail page's enrollment table plus activity tab
- [ ] T203 [P] [US4] Build the enroll-student and withdraw-student dialogs — enroll with University ID validation and the name-mismatch warning, withdraw with a **mandatory** reason textarea and a danger confirmation (FR-047, FR-082)
- [ ] T204 [P] [US4] Add the `terms`, `branches`, `courses`, `sections`, and `students` translation keys to `lang/ar/` and `lang/en/`, taking Arabic strings from the reference `i18n.js`

**Checkpoint**: Real academic data can be created and maintained by hand, matching the approved UI.

---

## Phase 8: User Story 5 - A Whole Roster Loads From One File (Priority: P5)

**Goal**: The six-step central import validates everything before writing anything, commits in resumable 500-row chunks, links rather than duplicates, and produces a downloadable per-row error report.

**Independent Test**: Upload one file mixing valid rows with every defect category; confirm nothing is written before confirmation, every defect yields a row-level reason, and a re-upload creates zero new records.

### Tests for User Story 5

- [ ] T205 [P] [US5] Write `app/Console/Commands/MakeRosterFixture.php` (`track:make-roster-fixture --rows= --with-defects`) emitting an `.xlsx` containing valid rows plus in-file duplicates, malformed University IDs, missing required values, an unknown branch, an unknown course, an unregistered instructor, a name mismatch, and an unresolvable section
- [ ] T206 [P] [US5] Write `app/Console/Commands/CoreCounts.php` (`track:core-counts`) printing row counts for sections, students, enrollments, courses, and branches — the probe quickstart scenario 5.4 uses
- [ ] T207 [P] [US5] Write `tests/Feature/Imports/NoWritesBeforeConfirmTest.php` snapshotting core table counts across upload, mapping, validation, and preview and asserting they are **unchanged** — the contract's central guarantee (FR-060)
- [ ] T208 [P] [US5] Write `tests/Feature/Imports/UploadValidationTest.php` asserting refusal over 20 MB, over 50,000 rows, on an unsupported type, and on a `.xlsx` extension whose magic bytes disagree — all before storage (FR-055)
- [ ] T209 [P] [US5] Write `tests/Feature/Imports/MappingTest.php` asserting the suggested mapping is returned, an unmapped required target is refused, and a mapped column absent from the header row is refused (FR-056)
- [ ] T210 [P] [US5] Write `tests/Feature/Imports/ValidationCategoriesTest.php` asserting each of the eight categories in [contracts/imports.md](./contracts/imports.md) produces the right row status and localized reason key (FR-057, FR-062, FR-068)
- [ ] T211 [P] [US5] Write `tests/Feature/Imports/PreviewTest.php` asserting the create-versus-match counts, the warning and error counts, the first 100 rows each with a status, and 409 while validation is unfinished (FR-061)
- [ ] T212 [P] [US5] Write `tests/Feature/Imports/ChunkedCommitTest.php` asserting commit runs in 500-row transactional chunks and that existing records are linked rather than duplicated (FR-064)
- [ ] T213 [P] [US5] Write `tests/Feature/Imports/ResumeAfterChunkFailureTest.php` failing a chunk mid-commit, asserting `last_committed_row_id` sits at the last **successful** chunk, committed rows survive, and a resume completes the remainder (FR-065)
- [ ] T214 [P] [US5] Write `tests/Feature/Imports/DuplicateFileHashTest.php` asserting a matching `file_hash` for the same term warns via `duplicate_of`, and that continuing creates **zero** new records (FR-067, SC-008)
- [ ] T215 [P] [US5] Write `tests/Unit/DuplicateDetectionTest.php` asserting all four levels — University ID, section key, student + section, student + assignment — are each enforced by a unique index, and that a per-row violation becomes an error reason without failing its chunk (FR-066)
- [ ] T216 [P] [US5] Write `tests/Feature/Imports/ErrorReportTest.php` asserting the `.xlsx` contains row number, column, submitted value, and reason for **every** rejected row with none missing, Arabic-safe (FR-071, SC-009)
- [ ] T217 [P] [US5] Write `tests/Feature/Imports/QueueUnavailableTest.php` asserting that with no worker the job stays `validating` or `committing` and the response says the import is waiting rather than reporting a failure — the state the reference kit does not cover ([ui-conflicts.md](./ui-conflicts.md) C-13)
- [ ] T218 [P] [US5] Write `tests/Authorization/ImportEndpointsTest.php` with allow and deny cases for all 9 endpoints in contracts/imports.md
- [ ] T219 [P] [US5] Write `tests/Performance/ImportThroughputTest.php` asserting 10,000 rows validate and commit within 5 minutes (SC-007), in the `performance` group, excluded from the default run
- [ ] T220 [P] [US5] Write `tests/Visual/us5.spec.ts` screenshotting all six wizard steps plus the imports list, the re-upload warning, the row-reason table, the report, and the **queue-waiting** state against the `ImportScreens` reference, both locales; the queue-waiting baseline has no React counterpart and is baselined from the Blade implementation for design review (C-13)
- [ ] T221 [US5] Write `tests/Feature/Imports/CentralModeRejectsRegistrarFileTest.php` asserting that the actual registrar export at `design-system/uploads/filedownloadservlet(21).xlsx` fed to the **central** mode fails with clear per-target reasons naming each of FR-052's ten required targets it cannot supply, and points the operator at the registrar mode — rather than crashing or importing partial nonsense. *(C-09 was decided on 2026-09-27: the registrar mode now handles this file properly, so this task's original purpose — gathering evidence for that decision — is served. It remains as a regression guard on the central mode's failure behaviour. The successful end-to-end import of the same file is T325.)*

### Backend implementation for User Story 5

- [ ] T222 [P] [US5] Implement `app/Models/ImportJob.php`, `ImportRow.php`, and `ImportError.php` with their relations and status enums
- [ ] T223 [US5] Implement `app/Modules/Imports/Pipeline/FileValidator.php` checking extension, MIME, magic bytes, size against `import_max_file_mb`, and row count against `import_max_rows`, all read from settings — rejecting before storage
- [ ] T224 [US5] Implement `app/Modules/Imports/Actions/UploadImport.php` storing the file on the private disk, computing the SHA-256 `file_hash`, detecting a prior matching import for the same term as `duplicate_of`, creating the job in `uploaded`, and auditing `import.uploaded`
- [ ] T225 [US5] Implement `app/Modules/Imports/Pipeline/HeaderReader.php` and `ColumnMapper.php` streaming only the header row and proposing a mapping by normalized name match
- [ ] T226 [US5] Implement `PUT /imports/{id}/mapping` in `ImportMappingController.php`, persisting the confirmed mapping and the `create_missing_courses` and `create_missing_instructors` options, refusing an unmapped required target
- [ ] T227 [US5] Implement `app/Modules/Imports/Pipeline/RowValidator.php` detecting all eight categories from contracts/imports.md, using `UniversityId` from T183 and a running hash set for in-file duplicates
- [ ] T228 [US5] Implement `app/Modules/Imports/Jobs/ValidateImportJob.php` streaming the file with OpenSpout and writing one `import_rows` row per source row with `raw`, `resolved`, `status`, and localized `reasons` — **writing to no core table**
- [ ] T229 [US5] Implement `POST /imports/{id}/validate` returning 202 and dispatching the job, with 409 when the job is not in `mapped` state
- [ ] T230 [US5] Implement `GET /imports/{id}/preview` reading counts and the first 100 rows from `import_rows`, with 409 while validation is unfinished
- [ ] T231 [US5] Implement `app/Modules/Imports/Pipeline/Resolver.php` resolving Branch → Course → Section → Instructor → Student → Enrollment, honouring the create-or-skip options and keeping the stored student name on a mismatch
- [ ] T232 [US5] Implement `app/Modules/Imports/Jobs/CommitImportChunkJob.php` as a self-chaining job walking `import_rows` by id in 500-row chunks, each chunk one `DB::transaction()` that advances `last_committed_row_id` **as its final statement**, catching per-row unique violations as error reasons without failing the chunk
- [ ] T233 [US5] Implement `POST /imports/{id}/commit` requiring `confirm: true`, returning 202, refusing with 409 unless the job is `validated` and the preview was fetched, and auditing `import.committed` with filename, counts, and actor
- [ ] T234 [US5] Implement `POST /imports/{id}/resume` restarting from `id > last_committed_row_id`, with 409 unless the job is `failed`, auditing `import.resumed`
- [ ] T235 [US5] Implement `GET /imports/{id}` returning status, the five counters, `last_committed_row_id`, `failure_reason`, timings, and `progress_percent` for the 5-second poller
- [ ] T236 [US5] Implement `app/Modules/Imports/Exports/ImportErrorReportExport.php` materializing `import_errors` from `import_rows` and streaming an Arabic-safe `.xlsx`, exposed as `GET /imports/{id}/errors/download` and auditing `import.error_report_downloaded`
- [ ] T237 [P] [US5] Implement `app/Modules/Imports/Policies/ImportJobPolicy.php` mapping each endpoint to `import.central.run`, `import.section.run`, or `import.view`, and requiring job ownership
- [ ] T238 [P] [US5] Store a notification row on queued-job completion in `app/Modules/Imports/Notifications/ImportFinished.php` so the later notification-centre feature surfaces it without rework — **no email is sent in this feature**

### Screens for User Story 5 — the six-step wizard

- [ ] T239 [US5] Build `resources/views/imports/index.blade.php` to the `ImportsScreen` reference — `x-track-data-table` of past jobs with mode, file, actor, timestamp, `x-track-status-badge` for the seven job states, the five counters as numerics, a **Resume** ghost button on `failed` rows, and an error-report download button where `errors > 0`
- [ ] T240 [US5] Build `resources/views/imports/_wizard.blade.php` as the shared wizard frame — `x-track-stepper` with the six approved Arabic step labels (رفع الملف · مطابقة الأعمدة · الفحص · المعاينة · الاستيراد · التقرير) and the `x-track-card` body, driven by `resources/js/components/import-wizard.js`
- [ ] T241 [US5] Build step 1 `resources/views/imports/step-upload.blade.php` — drag-and-drop zone with the accepted types and limits stated up front, `FileMeta` panel showing name and size after selection, term `x-track-select` for central mode, and the `duplicate_of` re-upload `x-track-alert` tone warning
- [ ] T242 [US5] Build step 2 `resources/views/imports/step-mapping.blade.php` — a two-column mapping table pairing each required target with a source-column `x-track-select` pre-filled from the suggestion, required targets marked, the create-or-skip `x-track-switch` pair for unknown courses and unregistered instructors, and a disabled Continue until every required target is mapped
- [ ] T243 [US5] Build step 3 `resources/views/imports/step-validating.blade.php` — `x-track-progress` with the 5-second poller, a row counter, and the **queue-waiting** `x-track-alert` tone info shown when the job has not been picked up, saying the import is waiting and will be reported when it finishes (C-13)
- [ ] T244 [US5] Build step 4 `resources/views/imports/step-preview.blade.php` — a summary grid of `x-track-stat-card` tiles for will-create versus will-match versus warnings versus errors, then `x-track-data-table` of the first 100 rows with a per-row `x-track-status-badge` (valid / warning / error) and its localized reasons, and a confirmation `x-track-dialog` restating the counts before commit (FR-082)
- [ ] T245 [US5] Build step 5 `resources/views/imports/step-committing.blade.php` — `x-track-progress` with the chunk counter ("الدفعة 2 من 4") and the same poller
- [ ] T246 [US5] Build step 6 `resources/views/imports/step-report.blade.php` — the four approved counters (processed · imported · duplicates · errors), the error-report download button, and a success `x-track-toast`
- [ ] T247 [P] [US5] Add the `imports` translation keys — including every `import.*` row-reason key from contracts/imports.md and the queue-waiting copy — to `lang/ar/imports.php` and `lang/en/imports.php`

**Checkpoint**: A real 5,000-row roster loads end to end with an error report — the PRD Phase 1 gate's second check.

---

## Phase 9: User Story 6 - Instructors Load Their Own Section Lists (Priority: P6)

**Goal**: An instructor imports a two-column student list into a section they teach, through the same validated six steps, and cannot touch anyone else's section.

**Independent Test**: As an instructor, upload a file with one new student, one existing student whose name differs, and one duplicate row; then attempt the same against another instructor's section.

### Tests for User Story 6

- [ ] T248 [P] [US6] Write `tests/Feature/Imports/SectionModeImportTest.php` asserting the same six steps apply, nothing is saved before confirmation, and only `university_id` and `student_name` are required
- [ ] T249 [P] [US6] Write `tests/Feature/Imports/LinkExistingStudentTest.php` asserting an existing student is linked rather than duplicated, and that a differing name keeps the stored value and raises a warning (FR-069)
- [ ] T250 [P] [US6] Write `tests/Feature/Imports/InFileDuplicateRejectedTest.php` asserting a repeated student in one file has its second occurrence rejected naming its row number
- [ ] T251 [P] [US6] Write `tests/Scope/InstructorCannotImportForeignSectionTest.php` asserting **404** plus an `access.denied` audit entry when an instructor targets a section they neither teach nor hold a grant for (FR-054)
- [ ] T252 [P] [US6] Write `tests/Feature/Sections/InstructorWithNoSectionsTest.php` asserting the empty state naming whom to contact, not a blank page
- [ ] T253 [P] [US6] Write `tests/Visual/us6.spec.ts` screenshotting the section-page import entry, the two-column template download, and the instructor's empty state against the `SectionFilePicker` reference, both locales

### Implementation for User Story 6

- [ ] T254 [US6] Implement `POST /sections/{id}/imports` in `app/Modules/Imports/Http/Controllers/SectionImportController.php`, resolving the section through the scoped query so a foreign section yields 404, and creating the job with `mode: section`
- [ ] T255 [US6] Extend `app/Modules/Imports/Pipeline/RowValidator.php` with the section-mode rule set — `university_id` and `student_name` only, with the section fixed by the job rather than read from the file
- [ ] T256 [US6] Extend `app/Modules/Imports/Pipeline/Resolver.php` for section mode: attach students to the one job section only, never creating or resolving a section from file values
- [ ] T257 [P] [US6] Build `resources/views/sections/import.blade.php` to the `SectionFilePicker` reference, reusing the wizard frame with a two-column template download button and the section fixed in the header
- [ ] T258 [P] [US6] Add the instructor-facing `imports` and `sections` empty-state translation keys to `lang/ar/` and `lang/en/`

**Checkpoint**: Instructors are unblocked from the central office for routine roster corrections.

---

## Phase 10: User Story 7 - Everyone Works In Their Own Language (Priority: P7)

**Goal**: Every screen is complete, correctly mirrored, accessible, and behaviourally identical in Arabic and English, with the design system's states applied throughout.

**Independent Test**: Switch language on each delivered screen and confirm full translation, correct mirroring, and identical behaviour; then confirm every status carries a label and every form validates, loads, errors, and confirms as specified.

**Note**: This story is cross-cutting — its components ship in Phase 3 and its strings ship with each screen. This phase is the sweep that verifies and closes the gaps.

### Tests for User Story 7

- [ ] T259 [P] [US7] Write `tests/Feature/Localization/EveryScreenRendersBothDirectionsTest.php` asserting every registered `web` route renders in both `ar` and `en` with the correct `lang` and `dir` attributes and the matching stylesheet
- [ ] T260 [P] [US7] Write `tests/Feature/Localization/NoUntranslatedOutputTest.php` asserting no rendered screen contains a raw translation key or a fallback-language string while the other locale is active
- [ ] T261 [P] [US7] Write `tests/Feature/Ux/StatusBadgeRequiresLabelTest.php` asserting `x-track-status-badge` cannot render colour without a text label (FR-077)
- [ ] T262 [P] [US7] Write `tests/Feature/Ux/ConfirmationRequiredTest.php` asserting student withdrawal, account deactivation, instructor change, and term status change each require a confirmation restating the impact (FR-082)
- [ ] T263 [P] [US7] Write `tests/Feature/Ux/ValidationStatesTest.php` asserting server-side rules repeat every client-side rule and that errors render beside their field in the active language (FR-078)
- [ ] T264 [P] [US7] Write `tests/Visual/screens-all.spec.ts` running the full screen regression across all 20 in-scope screens × both locales × 1280 / 1440 / 1920 / 390, at ≤ 1% pixel difference against the reference ([design-system.md](./design-system.md) §13)
- [ ] T265 [P] [US7] Write `tests/Visual/axe-all.spec.ts` running axe-core over all 20 screens in both locales, requiring **zero** WCAG 2.1 AA violations (SC-014)
- [ ] T266 [P] [US7] Write `tests/Visual/rtl-mirroring.spec.ts` asserting per screen that the sidebar sits right in RTL and left in LTR, breadcrumb chevrons point correctly, button icons sit on the correct side, and the toast anchors to the correct corner
- [ ] T267 [P] [US7] Write `tests/Performance/PageLoadTest.php` asserting first contentful render under 2 seconds at p95 (SC-015), in the `performance` group, excluded from the default run

### Implementation for User Story 7

- [ ] T268 [US7] Sweep every Blade view for hard-coded user-facing text, moving each string into `lang/ar/` and `lang/en/` until `NoHardcodedStringsTest` and `LocalizationParityTest` both pass
- [ ] T269 [US7] Fix every RTL mirroring defect `rtl-mirroring.spec.ts` reports, in `resources/css/app-rtl.scss` and the affected views, using CSS logical properties rather than direction-specific overrides wherever Bootstrap allows
- [ ] T270 [P] [US7] Implement `app/Modules/Shared/Support/Format.php` emitting Western Arabic digits 0-9 and `DD/MM/YYYY` in both locales, plus the "2026/2027 - الأول" term format from the design system's content rules, and apply it to every rendered number and date (FR-084)
- [ ] T271 [P] [US7] Verify and correct the responsive behaviour at 1280 and 1920, and the <768px stacked-card and drawer behaviour, against the design system's layout rules (FR-085)
- [ ] T272 [US7] Complete WCAG 2.1 AA and full keyboard operation across every screen — focus order, the `--green-600` visible focus ring, ARIA labels on every icon-only control, dialog focus trapping and restoration, and the shell skip link — until `axe-all.spec.ts` is clean (SC-014)
- [ ] T273 [P] [US7] Verify the loading, error, success, empty, and confirmation states are applied uniformly across all 20 screens against the §10 state matrix, and fix every gap
- [ ] T274 [P] [US7] Build `resources/views/profile/preferences.blade.php` with the locale control persisting `users.locale`, matching the topbar switch's approved segmented-control styling

**Checkpoint**: All seven stories are independently functional, visually faithful, and accessible in both languages.

---

## Phase 11: Polish & Cross-Cutting Concerns

- [ ] T275 [P] Implement structured JSON application logging with request id, user id, and route in `app/Modules/Shared/Logging/` and `config/logging.php` (PRD 8.8)
- [ ] T276 [P] Implement the `/health` endpoint in `app/Modules/Shared/Http/Controllers/HealthController.php` checking database, Redis, queue workers, and storage
- [ ] T277 Implement `app/Modules/Shared/Http/Middleware/ContentSecurityPolicy.php` with a strict policy and register it on the `web` group — and confirm it needs **no** exemption for `unpkg.com`, `cdn.jsdelivr.net`, or `fonts.googleapis.com`, since fonts, icons, and scripts are all self-hosted (T058, T059, T066)
- [ ] T278 [P] Verify every state-changing route carries CSRF protection, and add `tests/Feature/Security/CsrfCoverageTest.php`
- [ ] T279 Run the OWASP Top 10 review against this feature's surface and record the findings in `docs/security-review-001.md`
- [ ] T280 [P] Write the deployment runbook in `docs/runbook-deploy.md` covering the Supervisor worker configuration, the audit-table grant verification for staging and production, backup and restore, the 5-year audit retention policy, and the asset build step that copies `design-system/assets/`
- [ ] T281 [P] Write `docs/architecture-001.md` recording the decisions from [research.md](./research.md) and the six design-system decisions from plan.md, so they survive the key-person risk in PRD R-05
- [ ] T282 Request vector originals (SVG or AI/EPS) for the logo set from the brand author, per [ui-conflicts.md](./ui-conflicts.md) C-06 — the shipped assets are ~3× raster upscales and Phase 3's PDF export headers will show it
- [ ] T283 Walk the design author through `design-system/ACCESSIBILITY-DELTA.md` and obtain their acknowledgement of the 7 palette corrections (C-17)
- [ ] T284 Execute the full gate from [quickstart.md](./quickstart.md): `pint --test`, `phpstan analyse`, `php artisan test`, `php artisan test --group=performance`, `--testsuite=Architecture`, and the Playwright visual + axe suites
- [ ] T285 Walk quickstart.md validation scenarios **1-10** (Phases 1-17 scope; Scenarios 11-21 are verified by T462) manually and record the outcome against the four PRD Phase 1 gate checks and the seven constitution Definition-of-Done items

---
## Phase 12: Typed EAV Capacity Spike

**Ungated 2026-09-27.** The Product Owner confirmed that the PRD's 15 dynamic field types stand and
that the UI's ordered stages are one preset of Yes/No fields ([ui-conflicts.md](./ui-conflicts.md)
C-08). The spike therefore measures the design that will ship, and runs as originally specified.

Runs in parallel with Phases 4–11; depends only on Phase 2. **Must complete before this feature is
accepted**, because Phase 2 planning depends on the answer.

**⚠️ These tables carry no controller, route, policy, or view in this feature.** Containment is
asserted by T291.

- [ ] T286 [P] Create migrations for `initiatives`, `initiative_schema_versions` (**unique `(initiative_id, version_number)`**), `initiative_fields` (**unique `(schema_version_id, field_key)`**), and `initiative_sections` (**unique `(initiative_id, section_id)`**) per [data-model.md](./data-model.md) Group 6
- [ ] T287 [P] Create migrations for `initiative_targets` (**unique `(assignment_id, student_id)`**), `student_initiative_records` (**unique `(assignment_id, student_id)`**), and `student_initiative_values` (**unique `(record_id, field_id)`**) with the typed columns `value_text`, `value_number`, `value_date`, `value_bool`, `value_json`, and `attachment_id`
- [ ] T288 Add the two indexes under measurement — `student_initiative_values(field_id, value_bool)` and `student_initiative_records(assignment_id, is_complete)` — as a separate toggleable migration, since whether they ship is decided by the spike result
- [ ] T289 Implement `database/seeders/EavSpikeSeeder.php` generating, with chunked raw multi-row inserts rather than Eloquent, 1 term, 6 branches, 3,000 sections, 30,000 students, 30,000 enrollments, 10 initiatives × 5 schema versions, 50 field definitions, 30,000 records, and **1,500,000** values distributed ≈45% `value_text`, ≈25% `value_number`, ≈15% `value_bool`, ≈10% `value_date`, ≈5% `value_json`
- [ ] T290 Implement `tests/Performance/EavSpikeTest.php` measuring each of the five queries in [research.md](./research.md) R-06 as p95 over 10 cold runs against its threshold: assignment grid of 200 records × 15 fields pivoted **< 2 s**; per-section completion counts across ≈3,000 sections **< 3 s**; initiative-wide student-weighted completion at 30,000 targets **< 3 s**; full recalculation sweep over 30,000 records **< 2 min**; single value upsert **< 500 ms**
- [ ] T291 [P] Write `tests/Architecture/EavSpikeContainmentTest.php` asserting no route, controller, policy, or Blade view in this feature references the Group 6 tables
- [ ] T292 Write `specs/001-foundation-access-roster/spike-eav-results.md` recording the schema measured, the index set, row counts, `EXPLAIN` output per query, measured p95s, and a PASS/FAIL per threshold
- [ ] T293 On any FAIL, record the finding and the proposed remedy in `spike-eav-results.md` before Phase 2 planning begins, selecting from the ranked candidates in research.md R-06 — and state the reason if a new remedy is proposed instead (FR-088)

---

# Amendment 1 (2026-09-27) — accepted UI additions

All five previously blocked items were accepted by the Product Owner. Requirements are
[spec.md](./spec.md) FR-089 … FR-113 and User Story 8; the decision record is
[ui-conflicts.md](./ui-conflicts.md).

**These phases interleave with the phases above rather than following them.** Each states its
insertion point. The table in "Dependencies & Execution Order" below gives the full merged order.

| Amendment phase | Adds | Interleaves with |
|---|---|---|
| **13** | Programs + new columns: migrations, models, seeders | **Phase 2** (schema) — run together |
| **14** | Programs endpoints and screens | **Phase 7 / US4** — run together |
| **15** | Registrar import mode | **Phase 8 / US5** — after T233 |
| **16** | Course and staff bulk import (**User Story 8**) | After **Phase 9 / US6** |
| **17** | `last_login_at` | **Phase 4 / US1** — with T116 |

---

## Phase 13: Programs & New Columns — Schema *(interleaves with Phase 2)*

- [ ] T294 Create the `programs` migration with `organization_id`, `code` varchar(20) stored uppercase with **unique `(organization_id, code)`**, `name_ar` and `name_en` varchar(255) both required, `unit` enum(`admin`,`tech`), `is_active` boolean default true, and `deleted_at` (FR-089)
- [ ] T295 Create the `course_program` pivot migration with `course_id` and `program_id` cascade foreign keys and **unique `(course_id, program_id)`** (FR-091)
- [ ] T296 Add `program_id` to the `sections` migration as a **non-nullable** FK → `programs.id` with **restrict** on delete, plus an index `(program_id)`. Non-nullable from the start is deliberate: adding it after 3,000 sections exist would require a backfill with no source of truth (FR-090, FR-092, FR-094)
- [ ] T297 [P] Add `degree` varchar(40) default `bachelor` and `activity` varchar(40) default `lecture` to the `sections` migration — **not** database enums, so adding a value later needs no migration (FR-108, FR-109, constitution IV)
- [ ] T298 [P] Add `academic_status` enum(`regular`,`withdrawn`,`excused`) nullable and `roster_sequence` int unsigned nullable to the `section_enrollments` migration, **separate from** `status` (FR-110, FR-112)
- [ ] T299 [P] Extend the `import_jobs` migration: `mode` enum gains `registrar`, `course`, `user`; add nullable `instructor_id` FK → `users.id` and `file_metadata` json (FR-096, FR-101, FR-103, FR-104)
- [ ] T300 Create `app/Models/Program.php` with `SoftDeletes`, the `Auditable` trait, the `courses` belongs-to-many and `sections` has-many relations, and a `scopeActive()`
- [ ] T301 [P] Add the `program` belongs-to relation to `app/Models/Section.php` and the `programs` belongs-to-many to `app/Models/Course.php`
- [ ] T302 Extend `database/seeders/PermissionSeeder.php` with `program.view`, `program.create`, `program.edit`, `import.registrar.run`, `import.course.run`, and `import.user.run` — **42 permissions total** — and grant all six to `SUPER_ADMIN`, plus `program.view` to `BRANCH_COORDINATOR`
- [ ] T303 [P] Extend `database/seeders/SettingSeeder.php` with `section_degrees` = `[{"value":"bachelor","ar":"البكالوريوس","en":"Bachelor"},{"value":"intermediate_diploma","ar":"الدبلوم المتوسط","en":"Intermediate Diploma"}]` and `section_activities` = `[{"value":"lecture","ar":"محاضرة","en":"Lecture"}]`
- [ ] T304 [P] Implement `app/Modules/Shared/Rules/InSetting.php`, a validation rule checking a value against a named settings list — used by the section form, the section import, and the registrar import for both `degree` and `activity` (FR-108, FR-109)
- [ ] T305 [P] Extend `database/seeders/UiFixtureSeeder.php` and the factories with the 9 programs from `design-system/ui_kits/web-app/store.js` (HR, ACC, LOG, MKT, CS, APP, GD, RE, EC with their admin/tech units), their course associations, each section's program, and the degree, activity, academic-status, and sequence values the reference screens display

---

## Phase 14: Programs — Endpoints & Screens *(interleaves with Phase 7 / US4)*

### Tests

- [ ] T306 [P] [US4] Write `tests/Feature/Programs/ProgramCrudTest.php` covering creation, the duplicate-code refusal naming the code, editing, and deactivation (FR-089, FR-092)
- [ ] T307 [P] [US4] Write `tests/Feature/Programs/CannotDeleteProgramWithSectionsTest.php` asserting permanent removal is refused at both the route level (no `DELETE` exists) and the database level (restrict FK), and that deactivation is offered instead (FR-092, SC-021)
- [ ] T308 [P] [US4] Write `tests/Feature/Programs/CourseProgramAssociationTest.php` asserting a course associates with several programs and a program holds several courses (FR-091)
- [ ] T309 [P] [US4] Write `tests/Feature/Sections/SectionRequiresProgramTest.php` asserting section creation without `program_id` is refused, that `program_id` cannot be set to null on edit, and that **zero** sections exist without a program after the roster import (FR-090, SC-019)
- [ ] T310 [P] [US4] Write `tests/Feature/Sections/DegreeAndActivityValidationTest.php` asserting a value outside the settings-defined set is refused, and that adding a new value to the setting makes it immediately valid with no migration (FR-108, FR-109)
- [ ] T311 [P] [US4] Write `tests/Feature/Sections/DeactivatedProgramTest.php` asserting a deactivated program is refused on section create but retained and displayed on an existing section (spec Edge Cases)
- [ ] T312 [P] [US4] Write `tests/Authorization/ProgramEndpointsTest.php` with allow and deny cases for all 5 program endpoints in [contracts/academic.md](./contracts/academic.md)
- [ ] T313 [P] [US4] Write `tests/Visual/programs.spec.ts` screenshotting the programs list and its create/edit dialog against the `ProgramsScreen` reference, both locales, all four widths

### Implementation

- [ ] T314 [US4] Implement `app/Modules/AcademicStructure/Http/Controllers/ProgramController.php` with `GET /programs`, `GET /programs/{id}`, `POST /programs`, `PUT /programs/{id}`, and `PUT /programs/{id}/courses`, uppercasing the code and refusing duplicates with 422
- [ ] T315 [P] [US4] Implement `app/Modules/AcademicStructure/Policies/ProgramPolicy.php` mapping each action to its `program.*` permission, and register it. **Note**: `Program` takes **no** scope trait — it is institution-wide reference data with no branch dimension, so a coordinator may read programs while still seeing only their own branches' sections within them (plan.md Amendment 1 re-check)
- [ ] T316 [P] [US4] Add the `program.created`, `program.updated`, `program.deactivated`, and `program.courses_changed` action keys to the audit label translations in `lang/ar/audit.php` and `lang/en/audit.php`
- [ ] T317 [US4] Build `resources/views/programs/index.blade.php` to the `ProgramsScreen` reference — `x-track-page-header` with the subtitle "كل شعبة في الكلية مرتبطة ببرنامج أكاديمي واحد", a create action and an import-courses action, and `x-track-data-table` showing code in LTR bold, both names, specialty unit, course count, section count, and an active `x-track-status-badge`
- [ ] T318 [US4] Build `resources/views/programs/_dialog.blade.php` to the `ProgramsScreen` dialog reference — `x-track-dialog` at 560px with a two-column grid: code in LTR, specialty-unit `x-track-select`, both names, an active `x-track-switch` spanning both columns, and the cannot-delete `x-track-alert` tone info shown when editing
- [ ] T319 [US4] Add the **required** program `x-track-select` to `resources/views/sections/_dialog.blade.php`, plus the degree and activity selects populated from settings, and add the program column and filter to `resources/views/sections/index.blade.php` (FR-094)
- [ ] T320 [P] [US4] Add both statuses to the section roster table in `resources/views/sections/show.blade.php` — TRACK's `status` and the registrar's `academic_status` as **separate** columns, each with its own `x-track-status-badge`, plus `roster_sequence` as the leading numeric column where present (FR-111)
- [ ] T321 [P] [US4] Add the `programs` translation keys to `lang/ar/` and `lang/en/`, and restore the `programs` entry to `config/navigation.php` for `SUPER_ADMIN` with the `graduation-cap` icon, matching the approved sidebar

---

## Phase 15: Registrar Import Mode *(interleaves with Phase 8 / US5, after T233)*

### Tests

- [ ] T322 [P] [US5] Write `tests/Feature/Imports/RegistrarMetadataBlockTest.php` asserting the metadata block above the header row is parsed into campus, degree, course, activity, and section number, and that a file without one is refused at upload with a message naming the other modes (FR-097, spec Edge Cases)
- [ ] T323 [P] [US5] Write `tests/Feature/Imports/ResolveBranchFromCampusTest.php` asserting "المجمعة (طلاب)" resolves to exactly one branch, and that a campus resolving to none or to several rejects the file before any row is processed (FR-099)
- [ ] T324 [P] [US5] Write `tests/Feature/Imports/RegistrarCourseMatchTest.php` asserting an Arabic course-name match succeeds unambiguously, that no match rejects the file, and that several matches flag the rows for the operator rather than guessing (FR-100)
- [ ] T325 [P] [US5] Write `tests/Feature/Imports/RegistrarRealFileTest.php` importing `design-system/uploads/filedownloadservlet(21).xlsx` **unmodified** end to end and asserting the 19 students land in the resolved section with their academic status and sequence — this supersedes T221, which only recorded why the file could not be mapped to the central mode (SC-020)
- [ ] T326 [P] [US5] Write `tests/Feature/Imports/AcademicStatusNeverOverwritesWithdrawalTest.php` asserting that importing a registrar status of `regular` over a TRACK-withdrawn enrollment leaves `status`, `withdrawn_at`, and `withdraw_reason` untouched, and that a TRACK withdrawal leaves `academic_status` untouched (FR-111, SC-022)
- [ ] T327 [P] [US5] Write `tests/Authorization/RegistrarImportEndpointTest.php` with allow and deny cases for `POST /imports/registrar`
- [ ] T328 [P] [US5] Write `tests/Visual/us5-registrar.spec.ts` screenshotting the registrar upload step and its parsed-metadata confirmation panel. **No React reference exists** — the mode was accepted after the design system was produced — so the baseline is taken from the Blade implementation and reviewed by the design author, as the queue-waiting state is ([design-system.md](./design-system.md) §9 row 26)

### Implementation

- [ ] T329 [US5] Implement `app/Modules/Imports/Pipeline/MetadataBlockReader.php` streaming the rows above the header row and extracting campus, degree, course, activity, and section number by Arabic label match (FR-097)
- [ ] T330 [US5] Implement `app/Modules/Imports/Pipeline/ResolveBranchFromCampus.php` splitting a campus string such as "المجمعة (طلاب)" into campus and gender and resolving it to exactly one branch, returning a distinct no-match and ambiguous-match result so the caller can reject with the right reason (FR-099)
- [ ] T331 [US5] Implement `POST /imports/registrar` in `app/Modules/Imports/Http/Controllers/RegistrarImportController.php` requiring `term_id` and `instructor_id`, parsing and resolving at upload so the operator sees what the file was understood to mean before mapping, and returning the `file_metadata` and `resolved` payloads (FR-096, FR-101)
- [ ] T332 [US5] Implement the registrar mode in `app/Modules/Imports/Modes/RegistrarMode.php` — four data columns (sequence, university id, student name, academic status), no mapping step needed beyond confirmation, and the three additional row-level reason categories from [contracts/imports.md](./contracts/imports.md) (FR-098)
- [ ] T333 [US5] Extend `app/Modules/Imports/Pipeline/Resolver.php` for registrar mode: write `academic_status` and `roster_sequence` on the enrollment and **never** touch `status`, `withdrawn_at`, or `withdraw_reason` (FR-111)
- [ ] T334 [P] [US5] Build `resources/views/imports/step-upload-registrar.blade.php` — the upload zone plus an `x-track-card` confirmation panel showing the parsed campus, degree, course, activity, and section alongside the resolved branch, course, and section, with term and instructor `x-track-select` controls, and `x-track-alert` danger for an unresolvable campus or course
- [ ] T335 [P] [US5] Add the registrar-mode translation keys — including `import.ambiguous_course`, `import.unknown_academic_status`, `import.invalid_sequence`, and the not-a-registrar-export message — to `lang/ar/imports.php` and `lang/en/imports.php`

---

## Phase 16: User Story 8 - Courses And Staff Load In Bulk (Priority: P8) *(after Phase 9 / US6)*

**Goal**: Courses and staff accounts load through the same six-step wizard, with created accounts unusable until their password is reset.

**Independent Test**: Upload a course file with one new course, one existing code, and one unknown program; then a staff file with one new instructor, one duplicate email, and one coordinator with no branch. Confirm nothing is written before confirmation and every created account is refused sign-in.

### Tests for User Story 8

- [ ] T336 [P] [US8] Write `tests/Feature/Imports/CourseImportTest.php` asserting new courses are created, existing codes are matched rather than duplicated, and program associations are applied (FR-103)
- [ ] T337 [P] [US8] Write `tests/Feature/Imports/CourseImportProgramCasesTest.php` asserting an unknown program flags the row for create-or-skip and a deactivated program associates only on confirmation (spec Edge Cases)
- [ ] T338 [P] [US8] Write `tests/Feature/Imports/UserImportTest.php` asserting accounts are created with their role and, for coordinators, their branches (FR-104)
- [ ] T339 [P] [US8] Write `tests/Feature/Imports/UserImportUnusableUntilResetTest.php` asserting **every** created account is refused sign-in until a Super Admin reset sets a real hash (FR-105, SC-024)
- [ ] T340 [P] [US8] Write `tests/Feature/Imports/UserImportRejectionsTest.php` asserting a duplicate email — including against a soft-deleted account — a coordinator with no branch, an unknown role, and an unknown branch are each rejected with their own reason (FR-104, FR-005, FR-004)
- [ ] T341 [P] [US8] Write `tests/Authorization/BulkImportEndpointsTest.php` with allow and deny cases for `POST /imports/courses` and `POST /imports/users`, including that an **instructor** is refused both (FR-107)
- [ ] T342 [P] [US8] Write `tests/Performance/BulkImportTest.php` asserting 40 courses and 30 staff accounts load in under 10 minutes total (SC-023), in the `performance` group, excluded from the default run
- [ ] T343 [P] [US8] Write `tests/Visual/us8.spec.ts` screenshotting both wizard entry points against the `CourseImportDialog` and `UserImportDialog` references, both locales

### Implementation for User Story 8

- [ ] T344 [US8] Implement the course mode in `app/Modules/Imports/Modes/CourseMode.php` — column contract (code, name AR, name EN, programs), the four row-level reason categories from contracts/imports.md, and resolution that links existing codes and applies program associations
- [ ] T345 [US8] Implement `POST /imports/courses` in `app/Modules/Imports/Http/Controllers/CourseImportController.php` behind `import.course.run`
- [ ] T346 [US8] Implement the user mode in `app/Modules/Imports/Modes/UserMode.php` — column contract (name AR, name EN, email, role, branches), its four row-level reason categories, and account creation with an **unusable** password hash and `first_login_at` null (FR-105)
- [ ] T347 [US8] Implement `POST /imports/users` in `app/Modules/Imports/Http/Controllers/UserImportController.php` behind `import.user.run`
- [ ] T348 [P] [US8] Build `resources/views/imports/step-upload-courses.blade.php` and `step-upload-users.blade.php` to the `CourseImportDialog` and `UserImportDialog` references, reusing the wizard frame with a template-download button for each shape
- [ ] T349 [P] [US8] Add the course-import entry point to `resources/views/programs/index.blade.php` as the "استيراد المقررات من Excel" secondary action the reference shows, and the staff-import entry point to `resources/views/users/index.blade.php`
- [ ] T350 [P] [US8] Add the course- and user-import translation keys, including every new reason key, to `lang/ar/imports.php` and `lang/en/imports.php`

**Checkpoint**: Term setup no longer requires typing each course and account by hand.

---

## Phase 17: `last_login_at` *(interleaves with Phase 4 / US1, with T116)*

- [ ] T351 [P] [US1] Write `tests/Feature/Authentication/LastLoginTest.php` asserting `last_login_at` is updated on every successful sign-in, is not set by a failed attempt, and renders on the accounts list (FR-113)
- [ ] T352 [US1] Set `last_login_at` in `app/Modules/Authentication/Http/Controllers/LoginController.php` on every successful sign-in, alongside the existing `first_login_at` handling
- [ ] T353 [P] [US1] Add the `last_login_at` column to `resources/views/users/index.blade.php` as "آخر دخول" formatted through `Format::dateTime()`, and to the profile screen as the reference's "آخر دخول" line

---
---

# Amendment 2 (2026-09-27) — student technical support channel

Merged in from what was briefly a separate feature, `002-student-support`, at the Product Owner's
direction on 2026-09-27. Decisions:
[specs/changes/CR-002-student-support.md](../changes/CR-002-student-support.md). Requirements:
[spec.md](./spec.md) FR-114 … FR-169, User Stories 9–13, SC-025 … SC-038. Governing rules:
constitution v1.1.0's "Public endpoints" subsection.

**Phases 18–25 run after Phase 24 (User Story 7), not interleaved.** Every one of them depends on
the roster, the branch scope, the `Auditor`, the settings facade, and the component library that
Phases 1–17 build. The support channel is **additive**: the PRD's Phase 1 acceptance gate does not
depend on it, so if the 8-week schedule tightens, this is the block to defer.

**⚠️ One task in Phase 4 changes because of this merge**: T127 (the sign-in screen) must now carry
the link to `/support` behind a feature flag. See T463 in Phase 25.

## Phase 18: Setup

**Purpose**: Module skeleton and the public route boundary

- [ ] T354 Create the module skeleton at `app/Modules/Support/` with subdirectories `Http/Controllers`, `Http/Controllers/Public`, `Http/Requests`, `Http/Middleware`, `Actions`, `Services`, `Services/Challenge`, `Policies`, and `Tests`, and register it in `app/Providers/ModuleServiceProvider.php`
- [ ] T355 Create `routes/public.php` and register it in `bootstrap/app.php` on the `web` middleware group **without** `auth:sanctum`, deliberately separate from `routes/web.php` so the entire public surface of the platform is readable in one short file
- [ ] T356 [P] Register the `support` translation namespace and create empty `lang/ar/support.php` and `lang/en/support.php`
- [ ] T357 [P] Add `tests/Feature/Support/`, `tests/Feature/Support/Enumeration/`, `tests/Feature/Support/Throttle/`, and `tests/Feature/Support/Retention/` directories, and register the `Enumeration` group in `phpunit.xml` so it can be run alone
- [ ] T358 [P] Vendor the self-hosted proof-of-work challenge widget into `resources/js/vendor/` and `public/js/`, and **confirm it makes no request to any external origin** — if it does, use the hand-rolled fallback described in [research.md](./research.md) R-08 instead. Record which was chosen in `README.md`
- [ ] T359 [P] Confirm the strict Content-Security-Policy from 001's `ContentSecurityPolicy` middleware needs **no new exemption** for this feature, and add `tests/Feature/Security/NoNewCspExemptionTest.php` asserting the policy is unchanged

---

## Phase 19: Foundational (Blocking)

**Purpose**: Schema, permissions, settings, and the four security services every story relies on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

### Migrations

- [ ] T360 Create the `support_requests` migration in `database/migrations/` with: `reference` **char(12) UNIQUE** · `student_id` FK → `students.id` **nullable, restrict** (nullable **only** so retention can anonymise; never null on creation) · `term_id` FK → `academic_terms.id` restrict · `category` **varchar(40)** · `description` **text** · `contact` **varchar(255) nullable** · `status` **enum(`new`,`in_progress`,`resolved`,`closed`,`rejected`)** · `rejection_reason` **varchar(500) nullable** · `assigned_to` FK → `users.id` nullable · `submitted_ip` **varchar(45)** · `anonymised_at` **timestamp nullable**
- [ ] T361 Add the indexes to the `support_requests` migration: `reference` unique · `(status, created_at)` for the queue's oldest-first default · `(student_id)` for the branch-scope join · `(category, created_at)` · `(assigned_to)` · `(created_at)` for the retention sweep
- [ ] T362 [P] Create the `support_responses` migration with `support_request_id` FK cascade · `user_id` FK → `users.id` · `body` **text** · `created_at` and **no `updated_at` column**, plus index `(support_request_id, created_at)`. The absent `updated_at` is what makes the table append-only at the schema level

### Models

- [ ] T363 Create `app/Models/SupportRequest.php` applying **001's existing `BelongsToBranchScope` unchanged**, resolved through the student's active enrollments; the `status` cast; the `responses` and `student` and `assignee` relations; a computed `waiting_time`; and **no update path for `reference`**
- [ ] T364 [P] Create `app/Models/SupportResponse.php` with `$timestamps = false` except `created_at`, guarded against mass assignment, and **no update or delete path** reachable from application code
- [ ] T365 [P] Apply 001's `Auditable` trait to `SupportRequest` with an `auditedAttributes()` allowlist that **excludes `description`** — the body lives in the request row, and copying free-text personal data into a 5-year audit payload would defeat the 2-year retention

### Permissions and settings

- [ ] T366 Create `database/seeders/SupportPermissionSeeder.php` seeding `support.view`, `support.respond`, `support.assign`, `support.close` — taking the platform total from 42 to **46** — granting all four to `SUPER_ADMIN`, and **only** `support.view` and `support.respond` to `BRANCH_COORDINATOR`. `INSTRUCTOR` receives none
- [ ] T367 Create `database/seeders/SupportSettingSeeder.php` seeding the seven keys from [data-model.md](./data-model.md): `support_categories` (5 entries with AR/EN labels) · `support_description_max` = **2000** · `support_throttle_ip` = **5 per hour** · `support_throttle_identifier` = **3 per hour** · `support_status_throttle_ip` = **10 per hour** · `support_min_response_ms` = **400** · `support_retention_months` = **24**

### The four security services

- [ ] T368 Implement `app/Modules/Support/Services/VerifyStudentIdentity.php` resolving roster existence **and** active enrollment in the current term in **one query** returning a **single boolean**. It MUST NOT expose which condition failed, and no caller may branch on the reason ([research.md](./research.md) R-10)
- [ ] T369 Implement `app/Modules/Support/Services/ReferenceGenerator.php` producing **10 characters of Crockford base32 from a CSPRNG plus a check character**, rendered uppercase and hyphenated (`K7Q2-M9XV-T3`), excluding `I`, `L`, `O`, `U`, retrying on collision. **Must not** use ULID, UUIDv7, Snowflake, or any time-ordered scheme — they leak creation order and time (R-11)
- [ ] T370 Implement `app/Modules/Support/Services/Challenge/` — issue a signed single-use proof-of-work challenge stored in Redis with a 10-minute expiry and difficulty from settings, and verify-and-consume it. It MUST **fail closed**: when the store is unavailable, verification returns false rather than true (FR-129)
- [ ] T371 Implement `app/Modules/Support/Http/Middleware/SupportSubmissionThrottle.php` evaluating **two independent limiters**, both of which must pass: per originating IP against `support_throttle_ip`, and per submitted University ID **hashed before use** against `support_throttle_identifier`. A refusal MUST state a limit was reached and when to retry and MUST NOT reveal **which** limit (FR-130, R-09)
- [ ] T372 Implement `app/Modules/Support/Http/Middleware/ConstantResponseTime.php` padding every public response to `support_min_response_ms` (default **400 ms**), because the not-found path returns after one index lookup while the not-enrolled path returns after a join, and that difference is measurable over enough samples (R-10)
- [ ] T373 [P] Implement `app/Modules/Support/Policies/SupportRequestPolicy.php` mapping each action to its `support.*` permission, with `close` requiring `support.close` and `assign` requiring `support.assign`, and register it in `app/Providers/AuthServiceProvider.php`

### Public layout

- [ ] T374 Create `resources/views/layouts/public.blade.php` — a standalone layout carrying **no navigation to any authenticated area**, no sidebar and no topbar (FR-163), with the brand logo, the language switch available **without a sign-in**, and `<html lang dir>` from the requested locale
- [ ] T375 [P] Create `resources/css/_public.scss` for the public layout using **only** design tokens, and add it to both the RTL and LTR Vite entry points
- [ ] T376 [P] Add the shared `support` translation keys to `lang/ar/support.php` and `lang/en/support.php`: the five category labels, the five state labels, the two generic failure messages, and the throttle message

**Checkpoint**: Schema migrates, 46 permissions and 7 settings seed, all four security services exist and fail closed, and the public layout renders in both directions.

---

## Phase 20: User Story 9 - A Student Reports A Problem (Priority: P1) 🎯 MVP

**Goal**: A student submits a support request through a public page that verifies them against the roster, and receives a reference number. No account is created.

**Independent Test**: Submit one request as a genuinely enrolled student and confirm it is recorded with a reference; submit as a non-existent identifier and as a student with no active enrollment, and confirm both are refused identically. No staff-facing work needs to exist.

### Tests for User Story 9

- [ ] T377 [P] [US9] Write `tests/Feature/Support/SubmitRequestTest.php` covering successful submission by an enrolled student, the recorded reference, the recorded IP, the empty-description refusal, and that **no account, credential, or session is created** (FR-124)
- [ ] T378 [P] [US9] Write `tests/Feature/Support/Enumeration/SubmissionFailuresIdenticalTest.php` asserting **byte-identical bodies, identical status codes, and no varying header** across all three verification failures: identifier absent · identifier present with no enrollment in the current term · identifier present with only a withdrawn enrollment (FR-119, SC-026). **This is the single most important test in the feature**
- [ ] T379 [P] [US9] Write `tests/Feature/Support/Enumeration/ResponseTimeFloorTest.php` asserting every public response takes at least `support_min_response_ms`, so the one-lookup path and the join path are indistinguishable by stopwatch (R-10)
- [ ] T380 [P] [US9] Write `tests/Feature/Support/FormatFailureIsSpecificTest.php` asserting a University ID failing the configured pattern gets a **specific** message naming the expected format, and that it is returned **before** the verification query runs — the one deliberate exception to the generic-failure rule (FR-117)
- [ ] T381 [P] [US9] Write `tests/Feature/Support/Throttle/IpThrottleTest.php` asserting sustained submission from one IP with **varying** identifiers is refused at `support_throttle_ip`
- [ ] T382 [P] [US9] Write `tests/Feature/Support/Throttle/IdentifierThrottleTest.php` asserting sustained submission for one identifier from **varying** IPs is refused at `support_throttle_identifier`, **independently** of the IP limit. A single combined limiter passes T381 and fails this — which is the point (R-09)
- [ ] T383 [P] [US9] Write `tests/Feature/Support/Throttle/ThrottleMessageHidesWhichLimitTest.php` asserting neither refusal reveals which limit was reached (FR-130)
- [ ] T384 [P] [US9] Write `tests/Feature/Support/ChallengeTest.php` asserting submission is refused with no solution, with a replayed solution, with an expired solution, with the honeypot completed, within the minimum elapsed time, and — critically — **when the challenge store is unavailable** (FR-128, FR-129)
- [ ] T385 [P] [US9] Write `tests/Feature/Support/ReferenceGeneratorTest.php` generating 1,000 references and asserting no sequence, no timestamp correlation, no growing shared prefix, and none of the characters `I`, `L`, `O`, `U` (SC-027)
- [ ] T386 [P] [US9] Write `tests/Feature/Support/AuditOnPublicSubmissionTest.php` asserting exactly one `support.request_created` entry with a **null actor**, `channel: public`, the IP, and the resolved student and term — and that the entry contains **no** description, challenge solution, or honeypot value
- [ ] T387 [P] [US9] Write `tests/Feature/Support/NoAttachmentRouteTest.php` asserting no upload route exists for this feature and the form carries no file control (FR-125)
- [ ] T388 [P] [US9] Write `tests/Visual/support-public-form.spec.ts` screenshotting the form, the confirmation, and the generic refusal in both locales at desktop and phone width

### Implementation for User Story 9

- [ ] T389 [US9] Implement `app/Modules/Support/Http/Requests/SubmitSupportRequestRequest.php` validating `university_id` against 001's shared `UniversityId` rule, `category` against 001's shared `InSetting` rule reading `support_categories`, `description` present and within `support_description_max`, `contact` optional, and the honeypot and issued-at guards
- [ ] T390 [US9] Implement `app/Modules/Support/Actions/SubmitSupportRequest.php` extending 001's `TransactionalAction`: resolve the student through `VerifyStudentIdentity`, generate the reference, create the request in `new`, record the IP, and audit `support.request_created` with a null actor — all in one transaction so a failed audit aborts the submission (constitution II)
- [ ] T391 [US9] Implement `app/Modules/Support/Http/Controllers/Public/SupportRequestController.php` with `GET /support` and `POST /support`, applying the **contractual order of evaluation** from [contracts/public.md](./contracts/support-public.md): rate limits first, then challenge and honeypot and elapsed time, then **format** validation, then other fields, then verification. Refusing first means no later step can be used as an oracle
- [ ] T392 [US9] Implement `GET /support/challenge` in `app/Modules/Support/Http/Controllers/Public/ChallengeController.php` returning the challenge, its difficulty, and its expiry, and **503** with a generic message when the store is unavailable
- [ ] T393 [US9] Implement `GET /support/submitted` reading the reference from a **single-render flash**, not from the URL, so it does not land in browser history, a proxy log, or a shared link
- [ ] T394 [US9] Register all four routes in `routes/public.php` with the `SupportSubmissionThrottle` and `ConstantResponseTime` middlewares, CSRF **enabled** (a public form is not an excuse to disable it), and no `auth` middleware
- [ ] T395 [P] [US9] Build `resources/views/support/public/form.blade.php` on the public layout — `x-track-input` for the University ID, `x-track-select` for the category from settings, a description textarea with a live character count against `support_description_max`, an optional contact input, the challenge widget, the hidden honeypot, and `x-track-button` primary with `loading`
- [ ] T396 [P] [US9] Build `resources/views/support/public/submitted.blade.php` displaying the reference **prominently** with guidance to retain it, and a link to the status page
- [ ] T397 [P] [US9] Build `resources/views/support/public/refused.blade.php` rendering the generic failure through `x-track-alert` tone danger, with **identical** copy for all three verification failure modes
- [ ] T398 [P] [US9] Add the US9 translation keys to `lang/ar/support.php` and `lang/en/support.php` — form labels, the character-count text, the guidance to retain the reference, and the two generic messages

**Checkpoint**: A student can raise a request and nothing about the roster leaks. This is the MVP — but see the note under US10 about why it should not ship alone.

---

## Phase 21: User Story 10 - Staff See And Answer Requests (Priority: P2)

**Goal**: Staff work the queue: list, filter, open, respond, transition, reassign — every action recorded.

**Independent Test**: With three requests seeded, open the queue as Super Admin, respond to one, reject one with a reason, reassign the third, and confirm each action is recorded and the state changed.

**Note on shipping**: User Story 9 alone captures requests nobody answers. US9 + US10 is the smallest honest release.

### Tests for User Story 10

- [ ] T399 [P] [US10] Write `tests/Feature/Support/QueueTest.php` asserting each row carries the student, their branch, the category, the state, the assignee, and the **waiting time**, ordered oldest-first by default
- [ ] T400 [P] [US10] Write `tests/Feature/Support/QueueFiltersTest.php` asserting the `status`, `category`, `branch_id`, `assigned_to`, `date_from`, `date_to`, and `q` filters each narrow correctly and that totals count only matching rows
- [ ] T401 [P] [US10] Write `tests/Feature/Support/OpenTransitionsToInProgressTest.php` asserting opening a `new` request moves it to `in_progress` and records who did so (FR-147)
- [ ] T402 [P] [US10] Write `tests/Feature/Support/RespondTest.php` asserting a response is recorded against its author, becomes visible to the student, and that a response on a **closed** request is refused (FR-150, FR-151)
- [ ] T403 [P] [US10] Write `tests/Feature/Support/StateMachineTest.php` asserting every permitted transition in [data-model.md](./data-model.md) succeeds and every other is refused — explicitly including `new` → `closed`, `new` → `resolved`, and anything out of `closed` (FR-148)
- [ ] T404 [P] [US10] Write `tests/Feature/Support/RejectRequiresReasonTest.php` asserting rejection without a reason is refused and that the reason is visible to the student (FR-149)
- [ ] T405 [P] [US10] Write `tests/Feature/Support/ReassignTest.php` asserting both previous and new assignee are recorded, that a coordinator is refused, and that assigning to a user without `support.view` is refused because it would silently strand the request (FR-152)
- [ ] T406 [P] [US10] Write `tests/Feature/Support/ContactMarkedUnverifiedTest.php` asserting the student-typed contact is flagged unverified **in the payload**, not only in the view, so a client cannot render it as confirmed (FR-145)
- [ ] T407 [P] [US10] Write `tests/Feature/Support/AuditCoverageTest.php` performing respond, transition, and reassign once each and asserting exactly one entry per action with the correct key from [contracts/README.md](./contracts/README.md)
- [ ] T408 [P] [US10] Write `tests/Authorization/SupportEndpointsTest.php` with an allow case and a deny case for each of the **5** staff endpoints in [contracts/staff.md](./contracts/support-staff.md)
- [ ] T409 [P] [US10] Write `tests/Feature/Support/NoMarkupInterpretedTest.php` asserting a description and a response containing markup or script are rendered as plain text to staff (FR-153)
- [ ] T410 [P] [US10] Write `tests/Visual/support-staff.spec.ts` screenshotting the queue, the request detail with its response thread, and the reject confirmation in both locales

### Implementation for User Story 10

- [ ] T411 [US10] Implement `app/Modules/Support/Http/Controllers/SupportQueueController.php` with `GET /api/v1/support-requests` supporting all seven filters, defaulting to oldest-waiting-first, and returning the computed waiting time
- [ ] T412 [US10] Implement `GET /api/v1/support-requests/{reference}` in the same controller, bound by **`reference` rather than `id`** so the URL carries no sequential identifier a coordinator could iterate, returning the full response thread, the state history, and the contact flagged unverified
- [ ] T413 [US10] Implement the open-side-effect in that action: a `new` request becomes `in_progress` on first open, audited as `support.status_changed`. Stated in the contract because a read with a side effect is unusual
- [ ] T414 [US10] Implement `app/Modules/Support/Actions/RespondToSupportRequest.php` extending `TransactionalAction`, refusing on a `closed` request with 409, and auditing `support.responded`
- [ ] T415 [US10] Implement `app/Modules/Support/Http/Controllers/SupportResponseController.php` with `POST /api/v1/support-requests/{reference}/responses`
- [ ] T416 [US10] Implement `app/Modules/Support/Actions/TransitionSupportRequest.php` enforcing the state machine, requiring `rejection_reason` for `rejected`, requiring `support.close` to reach `closed`, and auditing `support.status_changed` with old and new values
- [ ] T417 [US10] Implement `app/Modules/Support/Http/Controllers/SupportStateController.php` with `PATCH /api/v1/support-requests/{reference}/status` and `PATCH /api/v1/support-requests/{reference}/assignee`, the latter behind `support.assign`
- [ ] T418 [P] [US10] Build `resources/views/support/staff/queue.blade.php` — `x-track-page-header`, a filter `x-track-card`, and `x-track-data-table` with `x-track-status-badge` per state, the waiting time as a numeric column, and row click-through
- [ ] T419 [P] [US10] Build `resources/views/support/staff/show.blade.php` — `x-track-breadcrumbs`, the request panel, the response thread, and the contact rendered with a **visible unverified marker**
- [ ] T420 [P] [US10] Build `resources/views/support/staff/_respond.blade.php` and `_transition.blade.php` — the response composer, the state control, and an `x-track-dialog` danger confirmation for rejection that restates the impact and requires the reason (FR-167)
- [ ] T421 [P] [US10] Add the support queue entry to `config/navigation.php` for `SUPER_ADMIN` and `BRANCH_COORDINATOR` with the `life-buoy` icon, and **not** for `INSTRUCTOR`
- [ ] T422 [P] [US10] Add the US10 translation keys and the five `action_label` entries for the new audit keys to `lang/ar/` and `lang/en/`

**Checkpoint**: Requests arrive and get answered. US9 + US10 is the smallest honest release.

---

## Phase 22: User Story 11 - A Student Finds Out What Happened (Priority: P3)

**Goal**: A student retrieves their request's state and any response using the reference number and their University ID, and sees nothing else.

**Independent Test**: With one responded-to request, retrieve it with the correct pair; then with a correct reference and the wrong ID, and with a reference that does not exist. Both failures must be identical.

### Tests for User Story 11

- [ ] T423 [P] [US11] Write `tests/Feature/Support/StatusRetrievalTest.php` asserting a correct reference plus matching University ID returns the request, its state, and every response
- [ ] T424 [P] [US11] Write `tests/Feature/Support/Enumeration/RetrievalFailuresIdenticalTest.php` asserting **byte-identical** responses for a non-existent reference and for a valid reference with a mismatched University ID (FR-135, SC-029)
- [ ] T425 [P] [US11] Write `tests/Feature/Support/StatusExposesNothingElseTest.php` asserting the response carries **no** assignee, no internal note, no originating IP, no echoed identifier, and nothing belonging to another student (FR-134)
- [ ] T426 [P] [US11] Write `tests/Feature/Support/Throttle/StatusThrottleTest.php` asserting retrieval is limited per IP at `support_status_throttle_ip`, tighter than submission, because an attacker holding a stolen reference would otherwise brute-force the identifier against it (FR-131)
- [ ] T427 [P] [US11] Write `tests/Feature/Support/StatusNoResponseYetTest.php` asserting a request with no response shows its state with an explanation that it is still being handled (FR-136)
- [ ] T428 [P] [US11] Write `tests/Visual/support-status.spec.ts` screenshotting the status form, a result with responses, a result with none, and the generic failure in both locales

### Implementation for User Story 11

- [ ] T429 [US11] Implement `app/Modules/Support/Http/Controllers/Public/SupportStatusController.php` with `POST /support/status` querying on **both** `reference` and the student's University ID in one query, so a mismatch is indistinguishable from a missing reference
- [ ] T430 [US11] Register the route in `routes/public.php` with its own tighter throttle and the `ConstantResponseTime` middleware, and **no audit entry** — a read exposing only the caller's own request with no state change is not a sensitive action, and logging every poll would fill the audit log with entries carrying the identifier, becoming a disclosure surface itself
- [ ] T431 [P] [US11] Build `resources/views/support/public/status-form.blade.php` and `status-result.blade.php` on the public layout, the result showing the reference, category, submitted date, `x-track-status-badge`, the rejection reason where present, and the response thread with dates
- [ ] T432 [P] [US11] Add the US11 translation keys, including the still-being-handled message and the generic retrieval failure

**Checkpoint**: The loop closes — a student can find out what happened.

---

## Phase 23: User Story 12 - A Coordinator Sees Only Their Own Branch (Priority: P4)

**Goal**: A coordinator's queue shows only requests from students enrolled in their branches; anything else behaves as nonexistent.

**Independent Test**: With requests from students in two branches and a coordinator assigned to one, confirm the queue, its totals, its filters, and any direct reference attempt all show only their own branch.

### Tests for User Story 12

- [ ] T433 [P] [US12] Write `tests/Scope/SupportRequestScopeTest.php` asserting a coordinator's queue and totals contain only requests from students enrolled in their branches (FR-140)
- [ ] T434 [P] [US12] Write `tests/Scope/SupportOutOfScopeReturns404Test.php` asserting a reference from another branch returns **404, never 403**, through the API route and the Blade route, and that the attempt writes `access.denied` (FR-143, FR-158)
- [ ] T435 [P] [US12] Write `tests/Scope/SupportBranchFilterCannotWidenTest.php` asserting a coordinator passing another branch's `branch_id` receives **their own scope, not an error** — so the filter cannot be used to probe
- [ ] T436 [P] [US12] Write `tests/Scope/SupportMultiBranchStudentTest.php` asserting a student with active enrollments in two branches has their request visible to coordinators of **both** (FR-141)
- [ ] T437 [P] [US12] Write `tests/Scope/SupportInstructorHasNoAccessTest.php` asserting an instructor has no queue and every support address returns 404 (FR-142, SC-038)
- [ ] T438 [P] [US12] Write `tests/Scope/SupportScopeFollowsEnrollmentTest.php` asserting that when a student's active enrollment moves to another branch, the new branch's coordinator gains access and the old one loses it

### Implementation for User Story 12

- [ ] T439 [US12] Verify `BelongsToBranchScope` on `SupportRequest` resolves through `student.enrollments.section.branch` filtered to **active** enrollments, and correct the relation path until T433–T438 pass. No new scope type is introduced
- [ ] T440 [US12] Configure route-model binding for `SupportRequest` by `reference` in `app/Providers/RouteServiceProvider.php` to resolve **through** the scoped query, so an out-of-scope reference raises `ModelNotFoundException` → 404 before any policy runs
- [ ] T441 [P] [US12] Constrain the `branch_id` filter in `SupportQueueController` to the caller's own branches for a coordinator, returning their scope rather than an error
- [ ] T442 [P] [US12] Add the Super Admin-only branch column and filter to the queue view, hidden for a coordinator whose queue is already single-scoped

**Checkpoint**: Handling is distributed and the data-protection boundary holds.

---

## Phase 24: User Story 13 - Everyone Works In Their Own Language (Priority: P5)

**Goal**: All 8 screens complete and accessible in Arabic (RTL) and English (LTR), including the public pages where the visitor has no stored preference.

**Independent Test**: Walk every screen in both languages and confirm complete translation, correct mirroring, a text label beside every status colour, and full keyboard operation.

### Tests for User Story 13

- [ ] T443 [P] [US13] Write `tests/Feature/Support/Localization/PublicPagesBothLocalesTest.php` asserting the public pages render in both locales with correct `lang` and `dir`, and that the language switch works **while signed out** (FR-161)
- [ ] T444 [P] [US13] Write `tests/Feature/Support/Localization/NoUntranslatedOutputTest.php` asserting no support screen renders a raw translation key or a fallback-language string
- [ ] T445 [P] [US13] Write `tests/Visual/support-axe.spec.ts` running axe-core over all 8 screens in both locales, requiring **zero** WCAG 2.1 AA violations (SC-036)
- [ ] T446 [P] [US13] Write `tests/Visual/support-rtl.spec.ts` asserting per screen that the layout, breadcrumb chevrons, button icon side, and toast corner all mirror correctly

### Implementation for User Story 13

- [ ] T447 [US13] Sweep every view under `resources/views/support/` for hard-coded user-facing text until 001's existing `NoHardcodedStringsTest` and `LocalizationParityTest` both pass for the `support` namespace
- [ ] T448 [US13] Complete keyboard operation and WCAG 2.1 AA across all 8 screens — focus order, the visible focus ring, ARIA labels on icon-only controls, the challenge widget's accessible labelling, and dialog focus trapping — until `support-axe.spec.ts` is clean
- [ ] T449 [P] [US13] Apply 001's `Format` helper to every rendered date and number in the support channel so digits are 0-9 and dates read DD/MM/YYYY in both locales
- [ ] T450 [P] [US13] Verify and correct the responsive behaviour of the public pages and the queue at desktop and phone width in both locales, with the queue table becoming stacked cards below 768px

**Checkpoint**: All five stories functional, faithful, and accessible in both languages.

---

## Phase 25: Retention, Hardening & Cross-Cutting

**Purpose**: The retention obligation the constitution added, plus the guards that keep the public surface bounded

**Retention is a requirement, not polish** — constitution v1.1.0 mandates it and SC-037 gates on it.

- [ ] T451 Implement `app/Modules/Support/Actions/AnonymiseExpiredRequests.php` clearing **exactly three columns** — `student_id`, `contact`, `submitted_ip` — stamping `anonymised_at`, and preserving the reference, category, status, state history, and **all** responses (FR-159)
- [ ] T452 Schedule the anonymisation sweep daily as a queued job in `routes/console.php`, reading `support_retention_months` (default **24**), with the clock running from **creation**, not closure, so an abandoned request is not retained indefinitely
- [ ] T453 [P] Write `tests/Feature/Support/Retention/AnonymiseTest.php` asserting the three columns are cleared, `anonymised_at` is stamped, and the category, state history, and responses **survive** (SC-037)
- [ ] T454 [P] Write `tests/Feature/Support/Retention/AuditSurvivesAnonymisationTest.php` asserting the audit entries are **not** touched — they follow the 5-year audit retention, which is longer (FR-160)
- [ ] T455 [P] Write `tests/Feature/Support/Retention/AnonymisedVisibilityTest.php` asserting an anonymised request becomes visible to the **Super Admin only** — it has no student, so no enrollments, so no branch — and that public retrieval by its old reference returns the **same generic failure** as a non-existent reference
- [ ] T456 Write `tests/Architecture/PublicSurfaceBoundedTest.php` failing the build if any route is registered outside the authenticated stack other than the **five** declared in [contracts/public.md](./contracts/support-public.md). A sixth requires a constitution amendment, not a pull request
- [ ] T457 [P] Write `tests/Architecture/NoRawOutputInSupportViewsTest.php` banning `{!! !!}` anywhere under `resources/views/support/`, so no description or response can ever be interpreted as markup (FR-153)
- [ ] T458 [P] Write `tests/Architecture/NoStudentAuthColumnsTest.php` asserting `students` carries no credential, contact, or activation column — the central constraint of this feature, and the reason feature 001 needed no change
- [ ] T459 [P] Add `support_requests` and `support_responses` retention and the anonymisation sweep to the deployment runbook at `docs/runbook-deploy.md`
- [ ] T460 Run the security review of this feature's public surface against the OWASP Top 10 and record it in `docs/security-review-002.md`, giving particular attention to the enumeration channels and the accepted impersonation risk recorded in spec "Accepted Risks"
- [ ] T461 Execute the full gate from [quickstart.md](./quickstart.md): `pint --test`, `phpstan analyse`, `php artisan test`, `--testsuite=Architecture`, `--testsuite=Scope`, and `npx playwright test`
- [ ] T462 Walk all 11 quickstart.md validation scenarios manually and record the outcome against the 8 Definition-of-Done items
- [ ] T463 Add the link to `/support` on the sign-in screen in `resources/views/auth/login.blade.php`, behind a `support_channel_enabled` setting defaulting to **false**, with its translation key in both locales — and write `tests/Feature/Support/SignInLinkTest.php` asserting the link is absent while the flag is false and present when true. **This is the only task in Phases 18–25 that edits a file built by Phases 1–17**, and it closes FR-114's requirement that the platform "MUST link to it from the sign-in screen", which the pre-merge analysis found uncovered

---


---

# Amendment 3 (2026-09-28) — design system v2

`TRACK Design System_V2.zip` reviewed against the merged specification. **The foundation did not
drift**: all 141 tokens, 27 components, 22 assets and 21 guideline cards are byte-identical to v1, and
every token the new screens use already exists. **Phase 3 (T057–T103) needs no change**, and neither do
the seven accessibility corrections or the visual-regression baselines.

v2's changes are confined to screens. Five conflict with the specification and are **blocked**; the rest
are adopted below. Decisions: [ui-conflicts.md](./ui-conflicts.md) C-19 … C-23.

## Phase 26: Design system v2 — adopted changes

Small, safe, and independent of everything blocked. Can run any time after Phase 3.

- [ ] T464 [P] Re-vendor `design-system/` from `TRACK Design System_V2.zip`, keeping v1 at
  `design-system-v1/` as the visual-regression baseline, and re-apply the seven accessibility
  corrections from `design-system/ACCESSIBILITY-DELTA.md`. Then run
  `tests/Architecture/TokenParityTest.php` — it must pass **without** a baseline update, because v2's
  tokens are byte-identical to v1's; a failure means the re-vendoring went wrong, not that the design
  changed
- [ ] T465 [P] Correct the `programs` nav icon from `graduation-cap` to `library` in
  `config/icons.php` and `config/navigation.php`, matching v2's `Shell.jsx`, and free
  `graduation-cap` for الطلبة as v2 reassigns it
- [ ] T466 [P] Add the four new navigation translation keys — `nav_approvals`, `nav_myinitiatives`,
  `nav_studentsadmin`, `nav_support` — to `lang/ar/nav.php` and `lang/en/nav.php`, taking the Arabic
  strings verbatim from `design-system/ui_kits/web-app/i18n.js`. The `approvals` and `myinitiatives`
  keys are seeded now but their nav entries stay **absent** until C-19 and C-22 are decided
- [ ] T467 [P] Copy `design-system/scratch/roster_sample.xlsx` and
  `design-system/uploads/filedownloadservlet(21)-4d54b7af.xlsx` into `tests/Fixtures/rosters/` and
  extend `tests/Feature/Imports/RegistrarRealFileTest.php` (T325) to run the registrar mode against
  **all three** real exports, asserting each resolves its branch, course, and section from its own
  metadata block. Three real files with differing column shapes is materially better evidence than one
- [ ] T468 Extend `resources/views/students/index.blade.php` and `show.blade.php` to match v2's
  `StudentsAdminScreen` and `StudentDialog` — the richer staff-side students view — reusing the existing
  `x-track-data-table`, adding the program column and the degree and activity columns from Amendment 1,
  and **reconciling its `StudentImportDialog` against the five import modes already specified** rather
  than adding a sixth. If it maps to an existing mode, wire it to that mode; if it does not, stop and
  raise it as a conflict rather than adding a mode
- [ ] T469 [P] Update `tests/Visual/us4.spec.ts` baselines for the students screens changed by T468, and
  confirm every other baseline still matches — v2 changed no token, so any other diff is a defect in our
  implementation, not a design change

**Checkpoint**: v2 is vendored, the icon and nav keys are corrected, three real registrar files are under
test, and the students-admin screen matches the approved design — with nothing blocked touched.

---

## RETIRED 2026-09-28: student authentication and portal (ui-conflicts.md C-19, C-20, C-21)

**Not in this release — decided by the Product Owner on 2026-09-28.** The fourteen tasks that stood here
(`T-S01` … `T-S14`) are **withdrawn**, not deferred into a backlog, because none of them would survive a
later revival unchanged: a future student portal should be built on university SSO (PRD P3-F002) rather
than on the password scheme v2 drew, which used the university ID as the initial credential.

What this decision preserves:

- **Phases 18–25 proceed exactly as written.** The public support form, roster verification, the two
  independent rate limits, the challenge that fails closed, the non-derivable reference, and the
  enumeration suite are all unchanged. T354–T463 need no edit.
- **Constitution stays at v1.1.0.** No MUST is reversed, so no 2.0.0 amendment and no migration plan.
- **FR-114 … FR-169 stand.** No requirement is rewritten; SC-025 … SC-038 keep their meaning.
- The impersonation risk in spec "Accepted Risks" stays bounded, because a public form exposes no data.

v2's `ForcePasswordScreen`, `StudentHome`, `StageTrack` and authenticated `SupportScreens` remain in
`design-system/` as reference for a future version. They are **not** to be built. The design author needs
the five public screens and three staff screens from
[CR-002 §12](../changes/CR-002-student-support.md) instead.

---

## Phase 27: Section approval by originator (Amendment 4)

Resolves [ui-conflicts.md](./ui-conflicts.md) C-22, decided 2026-09-28: **an instructor, a coordinator,
and a Super Admin can each create a section; only the Super Admin's is active immediately.** Requirements
FR-170 … FR-185, SC-039 … SC-043. Schema: [data-model.md](./data-model.md) Group 8. Contracts:
[contracts/academic.md](./contracts/academic.md) "Section approval".

**Interleaves with Phase 7 (User Story 4)** — it changes how sections are created, so it cannot follow the
section screens. Run T470–T473 with Phase 2's migrations and the rest with Phase 7.

**⚠️ One pre-existing task changes**: T023 builds the four-column unique index on `sections`. T471 drops
and replaces it. Sequence T023 before T471, which the phase ordering guarantees.

### Schema — runs with Phase 2

- [ ] T470 Add to the `sections` migration: `status` **enum(`pending`,`returned`,`rejected`,`active`) default `active`** so pre-amendment and Super-Admin-created sections need no backfill · `created_by` FK → `users.id` · `requires_approval` boolean derived on creation from the creator's role · `reviewed_by` FK → `users.id` nullable · `reviewed_at` timestamp nullable · `review_reason` varchar(500) nullable (FR-173)
- [ ] T471 Replace the section-key index: **drop** the four-column `UNIQUE (term_id, branch_id, course_id, section_number)` from T023, add the **stored generated column** `approval_key varchar(20)` = `CASE WHEN status='rejected' THEN NULL ELSE section_number END`, and add `UNIQUE (term_id, branch_id, course_id, approval_key)`. This is what makes FR-176 work: two non-rejected sections collide, a rejected one releases its number, and several rejected ones coexist because `NULL`s do not collide. Adding `status` or `deleted_at` to the index instead **does not work** — see [data-model.md](./data-model.md) Group 8 for why each alternative fails
- [ ] T472 [P] Create the `section_approval_history` migration with `section_id` cascade, `from_status` enum nullable, `to_status` enum, `actor_id`, `reason` varchar(500) nullable, `created_at` and **no `updated_at`**, plus index `(section_id, created_at)`
- [ ] T473 [P] Extend `PermissionSeeder` to grant the existing `section.create` to `BRANCH_COORDINATOR` and `INSTRUCTOR` as well as `SUPER_ADMIN`, and add `section.approve` granted to `SUPER_ADMIN` and `BRANCH_COORDINATOR` — **48 permissions total**. Do **not** add a `section.request` permission: creation and requesting are the same act, and the distinction is carried by `requires_approval`

### Tests — runs with Phase 7

- [ ] T474 [P] [US4] Write `tests/Feature/Sections/CreatorDecidesApprovalTest.php` asserting a Super Admin's section is `active` immediately while an instructor's and a coordinator's are `pending`, and that the initial status is derived from the **authenticated user's role** and cannot be set from a request field (FR-171, FR-172, SC-039)
- [ ] T475 [P] [US4] Write `tests/Feature/Sections/NoSelfApprovalTest.php` asserting a creator cannot decide their own section, and a coordinator cannot decide a section created by a coordinator of the **same branch** — both refused with **403**, not 404, because the caller can see it and merely may not decide it (FR-178, SC-040)
- [ ] T476 [P] [US4] Write `tests/Feature/Sections/ApprovalScopeTest.php` asserting a coordinator's queue shows only **instructors'** pending sections from their own branches, and a Super Admin's shows every pending section (FR-177, FR-184)
- [ ] T477 [P] [US4] Write `tests/Feature/Sections/RejectionReleasesNumberTest.php` asserting that after a rejection the same term, branch, course, and number can be used again, that two non-rejected sections with that combination are still refused, and that several rejected ones coexist (FR-176, SC-041)
- [ ] T478 [P] [US4] Write `tests/Feature/Sections/ApprovalStateMachineTest.php` asserting every permitted transition in [data-model.md](./data-model.md) Group 8 succeeds and every other is refused, explicitly including that **`rejected` is terminal** and that no decision can be made on an `active` section (FR-180)
- [ ] T479 [P] [US4] Write `tests/Feature/Sections/ReturnAndResubmitTest.php` asserting a return requires a reason of at least 10 characters, makes the section editable by its creator, shows the reason to them, and that resubmission returns it to `pending` (FR-179, FR-183)
- [ ] T480 [P] [US4] Write `tests/Feature/Sections/PendingNotTargetableTest.php` asserting a pending, returned, or rejected section never appears in an initiative targeting preview, never receives an assignment, and never enters a completion denominator (FR-174). **This is the most consequential test in the phase** — a pending section leaking into targeting would corrupt completion figures
- [ ] T481 [P] [US4] Write `tests/Feature/Sections/PendingHoldsEnrollmentsTest.php` asserting a creator can attach a roster at creation and that those enrollments survive both approval and rejection (FR-175)
- [ ] T482 [P] [US4] Write `tests/Feature/Sections/RejectedExcludedFromListsTest.php` asserting rejected sections are absent from default lists but present when `status=rejected` is requested, and that pending and returned ones are present and badged (FR-181, FR-183)
- [ ] T483 [P] [US4] Write `tests/Authorization/SectionApprovalEndpointsTest.php` with an allow case and a deny case for the four new routes, plus an out-of-scope 404 test and an instructor-denied test for the queue and the decision route
- [ ] T484 [P] [US4] Write `tests/Feature/Sections/ApprovalAuditTest.php` asserting exactly one entry per decision from the five new action keys, each carrying the previous and new status and the reason where given (FR-182)
- [ ] T485 [P] [US4] Write `tests/Feature/Sections/ApprovalVocabularyTest.php` asserting no interface string and no audit label renders a bare "approved" where both section approval and assignment review could be meant, and that the two vocabularies do not share a state name in the same view (FR-185, SC-043)
- [ ] T486 [P] [US4] Write `tests/Visual/section-approval.spec.ts` screenshotting the approval queue, the decision dialog, the return-reason dialog, and a badged pending section in the list, in both locales at all four widths, against v2's `ApprovalsScreen` and `ApprovalDialog` references

### Implementation — runs with Phase 7

- [ ] T487 [US4] Extend `app/Models/Section.php` with the `status` cast, the `creator`, `reviewer`, and `approvalHistory` relations, a `scopeActive()`, and a `scopeAwaitingDecisionBy(User $u)` implementing the asymmetric rule in FR-177 and FR-178
- [ ] T488 [US4] Extend `app/Modules/Sections/Actions/CreateSection.php` to derive `status` and `requires_approval` **from the authenticated user's role**, never from the request, write the first `section_approval_history` row, and audit `section.created` plus `section.submitted_for_approval` when pending
- [ ] T489 [US4] Implement `app/Modules/Sections/Actions/DecideSectionApproval.php` extending `TransactionalAction` — approve, return, reject — enforcing the state machine, requiring a reason for return and reject, appending history, and auditing the matching action key
- [ ] T490 [US4] Implement `app/Modules/Sections/Actions/ResubmitSection.php`, permitted only to the creator and only from `returned`, auditing `section.resubmitted`
- [ ] T491 [US4] Extend `app/Modules/Sections/Policies/SectionPolicy.php` with the `approve` ability implementing FR-178: refuse when the actor is the creator, and refuse when the actor is a coordinator and the creator is a coordinator of the same branch. Both refusals are **403**
- [ ] T492 [US4] Implement `GET /api/v1/sections/approvals` in `app/Modules/Sections/Http/Controllers/SectionApprovalController.php`, scoped by `scopeAwaitingDecisionBy`, ordered oldest-waiting-first, returning the creator with their role and the waiting time
- [ ] T493 [US4] Implement `PATCH /api/v1/sections/{id}/approval` and `PATCH /api/v1/sections/{id}/resubmit` in the same controller, plus `GET /api/v1/sections/{id}/approval-history`
- [ ] T494 [US4] Amend `POST /api/v1/sections` to accept an optional `roster` file and route it through the **existing** section import mode rather than a parallel path, and amend `GET /api/v1/sections` to exclude `rejected` by default while including pending and returned
- [ ] T495 [US4] Apply the `active`-only constraint to initiative targeting as a **global scope on the targeting query**, not a filter at each call site, so a later endpoint cannot forget it (FR-174)
- [ ] T496 [P] [US4] Build `resources/views/sections/approvals.blade.php` to v2's `ApprovalsScreen` reference — `x-track-data-table` with the creator, their role, the course, the branch, the enrollment count, the waiting time, and an `x-track-status-badge` per state
- [ ] T497 [P] [US4] Build `resources/views/sections/_approval-dialog.blade.php` to v2's `ApprovalDialog` reference — the three decisions, with the reason textarea **required** for return and reject and an `x-track-dialog` danger variant for reject that restates that rejection is final
- [ ] T498 [P] [US4] Add the status badge to the section list and detail views, and a return-reason `x-track-alert` on a returned section visible to its creator with a resubmit action (FR-183)
- [ ] T499 [P] [US4] Add the `approvals` entry to `config/navigation.php` for `SUPER_ADMIN` and `BRANCH_COORDINATOR` with the `clipboard-check` icon — the entry T466 seeded and left hidden — and **not** for `INSTRUCTOR`
- [ ] T500 [P] [US4] Add the section-approval translation keys to `lang/ar/sections.php` and `lang/en/sections.php`, taking the Arabic status vocabulary verbatim from v2: بانتظار الاعتماد · معادة للتعديل · مرفوضة · معتمدة — and the five new audit `action_label` entries

**Checkpoint**: instructors and coordinators can set up their own sections without queueing behind the central office, nothing enters an initiative until it is approved, and no one approves their own work.

---

## BLOCKED: student contact columns (ui-conflicts.md C-23)

**Do not start.** T105 asserts `students` carries no credential, contact, or activation column, per the
constitution's PDPL minimisation rule.

- [ ] T-C01 **Ask the registration unit whether they can supply student phone numbers or personal
  emails.** This is the most productive question in the whole v2 review: a verified contact is exactly
  what C-19's impersonation problem needs, so a yes converts a blocked conflict into a solvable one
- [ ] T-C02 If a contact can be supplied and is accepted, add it to a `student_contacts` table rather
  than to `students`, keeping the minimisation rule and T105 intact, and record the PDPL basis and
  retention period
- [ ] T-C03 Extend the registrar import to carry the contact if the export gains the column — the three
  real exports currently in hand have four columns and none is a contact


## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 Setup**: no dependencies — start immediately
- **Phase 2 Foundational A (schema, access, audit)**: depends on Setup
- **Phase 3 Foundational B (design system)**: depends on Setup; **independent of Phase 2** and can run fully in parallel with it
- **Phases 4–10 (user stories)**: depend on **both** Phase 2 and Phase 3. A screen cannot be built before its components exist, and T060's palette corrections must land before any screen or every screen inherits a failing accessibility gate
- **Phase 11 Polish**: depends on Phases 4–10 and Phase 16
- **Phase 12 EAV spike**: depends on Phase 2 migrations only; **ungated**; runs in parallel with
  Phases 4–11 and must complete before acceptance

### Amendment 1 phase interleaving (Phases 13–17)

| Phase | Tasks | Runs with | Why it cannot simply follow |
|---|---|---|---|
| **13** Programs & new columns — schema | T294–T305 | **Phase 2** | `sections.program_id` is non-nullable, so it must exist when the sections migration runs. Adding it afterwards means a backfill with no source of truth |
| **17** `last_login_at` | T351–T353 | **Phase 4 / US1**, with T116 | One column set by the sign-in controller; splitting it from sign-in work would mean touching the same file twice |
| **14** Programs — endpoints & screens | T306–T321 | **Phase 7 / US4** | The section create form cannot be built before the program picker exists, since `program_id` is required |
| **15** Registrar import mode | T322–T335 | **Phase 8 / US5**, after T233 | Reuses the staging, chunked-commit, and resume machinery T228–T234 build |
| **16** User Story 8 — bulk course + staff import | T336–T350 | **after Phase 9 / US6** | Reuses the full pipeline, and its course-import entry point lives on the programs screen from Phase 14 |

**Merged execution order**: 1 → (2 + 13 + 3 in parallel) → (4 + 17) → 5 → 6 → (7 + 14) → (8 + 15)
→ 9 → 16 → 10 → 11, with 12 running alongside from the end of Phase 2, **then 18 → 19 → 20 → 21 → 22
→ 23 → 24 → 25**.

### Amendment 2 phases (18–25) — the student support channel

Unlike Amendment 1, these phases **do not interleave**. They run after Phase 11, in their own order:

| Phase | Tasks | Depends on |
|---|---|---|
| **18** Setup | T354–T359 | Phase 1 |
| **19** Foundational — schema, permissions, settings, the four security services | T360–T376 | Phase 2 (students, enrollments, terms), Phase 3 (components) |
| **20** US9 — student submits | T377–T398 | Phase 19 |
| **21** US10 — staff queue and handling | T399–T422 | US9 (needs requests), Phase 7 (branch scope) |
| **22** US11 — student checks status | T423–T432 | US10 (needs responses) |
| **23** US12 — coordinator scoping | T433–T442 | US10, Phase 5 (the scope traits) |
| **24** US13 — bilingual and accessible | T443–T450 | US9–US12's screens |
| **25** Retention, hardening, and the sign-in link | T451–T463 | US9 (needs requests to age) |

**Why they are last and strictly additive.** Every one depends on Phases 1–17 and nothing in Phases
1–17 depends on them. They touch exactly **one** pre-existing file — T463, the sign-in link, behind a
`support_channel_enabled` flag defaulting to false. The PRD's Phase 1 acceptance gate has four checks,
none about students, so Phases 18–25 can be **deferred wholesale at any point** without unpicking
anything already built. If the 2026-10-08 window tightens, this is the block to drop.

**One pre-existing task changes**: T127 builds the sign-in screen; T463 adds the support link to it
behind the flag. Sequence T127 before T463, which the phase ordering already guarantees.

### User Story Dependencies

These stories are **not** fully independent, and pretending otherwise would misrepresent the work.
The PRD build order is a data-dependency chain:

- **US1 (P1)** — depends on Phases 2 + 3 only. **The MVP.**
- **US2 (P2)** — needs US1's accounts to have anyone to scope, plus the T051 fixture
- **US3 (P3)** — needs Phase 2's `Auditor` and US2 for the scoped audit view
- **US4 (P4)** — needs US2's scopes, since sections and students are scoped models
- **US5 (P5)** — needs US4: it resolves into terms, branches, courses, sections, students, enrollments
- **US6 (P6)** — needs US5's pipeline and US2's section scope
- **US7 (P7)** — needs US1–US6 **and US8** to exist, since it verifies their screens
- **US8 (P8)** — needs US5's pipeline and US4's programs. Pure convenience: every course and account
  it creates can already be created by hand, so the release is viable without it

### Within Each User Story

- Tests are written first and must **fail** before implementation
- Rules and models before actions; actions before controllers; controllers before views
- A screen task is complete only when its visual-regression spec passes and axe-core is clean
- Translation keys land with the screen that uses them

### Parallel Opportunities

- **Phase 2 and Phase 3 run fully in parallel** — different files, no shared dependency. This is the single largest parallel win: a backend developer takes Phase 2 while a frontend developer takes Phase 3
- Setup: T005–T009, T014–T016 in parallel
- Phase 2 migrations: T020–T022, T024–T027, T031, T033, T034 in parallel (T018, T023, T028, T029 have ordering dependencies)
- Phase 2 models: T035, T037, T038, T039, T040 in parallel after T036
- **Phase 3 components: T070–T093 are 24 near-independent tasks**, the most parallelizable block in the feature
- Architecture tests T052–T056 and T064–T065 all in parallel
- Every test task marked [P] within a story
- The **EAV spike (Phase 12) runs in parallel with everything** — it touches only its own tables, seeder, and tests, so it can be given to a second developer from the end of Phase 2
- Phase 13's T297, T298, T299, T303, T304, T305 in parallel; Phase 14's T306–T313 tests in parallel; Phase 15's T322–T328 tests in parallel; Phase 16's T336–T343 tests in parallel

---

## Parallel Example: Phase 3 components

```bash
# 24 components, no shared files — launch as capacity allows:
Task: "x-track-button      in resources/views/components/track/button.blade.php"
Task: "x-track-input       in resources/views/components/track/input.blade.php"
Task: "x-track-status-badge in resources/views/components/track/status-badge.blade.php"
Task: "x-track-alert       in resources/views/components/track/alert.blade.php"
Task: "x-track-dialog      in resources/views/components/track/dialog.blade.php"
Task: "x-track-data-table  in resources/views/components/track/data-table.blade.php"
Task: "x-track-sidebar     in resources/views/components/track/sidebar.blade.php"
Task: "x-track-stepper     in resources/views/components/track/stepper.blade.php"
# …and the remaining 16
```

---

## Implementation Strategy

### MVP First (User Story 1 only)

1. Phase 1 Setup
2. Phases 2 **and** 3 in parallel — critical, blocks everything
3. Phase 4 User Story 1
4. **STOP and VALIDATE**: quickstart scenario 1 end to end, plus `us1.spec.ts` and axe
5. Demo: the Super Admin creates accounts and staff sign in, on the approved UI

### Incremental Delivery

| Increment | Adds | Demonstrable outcome |
|---|---|---|
| Setup | Project, tooling, vendored design system, CI | Pipelines green on an empty app |
| + Phases 2 & 13 | Schema incl. programs and the new columns, permissions, audit mechanism, fixture data | Migrations and seeders run |
| + Phase 3 | Tokens, 24 components, shell, visual harness | Component gallery matches the reference; axe clean |
| + US1 & Phase 17 | Accounts, sign-in, last-login | **MVP** — staff have access, on-brand |
| + US2 | Scoping | Real data can be loaded safely |
| + US3 | Audit viewing | Accountability is demonstrable |
| + US4 & Phase 14 | Academic structure incl. programs | Terms, programs, sections, students by hand |
| + US5 & Phase 15 | Central **and registrar** import | PRD Phase 1 gate reachable; the registrar's own file loads unmodified |
| + US6 | Instructor import | Instructors self-serve |
| + US8 (Phase 16) | Bulk course and staff import | Term setup without hand-typing |
| + US7 | Bilingual, a11y, fidelity sweep | Arabic-first users fully served |
| + Phase 12 | EAV capacity answer | Phase 2 planning unblocked |

### Parallel Team Strategy

With the PRD's team — backend developer, frontend developer, UI/UX designer, QA engineer:

1. Everyone on Phase 1 together
2. Then **backend** takes Phase 2 and the US1 → US2 → US3 → US4 → US5 → US6 backend chain in order,
   because each link genuinely depends on the last; **frontend** takes the whole of Phase 3 in
   parallel, then trails one story behind on screens; **designer** reviews the component gallery
   against the reference as T101 baselines land, and signs off `ACCESSIBILITY-DELTA.md` (T283);
   **QA** writes each story's test tasks ahead of its implementation, which the tests-first rule
   requires anyway
3. The EAV spike (Phase 12) goes to whoever has capacity from the end of Phase 2 — it is now ungated
   and is the only fully independent block of work in the feature

---

## Notes

- **500 executable tasks (T001–T500) plus 3 blocked**: 16 Setup · 40 Foundational A · 47 Foundational B (design system) · 31 US1 · 19 US2 · 17 US3 · 34 US4 · 43 US5 · 11 US6 · 16 US7 · 11 Polish · 8 EAV spike · **Amendment 1**: 12 + 16 + 14 + 15 + 3 · **Amendment 2** (support channel, Phases 18–25): 6 Setup · 17 Foundational · 22 US9 · 24 US10 · 10 US11 · 10 US12 · 8 US13 · 13 Retention & hardening
- [P] tasks touch different files with no dependency on incomplete work
- Every task states its exact file path
- Tests must fail before implementation — verify, do not assume
- Commit after each task or logical group; feature branches follow the `NNN-feature-name` prefix
- **403 where 404 is specified is a defect**, not a preference — it discloses that an out-of-scope record exists (constitution I)
- **A hard-coded hex, radius, shadow, or duration is a defect** — the token layer is the only place visual values live (T065)
- Nothing in `design-system/` is ever edited. The one file we add there is `ACCESSIBILITY-DELTA.md`, which records what we deliberately changed and why
- No task in this feature may set a term to `closed`, emit a 423, or expose the Group 6 EAV tables to a user — those belong to later features
- **`status` and `academic_status` are two columns on purpose.** A task that writes one must not touch the other: the withdraw action writes only `status` / `withdrawn_at` / `withdraw_reason`, and the registrar import writes only `academic_status` / `roster_sequence` (FR-111). Collapsing them would let an import erase a withdrawal and its reason
