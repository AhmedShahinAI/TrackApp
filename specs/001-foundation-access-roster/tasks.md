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

- [X] T017 Create the `organizations` migration in `database/migrations/` with `type` enum(`university`,`college`), `name_ar` and `name_en` varchar(255), and a nullable self-referencing `parent_id`
- [X] T018 Create the `users` migration adding `organization_id`, `name_ar` varchar(255), `name_en` varchar(255), `email` varchar(255) **unique on `email` alone so a soft-deleted account's email stays taken**, `password` varchar(255), `locale` char(2) default `ar`, `is_active` boolean default true, `first_login_at` timestamp nullable, and `deleted_at`
- [X] T019 Publish and run the `spatie/laravel-permission` migrations for `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, and `model_has_permissions`, unmodified
- [X] T020 [P] Create the `branches` migration with `organization_id`, `campus_name_ar` and `campus_name_en` varchar(255), `gender` enum(`male`,`female`), `is_active` boolean default true, and `deleted_at`
- [X] T021 [P] Create the `academic_terms` migration with `name_ar`, `name_en`, `academic_year` varchar(9), `semester` enum(`first`,`second`,`summer`), `start_date` and `end_date` dates, `status` enum(`draft`,`active`,`closed`,`archived`) default `draft`, `is_current` boolean default false, and `deleted_at`
- [X] T022 [P] Create the `courses` migration with `organization_id`, `code` varchar(20), `name_ar`, `name_en`, and **unique `(organization_id, code)`**
- [X] T023 Create the `sections` migration with `term_id`, `branch_id`, `course_id` (all restrict-on-delete), `section_number` varchar(20), nullable `instructor_id`, **unique `(term_id, branch_id, course_id, section_number)`**, and secondary indexes `(instructor_id)` and `(branch_id, term_id)`. No `gender` column — it is derived through `branch` (FR-050)
- [X] T024 [P] Create the `coordinator_branches` migration with `user_id` and `branch_id` cascade foreign keys and **unique `(user_id, branch_id)`**
- [X] T025 [P] Create the `section_access_grants` migration with `section_id` and `user_id` cascade, `granted_by`, `granted_at`, and **unique `(section_id, user_id)`**
- [X] T026 [P] Create the `section_instructor_history` migration with `section_id` cascade, `instructor_id`, `assigned_at`, nullable `unassigned_at`, and `changed_by`
- [X] T027 [P] Create the `students` migration with `university_id` varchar(20) **unique**, `name_ar` varchar(255), nullable `name_en` varchar(255), and `deleted_at`
- [X] T028 Create the `section_enrollments` migration with `student_id` and `section_id` restrict, `status` enum(`active`,`withdrawn`) default `active`, `enrolled_at`, nullable `withdrawn_at`, nullable `withdraw_reason` varchar(500), **unique `(student_id, section_id)`**, and index `(section_id, status)`
- [X] T029 Create the `audit_logs` migration with nullable `user_id`, `action` varchar(100), nullable `entity_type` varchar(100), nullable `entity_id`, `old_values` json nullable, `new_values` json nullable, `ip_address` varchar(45), nullable `user_agent` varchar(500), `created_at` and **no `updated_at` column**, plus indexes `(user_id, created_at)`, `(entity_type, entity_id)`, `(action, created_at)`, and `(created_at)`
- [X] T030 Create the audit grant-revocation migration issuing `REVOKE UPDATE, DELETE ON <database>.audit_logs FROM <app_user>`, guarded to no-op when the connection lacks GRANT privileges, per [research.md](./research.md) R-02
- [X] T031 [P] Create the `settings` migration with `key` varchar(100) **unique**, `value` json, and nullable `updated_by`
- [X] T032 Create the `import_jobs` migration with `user_id`, `mode` enum(`central`,`section`), nullable `section_id`, nullable `term_id`, `original_filename` varchar(255), `file_path` varchar(500), `file_hash` char(64), `mapping` json nullable, `options` json nullable, `status` enum(`uploaded`,`mapped`,`validating`,`validated`,`committing`,`completed`,`failed`), unsigned int counters `total_rows`/`processed`/`imported`/`duplicates`/`errors` all default 0, nullable `last_committed_row_id`, nullable `failure_reason` varchar(500), nullable `started_at` and `finished_at`, and indexes `(file_hash, term_id)` and `(user_id, created_at)`
- [X] T033 [P] Create the `import_rows` staging migration with `import_job_id` cascade, `row_number` unsigned int, `raw` json, `resolved` json nullable, `status` enum(`valid`,`warning`,`error`,`committed`,`skipped`), `reasons` json nullable, and indexes `(import_job_id, status)` and `(import_job_id, row_number)`
- [X] T034 [P] Create the `import_errors` migration with `import_job_id` cascade, `row_number` unsigned int, nullable `column` varchar(100), nullable `value` varchar(500), and `reason` varchar(500)

### Shared models

- [X] T035 [P] Create `app/Models/Organization.php` and `app/Models/Setting.php` with their relations and casts
- [X] T036 Create `app/Models/User.php` with `SoftDeletes`, the `HasRoles` trait, an Argon2id-hashed `password` cast, hidden `password`, the `coordinatorBranches` and `taughtSections` relations, the `sectionAccessGrants` relation, and the `coordinatorBranchIds()` and `accessibleSectionIds()` helpers that [research.md](./research.md) R-01 depends on
- [X] T037 [P] Create `app/Models/Branch.php` (SoftDeletes), `app/Models/AcademicTerm.php` (SoftDeletes), and `app/Models/Course.php` with their relations
- [X] T038 [P] Create `app/Models/Section.php`, `app/Models/SectionInstructorHistory.php`, and `app/Models/SectionAccessGrant.php`, with `Section::gender()` reading through the `branch` relation and no stored gender attribute
- [X] T039 [P] Create `app/Models/Student.php` (SoftDeletes) and `app/Models/SectionEnrollment.php` with their relations
- [X] T040 [P] Create `app/Models/AuditLog.php` with `$timestamps = false` except `created_at`, guarded against mass assignment, and **no update or delete path** — no `update()`, `save()` on an existing row, `delete()`, or `forceDelete()` reachable from application code

### Audit write mechanism (foundational — US1 depends on it)

- [X] T041 Implement `app/Modules/Audit/Services/Auditor.php` with a `record(string $action, ?Model $entity, array $old, array $new)` method performing a plain insert on the **current** connection — no `afterCommit`, no queue, no second connection — capturing actor, IP, and user agent from the request, and stripping any `password` key from both payloads
- [X] T042 Implement `app/Modules/Audit/Concerns/Auditable.php` as a model trait wiring `created`, `updated`, and `deleted` observers to `Auditor::record()`, with a per-model `auditedAttributes()` allowlist so no hash or token can enter a payload
- [X] T043 Create `app/Modules/Shared/Actions/TransactionalAction.php`, the base class that wraps `handle()` in `DB::transaction()` so audited actions inherit the constitution II guarantee rather than each remembering it

### Seeders

- [X] T044 Create `database/seeders/OrganizationSeeder.php` inserting exactly **one** `college` record, per constitution "organization hierarchy tables exist from v1 with a single college record"
- [X] T045 Create `database/seeders/PermissionSeeder.php` seeding **47 permissions**: the 35 named in [data-model.md](./data-model.md) Group 1, plus Amendment 1's 7 (including `dashboard.view` per [ui-conflicts.md](./ui-conflicts.md) C-12), Amendment 2's 4 support permissions, and Amendment 4's `section.approve`. `program.create` **is** seeded — C-04 was accepted on 2026-09-27, so the C-12 row calling it blocked is stale. **Corrected 2026-10-06**: data-model.md Group 8 states 48, double-counting `section.create`, which Amendment 4 regrants rather than adds
- [X] T046 Create `database/seeders/RoleSeeder.php` seeding `SUPER_ADMIN` (all permissions including `scope.bypass_all`, `term.edit_closed`, `section.access_override`), `BRANCH_COORDINATOR` (the `*.view` set plus `audit.view`), and `INSTRUCTOR` (`section.view`, `student.view`, `student.create`, `enrollment.create`, `enrollment.withdraw`, `import.section.run`, `import.view`) exactly as tabulated in data-model.md
- [X] T047 [P] Create `database/seeders/SettingSeeder.php` seeding `university_id_pattern` = `^\d{9}$`, `import_max_file_mb` = 20, `import_max_rows` = 50000, `attachment_max_mb` = 10, `default_locale` = `ar`, and `session_timeout_minutes` = 60
- [X] T048 [P] Implement `app/Modules/Settings/Settings.php` as a cached accessor facade for the `settings` table, so no literal for any seeded value appears in application code (constitution IV)
- [X] T049 [P] Create `database/seeders/DevAdminSeeder.php` creating the development Super Admin, refusing to run when `APP_ENV=production`
- [X] T050 [P] Create `database/factories/` factories for `User`, `Branch`, `AcademicTerm`, `Course`, `Section`, `Student`, and `SectionEnrollment`
- [X] T051 [P] Create `database/seeders/UiFixtureSeeder.php` porting the design system's demo dataset from `design-system/ui_kits/web-app/store.js` — 6 branches, 3 terms, 6 courses, 7 users across the three roles including a first-sign-in and a deactivated account, 9 sections including two sharing section number `123` across branches and one with no instructor, 1 access grant, instructor history, 55 students with Arabic names, and enrollments including one withdrawn with a reason. This is the dataset every visual-regression baseline renders against, so the Blade screens and the React reference show identical content

### Architecture tests

- [X] T052 [P] Create `tests/Architecture/NoHardcodedBranchesTest.php` asserting the strings `Zulfi`, `Majmaah`, and `Rumah` appear in no file under `app/`, and that no gender or campus literal is compared in application logic (constitution IV)
- [X] T053 [P] Create `tests/Architecture/EveryRouteAuthorizedTest.php` enumerating registered `/api/v1` routes and failing on any route without authorization middleware or a policy, and on any route lacking a corresponding authorization test (constitution VIII)
- [X] T054 [P] Create `tests/Architecture/LocalizationParityTest.php` recursively diffing the key sets of `lang/ar` and `lang/en` and failing on any key present in one and not the other
- [X] T055 [P] Create `tests/Architecture/NoHardcodedStringsTest.php` scanning `resources/views/` for user-facing text nodes outside `__()` or `@lang`, with an allowlist for numerals, punctuation, and component slots
- [X] T056 [P] Create `tests/Architecture/NoHardDeleteTest.php` asserting no `forceDelete` call and no `DELETE` route exists for `users`, `students`, `branches`, or `academic_terms` (constitution V, FR-049)

**Checkpoint A**: Schema migrates, 36 permissions and 3 roles seed, the audit mechanism records inside the caller's transaction, and the UI fixture dataset loads.

---

## Phase 3: Foundational B — Design System (Blocking)

**Purpose**: Stand up the approved design system before any screen is built

**⚠️ CRITICAL**: Every screen task in Phases 4–10 consumes these components. Building a screen
before this phase would mean rebuilding it. The palette corrections (T060) must land before any
screen, or every screen inherits a failing accessibility gate.

### Tokens, accessibility corrections, and the Bootstrap bridge

- [X] T057 Copy `design-system/tokens/{colors,typography,spacing,base}.css` verbatim to `resources/css/tokens/`, preserving all 141 custom properties and their comments
- [X] T058 Rewrite `resources/css/tokens/fonts.css` as `@font-face` blocks for self-hosted Cairo (400, 500, 600, 700, 800) and Inter (400, 500, 600, 700) replacing the design system's Google Fonts `@import`, per [design-system.md](./design-system.md) §3 — the strict CSP and PDPL forbid a third-party origin on every page load
- [X] T059 Download Cairo and Inter as woff2 subsets (Arabic + Latin + numerals) to `public/fonts/` and add `font-display: swap` plus preload hints for the two weights used above the fold
- [X] T060 Apply the **7 mandatory accessibility corrections** to `resources/css/tokens/colors.css` exactly as tabulated in [design-system.md](./design-system.md) §6: KPI numeral and focus border to `--green-600` `#059669`; green body text to `--green-700` `#047857`; warning text to `--amber-700` `#B45309`; danger badge label to `--red-700` `#B91C1C`; warning badge label to `--amber-800` `#92400E`; `--text-muted` to `--track-gray` `#6B7280`. `#10B981` **stays** the brand green for all non-text use — chart fills, progress bars, the active sidebar indicator, switch fills, badge backgrounds
- [X] T061 Write `specs/001-foundation-access-roster/ACCESSIBILITY-DELTA.md` recording each correction. **Amended 2026-10-06**: the path was `design-system/ACCESSIBILITY-DELTA.md`; nothing in `design-system/` is ever written to, so it sits beside design-system.md instead. **9 corrections, not 7** — Phase 3 found two more (A11Y-08, A11Y-09) by running axe over the built components, both surface-dependent in a way that measuring tokens in isolation could not catch. Each records with its measured before ratio, required ratio, corrected value, corrected ratio, and the WCAG success criterion it satisfies, as the record the design author reviews
- [X] T062 Create `resources/css/_bootstrap-bridge.scss` overriding Bootstrap's Sass variables from the tokens per the mapping table in [design-system.md](./design-system.md) §2 — `$primary`, `$success`, `$danger`, `$warning`, `$secondary`, `$body-bg`, `$body-color`, `$border-color`, `$font-family-sans-serif`, `$font-size-base` 0.875rem, `$h1`–`$h4` at 28/22/18/16px, the four radii at 6/8/12/16px, the three shadows, `$input-btn-focus-box-shadow`, `$spacer` on a 4px base, and `$grid-gutter-width` 14px
- [X] T063 Create `resources/css/app-rtl.scss` and `resources/css/app-ltr.scss` importing tokens, the bridge, Bootstrap, `_shell.scss`, and `_components.scss` in that order so tokens always win. **Amended 2026-10-06**: Bootstrap 5.3 has **no `bootstrap.rtl` Sass entry** — its dist `bootstrap.rtl.css` is `bootstrap.scss` compiled and then mirrored with RTLCSS. Both entries import `bootstrap`, and vite.config.js applies RTLCSS to the Arabic entry only
- [X] T064 [P] Create `tests/Architecture/TokenParityTest.php` asserting every custom property in `design-system/tokens/*.css` exists in `resources/css/tokens/*.css` with an identical value, except the 7 corrections, which must match `ACCESSIBILITY-DELTA.md` exactly — silent drift fails the build
- [X] T065 [P] Create `tests/Architecture/NoHardcodedVisualValuesTest.php` failing on any hex colour, px radius, px shadow, or transition duration in a `.blade.php` or `.scss` file outside `resources/css/tokens/` (constitution IV extended to the visual layer)

### Icons and assets

- [X] T066 Vendor Lucide 0.460.0 as a self-hosted SVG sprite at `public/icons/lucide.svg` containing only the icons in the [design-system.md](./design-system.md) §7 name map plus the component-level icons, with stroke width **1.75** and rounded caps — no CDN request, per C-07
- [X] T067 [P] Create `resources/views/components/track/icon.blade.php` (`x-track-icon`) rendering a sprite reference by kebab-case name with `size` (default 20) and `color` (default `currentColor`), and a single `config/icons.php` name map so replacing the set later touches one file
- [X] T068 [P] Wire the Vite build to copy `design-system/assets/` to `public/img/brand/`, and create `resources/views/components/track/logo.blade.php` (`x-track-logo`) exposing the six approved variants primary / reversed / mono / lockup / mark / mark-reversed with a `height` prop
- [X] T069 [P] Generate the favicon and PWA icon set from `design-system/assets/app-icon-{light,dark,green,black}.png` into `public/` and reference them in the shell layout

### The 24 Blade components

Each task re-authors one design-system component. Read `design-system/components/<group>/<Name>.jsx`
for anatomy and `<Name>.prompt.md` for the behavioural contract — quoted in each task below. Styles
go in `resources/css/_components.scss` using tokens only; behaviour in
`resources/js/components/<name>.js` as an Alpine component.

- [X] T070 [P] Create `x-track-button` in `resources/views/components/track/button.blade.php` — variants primary / accent / secondary / ghost / danger; sizes sm 32 / md 40 / lg 48; `icon` and `iconEnd` by Lucide name with **`icon` on the right in RTL**; `loading` swaps the icon for a spinner and disables the control **holding its width** so layout does not shift; hover darkens filled to `--blue-800`; press `scale(.98)`; focus `--green-600` border + `--ring-focus`
- [X] T071 [P] Create `x-track-icon-button` in `resources/views/components/track/icon-button.blade.php` — square; variants ghost / outline / filled; `dot` adds the red notification dot; **`label` is required** and renders as both `aria-label` and `title`
- [X] T072 [P] Create `x-track-input` in `resources/views/components/track/input.blade.php` — label with required asterisk, optional leading icon, hint, error; sizes sm/md/lg on `--control-h-*`; validates **on blur** and again on submit via `resources/js/components/field-validation.js`; `error` turns the border `--red-600`, replaces the hint, and sets `role="alert"` (FR-078)
- [X] T073 [P] Create `x-track-select` in `resources/views/components/track/select.blade.php` — native select styled to match Input, accepting string or `{value,label}` options, optional leading icon
- [X] T074 [P] Create `x-track-checkbox` in `resources/views/components/track/checkbox.blade.php` — square, filled `--track-blue` when on, label beside it
- [X] T075 [P] Create `x-track-switch` in `resources/views/components/track/switch.blade.php` — toggle, `--track-green` fill when on (non-text use, so `#10B981` is retained per T060)
- [X] T076 [P] Create `x-track-status-badge` in `resources/views/components/track/status-badge.blade.php` — 6px radius pill covering all **19 statuses** in [design-system.md](./design-system.md) §8 with the fixed AR/EN vocabulary from `lang/`; the component **cannot render without its text label**, which makes FR-077 structural rather than reviewed; uses the T060-corrected `--red-700` and `--amber-800` label colours
- [X] T077 [P] Create `x-track-alert` in `resources/views/components/track/alert.blade.php` — tones info / success / warning / danger, **always icon + text**, optional title, optional action slot, optional dismiss; used for the first-sign-in recommendation, the lockout countdown, scope notices, the previously-imported-file warning, and the queue-waiting state
- [X] T078 [P] Create `x-track-toast` in `resources/views/components/track/toast.blade.php` plus `resources/js/components/toast.js` — tones success / error / info, ~4s auto-dismiss, fixed bottom-**start** (bottom-left in RTL), error tone carries a **retry action** (FR-080, FR-081)
- [X] T079 [P] Create `x-track-dialog` in `resources/views/components/track/dialog.blade.php` plus `resources/js/components/dialog.js` — 16px radius, widths 480 (confirm) / 560 (form), teal 45% + 2px blur scrim, footer on a `--gray-50` band, `inline` variant without the overlay; **traps focus and restores it to the trigger on close** (SC-014)
- [X] T080 [P] Create `x-track-progress` in `resources/views/components/track/progress.blade.php` — thin bar with percentage, colour auto-shifting green / blue / red by value, `tone` override, `showLabel` toggle
- [X] T081 [P] Create `x-track-skeleton` in `resources/views/components/track/skeleton.blade.php` — pulsing placeholder with `width` and `height`, honouring `prefers-reduced-motion` (FR-079)
- [X] T082 [P] Create `x-track-tooltip` in `resources/views/components/track/tooltip.blade.php` plus `resources/js/components/tooltip.js` — dark tooltip on hover **and keyboard focus**, for icon buttons and truncated values
- [X] T083 [P] Create `x-track-empty-state` in `resources/views/components/track/empty-state.blade.php` — centred icon + title + message + optional action; also the renderer for full-page 403 and 404 (FR-025, FR-024, FR-079)
- [X] T084 [P] Create `x-track-card` in `resources/views/components/track/card.blade.php` — white, 1px `--border-default`, 12px radius, `--shadow-card`, 20px padding, optional title row with an action slot, `padding=0` variant for cards hosting tabs + table
- [X] T085 [P] Create `x-track-stat-card` in `resources/views/components/track/stat-card.blade.php` — centred KPI: `--fs-stat` 26px bold **Inter** numeral over an Arabic label, tones blue / green / dark, optional icon and trend; the green tone uses the T060-corrected `--green-600`
- [X] T086 Create `x-track-data-table` in `resources/views/components/track/data-table.blade.php` plus `resources/js/components/data-table.js` — gray header, row hover `--blue-50`, `onRowClick`, per-column slot renderers, `numeric` columns in Inter with `tabular-nums`, `dense` variant, `empty` message via `x-track-empty-state`, `loading` state rendering `x-track-skeleton` rows, client sort and filter without reload, and **stacked cards below 768px** (FR-085)
- [X] T087 Create `x-track-sidebar` in `resources/views/components/track/sidebar.blade.php` — fixed 240px, **right in RTL** / left in LTR, `--surface-sidebar` gradient, `x-track-logo` variant reversed at top, items 42px tall at 8px radius with 19px icons, active item `--surface-sidebar-active` with a green indicator, hover white 8%, footer slot for logout, and an off-canvas drawer below 768px
- [X] T088 Create `x-track-topbar` in `resources/views/components/track/topbar.blade.php` — 64px white with a hairline bottom border, drawer toggle, search field, notifications `x-track-icon-button` with dot, help, user block showing name + role linking to the profile, and an `end` slot for the language switch
- [X] T089 [P] Create `x-track-tabs` in `resources/views/components/track/tabs.blade.php` plus `resources/js/components/tabs.js` — variants underline (green indicator) and segmented (gray track), optional per-tab counts, full keyboard support with arrow keys
- [X] T090 [P] Create `x-track-breadcrumbs` in `resources/views/components/track/breadcrumbs.blade.php` — drill-down path above every detail title, **chevron direction following `dir`** (FR-083)
- [X] T091 [P] Create `x-track-stepper` in `resources/views/components/track/stepper.blade.php` — the 6 import steps; done steps green with a check, active step `--track-blue` with a soft ring, upcoming steps gray; `aria-current="step"` on the active one
- [X] T092 [P] Create `resources/views/components/track/page-header.blade.php` — breadcrumbs slot → h1 `--text-h1` → optional 14px `--text-secondary` subtitle → right-aligned action cluster, 20px bottom margin, per [design-system.md](./design-system.md) §4
- [X] T093 [P] Create `resources/views/components/track/panel.blade.php` and `field.blade.php` — the two layout primitives the reference kit uses throughout (`Panel` = bordered white section; `Field` = label + control + error/hint wrapper)

### Shell, locale, and motion

- [X] T094 Implement `app/Modules/Shared/Http/Middleware/SetLocale.php` resolving locale from `users.locale`, falling back to the `default_locale` setting, and register it on the `web` group
- [X] T095 Create `resources/views/layouts/app.blade.php` emitting `<html lang dir>` from the active locale, loading the matching stylesheet, composing `x-track-sidebar` + `x-track-topbar` + a scrolling content region at `28px 28px 48px`, plus a **skip link**, the flash-message region, and the first-sign-in banner slot
- [X] T096 [P] Create `resources/css/_shell.scss` implementing the §4 layout geometry — `--sidebar-width` 240px, `--topbar-height` 64px, 14px grid gaps, and the <768px drawer and stacked-card breakpoints at 1280 / 1920 verification widths
- [X] T097 [P] Create `resources/js/app.js` registering every Alpine component from `resources/js/components/`, plus the locale switcher and the **5-second poller** for queued work (FR-079)
- [X] T098 [P] Add the global motion rules to `resources/css/tokens/base.css` — 120 / 180 / 280ms on `cubic-bezier(.2,0,0,1)`, fades, colour, and progress width only, no bounce or parallax, all wrapped in a `prefers-reduced-motion: reduce` guard that disables them
- [X] T099 [P] Create the `lang/ar/` and `lang/en/` files for `common`, `status`, and `nav`, seeding the fixed status vocabulary from [design-system.md](./design-system.md) §8 and the navigation nouns from the design system's content rules

### Fidelity harness

- [X] T100 Create `resources/views/dev/component-gallery.blade.php`, a route available only when `APP_ENV=local`, rendering every `x-track-*` component in every variant and state — the surface the component visual-regression suite screenshots
- [X] T101 Create `tests/Visual/components.spec.ts` screenshotting each of the 27 components in the gallery in both locales at **≤ 1% pixel difference**. **Amended 2026-10-06**: `design-system/components/**/*.card.html` **does not exist** — the kit ships `.jsx` sources and `.prompt.md` contracts, and its only rendered artifact is the whole-app page, which has no per-component view. Baselines pin the Blade rendering and fail on drift; fidelity to the reference stays the human gate design-system.md §13 already defines, with the automatable structural comparison in `tests/Visual/reference.spec.ts`. The per-pixel `threshold` was also **0.2, which admitted any colour delta below 1409** — the §6 accent correction is 1335, so the gate could not have detected the corrections themselves. Now 0.05
- [X] T102 [P] Create `tests/Visual/axe.spec.ts` running axe-core over the component gallery in both locales, requiring **zero** WCAG 2.1 AA violations
- [X] T103 [P] Create `tests/Visual/helpers/render-reference.ts` that boots `design-system/ui_kits/web-app/index.html` with the T051 fixture data and a deterministic locale, so reference screenshots are reproducible rather than depending on `localStorage` state

**Checkpoint B**: The design system is live — 141 tokens (7 corrected), 24 components, the shell in both directions, self-hosted fonts and icons, and a visual-regression harness that fails on drift. Screen work can begin.

---

## Phase 4: User Story 1 - Staff Get Accounts And Sign In (Priority: P1) 🎯 MVP

**Goal**: Super Admin creates coordinator and instructor accounts; those people sign in, see a non-blocking first-sign-in recommendation, change their own password, and are refused when deactivated — with authorship preserved.

**Independent Test**: Create one coordinator and one instructor, sign in as each, change a password, deactivate one and confirm sign-in is refused while their name still appears on records they created.

### Tests for User Story 1

- [X] T104 [P] [US1] Write `tests/Feature/Authentication/LoginTest.php` covering successful sign-in with session regeneration and role-based landing, the generic failure message that never reveals which field was wrong (FR-009), the inactive-account refusal (FR-011), and 60-minute idle expiry (FR-016)
- [X] T105 [P] [US1] Write `tests/Feature/Authentication/LoginThrottleTest.php` asserting five failed attempts per email + IP within one minute block the sixth for 60 seconds with a countdown (FR-010)
- [X] T106 [P] [US1] Write `tests/Feature/Authentication/FirstLoginTest.php` asserting the recommendation appears only when `first_login_at` was null before the request, and not on subsequent sign-ins (FR-012)
- [X] T107 [P] [US1] Write `tests/Feature/Authentication/PasswordChangeTest.php` covering the policy, the wrong-current-password refusal, the new-equals-current refusal (FR-014), and that other sessions are invalidated while the current one survives (FR-015)
- [X] T108 [P] [US1] Write `tests/Feature/UserManagement/CreateUserTest.php` covering creation with both name languages and a role, required `branch_ids` for a coordinator (FR-004), the duplicate-email refusal including against a soft-deleted account (FR-005), and that no password or hash appears in the response or the audit payload (FR-007)
- [X] T109 [P] [US1] Write `tests/Feature/UserManagement/DeactivateUserTest.php` asserting sign-in is refused afterwards, all sessions are invalidated, the row is not erased, and the person's name still renders on records they created (FR-018, FR-049)
- [X] T110 [P] [US1] Write `tests/Feature/UserManagement/ResetPasswordTest.php` asserting all target sessions are invalidated and the password appears in neither audit payload
- [X] T111 [P] [US1] Write `tests/Feature/Authentication/AbsentRoutesTest.php` asserting `/register`, `/password/forgot`, `/password/reset`, and any 2FA path each return 404 (FR-001)
- [X] T112 [P] [US1] Write `tests/Authorization/UserEndpointsTest.php` with an allow case and a deny case for each of the 12 endpoints in [contracts/auth-users.md](./contracts/auth-users.md)
- [X] T113 [P] [US1] Write `tests/Unit/PasswordPolicyTest.php` asserting the ≥ 8 characters, ≥ 1 letter, ≥ 1 digit rule (FR-006)
- [X] T114 [P] [US1] Write `tests/Visual/us1.spec.ts` screenshotting login (default, error, deactivated, lockout-countdown), the first-sign-in banner, the home landing, the users list, the create/edit dialog, the deactivate/reset confirmations, and profile/password — both locales. **Amended 2026-10-06**: as with T101, the `AuthScreens`/`HomeScreen`/`UsersScreen` references are `.jsx` sources with no per-screen rendered artifact, so baselines pin the Blade rendering and reference fidelity stays the §13 human gate. The four interaction-driven cases (lockout, and the three dialogs) run at 1440 only: below 768px the data table stacks and the row-action buttons are present but not visible, so a click waits for a visibility that never comes

### Backend implementation for User Story 1

- [X] T115 [P] [US1] Implement `app/Modules/Shared/Rules/PasswordPolicy.php` as the single rule object used by create, change, and reset, so the three paths cannot drift
- [X] T116 [US1] Implement `app/Modules/Authentication/Http/Controllers/LoginController.php` with `POST /auth/login` and `POST /auth/logout` per contracts: session regeneration, the generic 422, the inactive 403, `first_login_at` set during the request, and the role landing route
- [X] T117 [US1] Register the login rate limiter keyed on `email` + IP at 5 per minute in `app/Providers/AppServiceProvider.php`, returning 429 with `retry_after`. **Note**: the named limiter landed in Phase 1 (F-04 fix); this phase adds the explicit `RateLimiter::tooManyAttempts` check **before** the credential check in LoginController, so attempt six teaches an attacker nothing, plus the `retry_after` flash the screen counts down from
- [X] T118 [P] [US1] Implement `app/Modules/Authentication/Http/Controllers/MeController.php` with `GET /auth/me` returning roles, permissions, and the resolved `scope` object, computed per request so a scope change takes effect immediately (FR-027)
- [X] T119 [US1] Implement `app/Modules/Authentication/Actions/ChangePassword.php` and `PasswordController.php` with `PUT /auth/password`, invalidating other sessions via `logoutOtherDevices` and auditing `user.password_changed`
- [X] T120 [US1] Implement `app/Modules/UserManagement/Actions/CreateUser.php` extending `TransactionalAction`, hashing with Argon2id, defaulting `locale` from the `default_locale` setting, attaching roles and coordinator branches, and auditing `user.created` with the password stripped
- [X] T121 [US1] Implement `app/Modules/UserManagement/Http/Requests/StoreUserRequest.php` and `UpdateUserRequest.php` enforcing unique email across soft-deleted rows, `PasswordPolicy`, and `branch_ids` required when the role is `BRANCH_COORDINATOR`
- [X] T122 [US1] Implement `app/Modules/UserManagement/Http/Controllers/UserController.php` with `GET /users`, `POST /users`, and `PUT /users/{id}`, never exposing any password field
- [X] T123 [US1] Implement `app/Modules/UserManagement/Actions/DeactivateUser.php` and `PATCH /users/{id}/deactivate`, invalidating the target's sessions, refusing self-deactivation with 409, and auditing `user.deactivated`
- [X] T124 [US1] Implement `app/Modules/UserManagement/Actions/ResetPassword.php` and `POST /users/{id}/reset-password`, invalidating **all** target sessions and auditing `user.password_reset`
- [X] T125 [P] [US1] Implement `app/Modules/UserManagement/Policies/UserPolicy.php` mapping each action to its `user.*` permission, and register it in `app/Providers/AuthServiceProvider.php`
- [X] T126 [US1] Add `SESSION_LIFETIME=60` enforcement plus HttpOnly, Secure, and `SameSite=Lax` cookie settings in `config/session.php`, and confirm `/register` is absent from `routes/web.php` and `routes/api_v1.php`

### Screens for User Story 1 — built to the design system

Each screen task carries the §10 state matrix as its checklist: default, loading, empty,
validation, error, success, confirmation, focus, hover, press, disabled, motion, RTL/LTR, <768px,
keyboard.

- [X] T127 [US1] Build `resources/views/auth/login.blade.php` to the `AuthScreens.jsx` reference — split layout with `hero-campus.png` and the teal protection gradient, `x-track-logo` lockup, `x-track-input` for email and password with a leading icon, `x-track-button` primary full-width with `loading`, the language switch, and `x-track-alert` danger for the generic failure and the 60-second lockout countdown
- [X] T128 [P] [US1] Build `resources/views/components/track/first-login-banner.blade.php` as a dismissable non-blocking `x-track-alert` tone warning with an action linking to the password-change screen, matching the reference's copy "يُنصح بتغيير كلمة المرور الأولية" (FR-012)
- [X] T129 [P] [US1] Build `resources/views/profile/index.blade.php` and `password.blade.php` to the `ProfileScreen` reference — profile details, last-sign-in display, and the change-password form with `x-track-input` blur validation on all three fields
- [X] T130 [US1] Build `resources/views/home/index.blade.php` to the `HomeScreen` reference — the role landing page (FR-017) with **6 `x-track-stat-card`** tiles (branches, sections, students, users, enrollments, imports), an `x-track-card` of recent activity via `x-track-data-table` dense, a quick-actions card, and the first-sign-in banner; each role sees only its own in-scope counts
- [X] T131 [US1] Build `resources/views/users/index.blade.php` to the `UsersScreen` reference — `x-track-page-header` with a create action, filter row of `x-track-select` for role/branch/status plus a search `x-track-input`, and `x-track-data-table` with `x-track-status-badge` for active/inactive, role labels, branch labels, last sign-in, and row actions
- [X] T132 [US1] Build `resources/views/users/_dialog.blade.php` to the `UserDialog` reference — `x-track-dialog` at 560px, a two-column grid of `x-track-input` for both names and email, `x-track-select` for role, a conditional multi-select of branches shown only for `BRANCH_COORDINATOR`, and inline errors on blur
- [X] T133 [P] [US1] Build the deactivate and reset-password confirmations as `x-track-dialog` danger variants restating the impact — "سيتم إنهاء جميع جلسات المستخدم" and the preserved-history note (FR-082)
- [X] T134 [P] [US1] Add the `auth`, `users`, `profile`, and `home` translation keys to `lang/ar/` and `lang/en/`, taking the Arabic strings from `design-system/ui_kits/web-app/i18n.js` so the approved copy register is preserved verbatim

**Checkpoint**: User Story 1 is fully functional, visually matches the reference, and is testable on its own — the MVP.

---

## Phase 5: User Story 2 - Each Role Sees Only Its Own Data (Priority: P2)

**Goal**: Every list, search, export, and directly typed address is limited to the caller's branches or sections; out-of-scope records behave as nonexistent; scope changes apply on the next request.

**Independent Test**: Sign in as each of two coordinators and confirm every access path shows only their own branch, and that the other branch's records return 404 rather than 403.

### Tests for User Story 2

- [X] T135 [P] [US2] Write `tests/Scope/OutOfScopeReturns404Test.php` asserting **404, never 403** for a cross-branch section, student, enrollment, import job, and audit entry, through the API route, the Blade route, the list endpoint, and the export endpoint (FR-024)
- [X] T136 [P] [US2] Write `tests/Scope/CoordinatorBranchScopeTest.php` asserting a coordinator's lists and their totals contain only their branches' records
- [X] T137 [P] [US2] Write `tests/Scope/InstructorSectionScopeTest.php` asserting an instructor sees only sections where they are `instructor_id`, plus granted sections flagged `shared_access`
- [X] T138 [P] [US2] Write `tests/Scope/ExportRespectsScopeTest.php` asserting no export can contain a record the same caller cannot retrieve individually (FR-023)
- [X] T139 [P] [US2] Write `tests/Scope/ScopeChangeTakesEffectImmediatelyTest.php` asserting a role or branch change is in force on the target's next request with no re-authentication and no stale cached permission set (FR-027)
- [X] T140 [P] [US2] Write `tests/Feature/Authorization/MissingPermissionReturns403Test.php` asserting a **visible** record with a missing permission yields 403 plus an `access.denied` audit entry — so 403 and 404 are never conflated
- [X] T141 [P] [US2] Write `tests/Feature/Authorization/NewRoleNeedsNoCodeChangeTest.php` creating a fourth role by seeding permissions only, and asserting it works with no policy or screen modification (FR-020)
- [X] T142 [P] [US2] Write `tests/Authorization/AccessGrantEndpointTest.php` with allow and deny cases for `POST /sections/{id}/access-grants`
- [X] T143 [P] [US2] Write `tests/Visual/us2.spec.ts` screenshotting the full-page 403 and 404, the shared-access badge in the section list, and each role's sidebar against the `RefusalScreen` and `Shell` references, both locales

### Implementation for User Story 2

- [X] T144 [US2] Implement `app/Modules/Authorization/Scopes/BranchScope.php` as an Eloquent **global** scope constraining `branch_id` directly or through the `section` relation to `coordinatorBranchIds()`, inert for `scope.bypass_all` holders and when no user is authenticated
- [X] T145 [US2] Implement `app/Modules/Authorization/Scopes/SectionScope.php` constraining to `accessibleSectionIds()` — the union of taught sections and `section_access_grants` — with the same two inert conditions
- [X] T146 [US2] Implement the `BelongsToBranchScope.php` and `BelongsToSectionScope.php` traits in `app/Modules/Authorization/Concerns/` and apply them to `Section`, `Student`, `SectionEnrollment`, `ImportJob`, and `AuditLog`
- [X] T147 [US2] Configure route-model binding in `app/Providers/RouteServiceProvider.php` to resolve **through** the scoped query so an out-of-scope id raises `ModelNotFoundException` → 404 before any policy runs, per [research.md](./research.md) R-01
- [X] T148 [US2] Implement `app/Modules/Authorization/Http/Middleware/AuditDeniedAccess.php` recording `access.denied` on a 403 or on a 404 arising from a scope miss, capturing the attempted route and id
- [X] T149 [P] [US2] Implement `app/Modules/Authorization/Actions/GrantSectionAccess.php` and `POST /sections/{id}/access-grants`, auditing `section.access_granted`
- [X] T150 [P] [US2] Add the `shared_access` computed attribute to `app/Models/Section.php` and render it as `x-track-status-badge status="shared"` in the section list (FR-026)
- [X] T151 [P] [US2] Implement `PUT /users/{id}/roles` and `PUT /users/{id}/branches` in `UserRoleController.php` and `UserBranchController.php`, auditing `user.roles_changed` and `user.branches_changed` with previous and new sets
- [X] T152 [US2] Build `resources/views/errors/403.blade.php` and `404.blade.php` to the `RefusalScreen` reference — full-page `x-track-empty-state` with a `shield-off` / `file-question` icon, the approved Arabic copy, and a link back to the caller's landing page, exposing no internal detail (FR-025, FR-080)
- [X] T153 [P] [US2] Implement the per-role navigation map in `config/navigation.php` matching the reference `NAV` exactly — Super Admin: home, dashboard\*, initiatives\*, users, terms, branches, courses, sections, imports, audit · Coordinator: home, initiatives\*, sections, imports, audit · Instructor: home, mysections, imports. Entries marked \* are **stubbed** to a "coming in a later phase" placeholder so the sidebar matches the approved design without dead links. `programs` is **omitted** pending C-04

**Checkpoint**: Stories 1 and 2 work independently. Cross-branch data is unreachable through every access path.

---

## Phase 6: User Story 3 - Every Sensitive Change Is Accountable (Priority: P3)

**Goal**: The audit trail is queryable, filterable, exportable, visible per entity within scope, and provably immutable and transactional.

**Independent Test**: Perform one account creation, one role change, and one refused access attempt; confirm exactly three complete entries exist, that filtering finds them, and that no path edits or removes them.

### Tests for User Story 3

- [X] T154 [P] [US3] Write `tests/Feature/Audit/TransactionalWriteTest.php` forcing the audit insert to fail mid-action and asserting the business row is **absent** afterwards and the caller is told to retry (FR-030)
- [X] T155 [P] [US3] Write `tests/Feature/Audit/ImmutabilityTest.php` asserting all three layers independently: no update or delete path in code, no `updated_at` column, and — where the connection has privileges — that a direct `UPDATE` on `audit_logs` is refused for the application user (FR-031)
- [X] T156 [P] [US3] Write `tests/Feature/Audit/ActionCoverageTest.php` performing each of the 34 action keys tabulated in [contracts/audit.md](./contracts/audit.md) once and asserting exactly one entry each with the correct key (FR-028)
- [X] T157 [P] [US3] Write `tests/Feature/Audit/NoSecretsInPayloadTest.php` asserting no audit payload contains a `password`, hash, session token, or CSRF token key
- [X] T158 [P] [US3] Write `tests/Feature/Audit/OldAndNewValuesTest.php` asserting both previous and new values are present for every changed field on an edit
- [X] T159 [P] [US3] Write `tests/Scope/AuditLogScopeTest.php` asserting a coordinator sees only entries resolving into their branches plus their own actions, and cannot widen `branch_id`
- [X] T160 [P] [US3] Write `tests/Authorization/AuditEndpointsTest.php` with allow and deny cases for `GET /audit-logs`, `GET /audit-logs/export`, and `GET /{entity}/{id}/activity`
- [X] T161 [P] [US3] Write `tests/Performance/AuditLogFilterTest.php` seeding 1,000,000 entries and asserting a person + date-range filter returns in under 2 seconds (SC-004), in the `performance` group, excluded from the default run
- [X] T162 [P] [US3] Write `tests/Visual/us3.spec.ts` screenshotting the audit list with filters applied, the before/after detail expansion, the immutability notice, and an activity tab against the `AuditScreen` reference, both locales

### Implementation for User Story 3

- [X] T163 [US3] Implement `app/Modules/Audit/Http/Controllers/AuditLogController.php` with `GET /audit-logs` supporting the `user_id`, `action`, `entity_type`, `entity_id`, `date_from`, `date_to`, and `branch_id` filters, served by the T029 indexes
- [X] T164 [P] [US3] Implement `app/Modules/Audit/Http/Resources/AuditLogResource.php` adding server-localized `action_label` and `entity_label` while keeping the raw `action` key stable for filtering
- [X] T165 [P] [US3] Implement `app/Modules/Audit/Exports/AuditLogExport.php` and `GET /audit-logs/export` producing an Arabic-safe `.xlsx` whose rows match the list endpoint exactly for the same filters, with a header block carrying the applied filters, timestamp, and requesting user — auditing `audit.exported`
- [X] T166 [US3] Implement `app/Modules/Audit/Http/Controllers/EntityActivityController.php` with `GET /{entity}/{id}/activity` for terms, branches, sections, and students, returning 404 when the entity is out of scope so it cannot be used to probe (FR-033)
- [X] T167 [P] [US3] Apply the `Auditable` trait and an `auditedAttributes()` allowlist to `User`, `Branch`, `AcademicTerm`, `Course`, `Section`, `Student`, and `SectionEnrollment`
- [X] T168 [US3] Build `resources/views/audit/index.blade.php` to the `AuditScreen` + `AuditTable` reference — `x-track-page-header` with an export action, a filter `x-track-card` holding actor/action/entity/date-range `x-track-select` and date inputs, `x-track-data-table` with monospace-numeric timestamps and an expandable before/after JSON diff row, and a persistent `x-track-alert` tone info stating entries cannot be modified or deleted
- [X] T169 [P] [US3] Build `resources/views/components/track/activity-tab.blade.php` as an `x-track-tabs` panel embedding the scoped activity table, reused on term, branch, section, and student detail pages
- [X] T170 [P] [US3] Add the `audit` translation keys — one `action_label` per action key in [contracts/audit.md](./contracts/audit.md) — to `lang/ar/audit.php` and `lang/en/audit.php`, taking the Arabic labels from the reference kit's audit strings per [ui-conflicts.md](./ui-conflicts.md) C-11

**Checkpoint**: The audit trail is complete, queryable, and provably immutable before records exist at scale.

---

## Phase 7: User Story 4 - The Academic Structure Is Set Up (Priority: P4)

**Goal**: Terms, branches, courses, sections, students, and enrollments are manageable, with the section key unique, one current term, instructor changes preserving authorship, and soft delete throughout.

**Independent Test**: Create a term, two branches, one course, two sections sharing a section number across branches, one student enrolled in both; withdraw from one and reassign one instructor.

### Tests for User Story 4

- [X] T171 [P] [US4] Write `tests/Feature/Terms/TermLifecycleTest.php` covering creation in `draft`, the permitted transitions from [data-model.md](./data-model.md), and 409 on a disallowed transition such as `draft → archived`
- [X] T172 [P] [US4] Write `tests/Feature/Terms/SingleCurrentTermTest.php` asserting exactly one row has `is_current = true` after sequential and concurrent set-current attempts (FR-036)
- [X] T173 [P] [US4] Write `tests/Feature/Terms/ClosedStatusStorableTest.php` asserting the enum accepts `closed` and `archived` and that **no seeder or screen in this feature sets a term to `closed`**, per plan.md Complexity Tracking
- [X] T174 [P] [US4] Write `tests/Feature/Sections/SectionUniquenessTest.php` asserting the same `section_number` coexists across differing term, branch, or course, and that a genuine duplicate is refused **at the database index level** as well as by the form request (FR-041)
- [X] T175 [P] [US4] Write `tests/Feature/Sections/ChangeInstructorTest.php` asserting access moves, `section_instructor_history` gains a closed row and a new open row, and **every existing record keeps its original author** (FR-048, SC-018)
- [X] T176 [P] [US4] Write `tests/Feature/Students/SingleStudentRecordTest.php` asserting one `students` row per University ID across multiple enrollments, and that a student appears once in a scoped list despite several enrollments (FR-043, SC-012)
- [X] T177 [P] [US4] Write `tests/Feature/Students/WithdrawEnrollmentTest.php` asserting the reason is mandatory, the date is recorded, the student record survives, and 409 on an already-withdrawn enrollment (FR-047)
- [X] T178 [P] [US4] Write `tests/Feature/AcademicStructure/NoHardDeleteTest.php` asserting a branch or term holding records refuses permanent removal and offers only deactivation or archiving (FR-049)
- [X] T179 [P] [US4] Write `tests/Feature/Sections/TermRequiredFirstTest.php` asserting section creation returns 409 with the explanatory message when no term exists (FR-037)
- [X] T180 [P] [US4] Write `tests/Authorization/AcademicEndpointsTest.php` with allow and deny cases for all 18 endpoints in [contracts/academic.md](./contracts/academic.md)
- [X] T181 [P] [US4] Write `tests/Unit/UniversityIdRuleTest.php` asserting the rule reads `university_id_pattern` from settings, defaults to `^\d{9}$`, and produces a message naming the expected format (FR-044)
- [X] T182 [P] [US4] Write `tests/Visual/us4.spec.ts` screenshotting the terms, branches, courses, sections, and students lists, the section detail with its roster and activity tabs, the student detail, and each create/edit/confirm dialog against the `StructureScreens` and `SectionsScreens` references, both locales, including the <768px stacked-card table

### Backend implementation for User Story 4

- [X] T183 [P] [US4] Implement `app/Modules/Shared/Rules/UniversityId.php` as the single rule used by both manual entry and the import row validator
- [X] T184 [US4] Implement `app/Modules/AcademicTerms/Actions/SetCurrentTerm.php` extending `TransactionalAction`, clearing the previous flag and setting the new one in one transaction, and auditing `term.set_current` naming both terms
- [X] T185 [US4] Implement `app/Modules/AcademicTerms/Actions/ChangeTermStatus.php` enforcing the state machine, requiring a reason when leaving `closed`, returning 409 on a disallowed transition, and auditing `term.status_changed`
- [X] T186 [US4] Implement `app/Modules/AcademicTerms/Http/Controllers/TermController.php` with `GET`, `POST`, `PUT /terms`, `PATCH /terms/{id}/status`, and `PATCH /terms/{id}/set-current`, excluding archived terms from the default list
- [X] T187 [P] [US4] Implement `app/Modules/AcademicStructure/Http/Controllers/BranchController.php` with `GET`, `POST`, and `PUT /branches`, where `is_active = false` is the only removal path, auditing `branch.created`, `branch.updated`, and `branch.deactivated`
- [X] T188 [P] [US4] Implement `app/Modules/AcademicStructure/Http/Controllers/CourseController.php` with `GET` and `POST /courses`, refusing a duplicate `(organization_id, code)` with 422
- [X] T189 [US4] Implement `app/Modules/Sections/Http/Requests/StoreSectionRequest.php` producing the localized message "توجد شعبة بهذا الرقم لنفس المقرر والفرع والفصل", with the unique index as the authority
- [X] T190 [US4] Implement `app/Modules/Sections/Http/Controllers/SectionController.php` with `GET`, `POST`, and `PUT /sections`, returning branch `gender` as a derived field and `shared_access` where applicable
- [X] T191 [US4] Implement `app/Modules/Sections/Actions/ChangeSectionInstructor.php` extending `TransactionalAction`: update `instructor_id`, close the open history row's `unassigned_at`, insert a new open row, never rewrite any existing record's author, and audit `section.instructor_changed`
- [X] T192 [P] [US4] Implement `app/Modules/Students/Http/Controllers/StudentController.php` with `GET /students` (distinct across enrollments) and `GET /students/{id}` returning enrollments plus an empty initiative-history placeholder
- [X] T193 [US4] Implement `app/Modules/Students/Actions/EnrollStudent.php` linking an existing University ID rather than duplicating, keeping the stored name on a mismatch and returning a `warnings` array, and auditing `student.created` when new plus `enrollment.created` always
- [X] T194 [US4] Implement `app/Modules/Students/Actions/WithdrawEnrollment.php` requiring a reason, setting `withdrawn_at`, preserving the student record, and auditing `enrollment.withdrawn`
- [X] T195 [P] [US4] Implement the policies `TermPolicy`, `BranchPolicy`, `CoursePolicy`, `SectionPolicy`, `StudentPolicy`, and `EnrollmentPolicy` under their modules and register them in `app/Providers/AuthServiceProvider.php`

### Screens for User Story 4 — built to the design system

- [X] T196 [US4] Build `resources/views/terms/index.blade.php` and `_dialog.blade.php` to the `TermsScreen` + `TermDialog` reference — list with `x-track-status-badge` for draft/active/closed/archived plus a `current` badge, section counts, a status-change `x-track-dialog` confirming the transition, and a set-current confirmation restating that the previous current term will be cleared
- [X] T197 [P] [US4] Build `resources/views/branches/index.blade.php` and `_dialog.blade.php` to the `BranchesScreen` reference — campus name in both languages, a gender `x-track-select`, an active `x-track-switch`, section and student counts, and an `x-track-alert` tone info stating a branch with sections is deactivated rather than deleted
- [X] T198 [P] [US4] Build `resources/views/courses/index.blade.php` and `_dialog.blade.php` to the `CoursesScreen` reference — code in LTR, both names, section count, and the duplicate-code inline error
- [X] T199 [US4] Build `resources/views/sections/index.blade.php` to the `SectionsScreen` reference — filter row of term/branch/course/instructor `x-track-select` plus search, `x-track-data-table` showing section number (numeric), course, branch with derived gender, instructor, enrollment count, and the `shared` badge; the instructor variant (`mysections`) drops the branch filter and shows `x-track-empty-state` "لم تُسند إليك أي شعبة بعد" when empty
- [X] T200 [US4] Build `resources/views/sections/show.blade.php` to the `SectionDetail` + `SectionMeta` + `RosterTable` reference — `x-track-breadcrumbs`, a metadata panel, and `x-track-tabs` for Roster / Instructor history / Activity, with the roster table carrying per-row withdraw actions and the withdrawn rows rendered greyed with their reason
- [X] T201 [P] [US4] Build `resources/views/sections/_dialog.blade.php` and `_assign-dialog.blade.php` to the `SectionDialog` + `AssignDialog` reference — create/edit with the duplicate-key error, and instructor change as a confirmation restating that access moves while past entries keep their author
- [X] T202 [P] [US4] Build `resources/views/students/index.blade.php` and `show.blade.php` to the `StudentsScreen` + `StudentDetail` reference — University ID in LTR numerics, both names, enrollment count, and the detail page's enrollment table plus activity tab
- [X] T203 [P] [US4] Build the enroll-student and withdraw-student dialogs — enroll with University ID validation and the name-mismatch warning, withdraw with a **mandatory** reason textarea and a danger confirmation (FR-047, FR-082)
- [X] T204 [P] [US4] Add the `terms`, `branches`, `courses`, `sections`, and `students` translation keys to `lang/ar/` and `lang/en/`, taking Arabic strings from the reference `i18n.js`

**Checkpoint**: Real academic data can be created and maintained by hand, matching the approved UI.

**Post-review corrections (2026-10-08).** An engineering review of this phase found fifteen issues
over a green suite. The eleven that were fixed are recorded here because each one marks a place
where a test passed while the behaviour it named was absent — which is the only thing worth
remembering about them. Regression cover lives in
`tests/Feature/AcademicStructure/ReviewRegressionsTest.php`, and every test there was confirmed to
fail against the original code before being kept.

- **Enrolment 500'd for every role it was built for.** `EnrollStudent` looked the student up through
  the *scoped* `Student` query, so a coordinator enrolling a student from another campus never found
  them, created a second row and hit the unique index. The phase's tests all acted as `SUPER_ADMIN`,
  who bypasses the scope. Same fault, same fix, in the existing-enrolment lookup.
- **No dialog could be submitted from a browser.** All eight dialogs post as ordinary forms to
  `api.v1.*` routes that returned JSON unconditionally, and Laravel's default renders validation
  errors as JSON for anything under `api/*`. Every write test used `postJson`, so the one caller
  that already worked was the only one exercised. Fixed with `RespondsToBoth` and by making content
  negotiation, not the URL prefix, decide the response format.
- **Instructor history was unmet in both directions.** Section creation opened no history row, so the
  founding instructor appeared nowhere; and `PUT /sections/{id}` mass-assigned `instructor_id`,
  moving access with no history and no `section.instructor_changed`. `ChangeInstructorTest` passed
  because it seeded by hand the row the application never created — the factory now opens it, so
  tests run against a section that could actually exist.
- **A settings-held regex was interpolated raw.** A pattern containing `/` closed the regex early and
  500'd every enrolment and every import row; an unanchored one accepted `xx441234567yy`.
- **Writes were not scoped.** `exists:branches,id` let a section be created in any campus;
  `organizationId()` returned the first college rather than the acting user's.
- Also: `filterOptions()` hydrated every visible section on each render, the roster loaded unbounded
  with one withdraw dialog per student, `is_active === false` missed a form-encoded `"0"`,
  re-enrolment audited `enrollment.updated`, `SetCurrentTerm` locked zero rows when no term was
  current, and the instructor empty-state was shown to administrators.

Four findings were **not** fixed and remain open, deliberately: the withdraw route path differs from
`contracts/academic.md`, policies ignore their model argument (scoping rests on global scopes),
`sections` has no `deleted_at` so soft delete is not "throughout", and `createOptions()` still loads
five reference tables whole.

---

## Phase 8: User Story 5 - A Whole Roster Loads From One File (Priority: P5)

**Goal**: The six-step central import validates everything before writing anything, commits in resumable 500-row chunks, links rather than duplicates, and produces a downloadable per-row error report.

**Independent Test**: Upload one file mixing valid rows with every defect category; confirm nothing is written before confirmation, every defect yields a row-level reason, and a re-upload creates zero new records.

### Tests for User Story 5

- [X] T205 [P] [US5] Write `app/Console/Commands/MakeRosterFixture.php` (`track:make-roster-fixture --rows= --with-defects`) emitting an `.xlsx` containing valid rows plus in-file duplicates, malformed University IDs, missing required values, an unknown branch, an unknown course, an unregistered instructor, a name mismatch, and an unresolvable section
- [X] T206 [P] [US5] Write `app/Console/Commands/CoreCounts.php` (`track:core-counts`) printing row counts for sections, students, enrollments, courses, and branches — the probe quickstart scenario 5.4 uses
- [X] T207 [P] [US5] Write `tests/Feature/Imports/NoWritesBeforeConfirmTest.php` snapshotting core table counts across upload, mapping, validation, and preview and asserting they are **unchanged** — the contract's central guarantee (FR-060)
- [X] T208 [P] [US5] Write `tests/Feature/Imports/UploadValidationTest.php` asserting refusal over 20 MB, over 50,000 rows, on an unsupported type, and on a `.xlsx` extension whose magic bytes disagree — all before storage (FR-055)
- [X] T209 [P] [US5] Write `tests/Feature/Imports/MappingTest.php` asserting the suggested mapping is returned, an unmapped required target is refused, and a mapped column absent from the header row is refused (FR-056)
- [X] T210 [P] [US5] Write `tests/Feature/Imports/ValidationCategoriesTest.php` asserting each of the eight categories in [contracts/imports.md](./contracts/imports.md) produces the right row status and localized reason key (FR-057, FR-062, FR-068)
- [X] T211 [P] [US5] Write `tests/Feature/Imports/PreviewTest.php` asserting the create-versus-match counts, the warning and error counts, the first 100 rows each with a status, and 409 while validation is unfinished (FR-061)
- [X] T212 [P] [US5] Write `tests/Feature/Imports/ChunkedCommitTest.php` asserting commit runs in 500-row transactional chunks and that existing records are linked rather than duplicated (FR-064)
- [X] T213 [P] [US5] Write `tests/Feature/Imports/ResumeAfterChunkFailureTest.php` failing a chunk mid-commit, asserting `last_committed_row_id` sits at the last **successful** chunk, committed rows survive, and a resume completes the remainder (FR-065)
- [X] T214 [P] [US5] Write `tests/Feature/Imports/DuplicateFileHashTest.php` asserting a matching `file_hash` for the same term warns via `duplicate_of`, and that continuing creates **zero** new records (FR-067, SC-008)
- [X] T215 [P] [US5] Write `tests/Unit/DuplicateDetectionTest.php` asserting all four levels — University ID, section key, student + section, student + assignment — are each enforced by a unique index, and that a per-row violation becomes an error reason without failing its chunk (FR-066)
- [X] T216 [P] [US5] Write `tests/Feature/Imports/ErrorReportTest.php` asserting the `.xlsx` contains row number, column, submitted value, and reason for **every** rejected row with none missing, Arabic-safe (FR-071, SC-009)
- [X] T217 [P] [US5] Write `tests/Feature/Imports/QueueUnavailableTest.php` asserting that with no worker the job stays `validating` or `committing` and the response says the import is waiting rather than reporting a failure — the state the reference kit does not cover ([ui-conflicts.md](./ui-conflicts.md) C-13)
- [X] T218 [P] [US5] Write `tests/Authorization/ImportEndpointsTest.php` with allow and deny cases for all 9 endpoints in contracts/imports.md
- [X] T219 [P] [US5] Write `tests/Performance/ImportThroughputTest.php` asserting 10,000 rows validate and commit within 5 minutes (SC-007), in the `performance` group, excluded from the default run
- [X] T220 [P] [US5] Write `tests/Visual/us5.spec.ts` screenshotting all six wizard steps plus the imports list, the re-upload warning, the row-reason table, the report, and the **queue-waiting** state against the `ImportScreens` reference, both locales; the queue-waiting baseline has no React counterpart and is baselined from the Blade implementation for design review (C-13)
- [X] T221 [US5] Write `tests/Feature/Imports/CentralModeRejectsRegistrarFileTest.php` asserting that the actual registrar export at `design-system/uploads/filedownloadservlet(21).xlsx` fed to the **central** mode fails with clear per-target reasons naming each of FR-052's ten required targets it cannot supply, and points the operator at the registrar mode — rather than crashing or importing partial nonsense. *(C-09 was decided on 2026-09-27: the registrar mode now handles this file properly, so this task's original purpose — gathering evidence for that decision — is served. It remains as a regression guard on the central mode's failure behaviour. The successful end-to-end import of the same file is T325.)* *(Its end-to-end half is **superseded by T325**, which imports the same file through the registrar mode; this test stays as the guard on the central mode refusing it.)*

### Backend implementation for User Story 5

- [X] T222 [P] [US5] Implement `app/Models/ImportJob.php`, `ImportRow.php`, and `ImportError.php` with their relations and status enums
- [X] T223 [US5] Implement `app/Modules/Imports/Pipeline/FileValidator.php` checking extension, MIME, magic bytes, size against `import_max_file_mb`, and row count against `import_max_rows`, all read from settings — rejecting before storage
- [X] T224 [US5] Implement `app/Modules/Imports/Actions/UploadImport.php` storing the file on the private disk, computing the SHA-256 `file_hash`, detecting a prior matching import for the same term as `duplicate_of`, creating the job in `uploaded`, and auditing `import.uploaded`
- [X] T225 [US5] Implement `app/Modules/Imports/Pipeline/HeaderReader.php` and `ColumnMapper.php` streaming only the header row and proposing a mapping by normalized name match
- [X] T226 [US5] Implement `PUT /imports/{id}/mapping` in `ImportMappingController.php`, persisting the confirmed mapping and the `create_missing_courses` and `create_missing_instructors` options, refusing an unmapped required target
- [X] T227 [US5] Implement `app/Modules/Imports/Pipeline/RowValidator.php` detecting all eight categories from contracts/imports.md, using `UniversityId` from T183 and a running hash set for in-file duplicates
- [X] T228 [US5] Implement `app/Modules/Imports/Jobs/ValidateImportJob.php` streaming the file with OpenSpout and writing one `import_rows` row per source row with `raw`, `resolved`, `status`, and localized `reasons` — **writing to no core table**
- [X] T229 [US5] Implement `POST /imports/{id}/validate` returning 202 and dispatching the job, with 409 when the job is not in `mapped` state
- [X] T230 [US5] Implement `GET /imports/{id}/preview` reading counts and the first 100 rows from `import_rows`, with 409 while validation is unfinished
- [X] T231 [US5] Implement `app/Modules/Imports/Pipeline/Resolver.php` resolving Branch → Course → Section → Instructor → Student → Enrollment, honouring the create-or-skip options and keeping the stored student name on a mismatch
- [X] T232 [US5] Implement `app/Modules/Imports/Jobs/CommitImportChunkJob.php` as a self-chaining job walking `import_rows` by id in 500-row chunks, each chunk one `DB::transaction()` that advances `last_committed_row_id` **as its final statement**, catching per-row unique violations as error reasons without failing the chunk
- [X] T233 [US5] Implement `POST /imports/{id}/commit` requiring `confirm: true`, returning 202, refusing with 409 unless the job is `validated` and the preview was fetched, and auditing `import.committed` with filename, counts, and actor
- [X] T234 [US5] Implement `POST /imports/{id}/resume` restarting from `id > last_committed_row_id`, with 409 unless the job is `failed`, auditing `import.resumed`
- [X] T235 [US5] Implement `GET /imports/{id}` returning status, the five counters, `last_committed_row_id`, `failure_reason`, timings, and `progress_percent` for the 5-second poller
- [X] T236 [US5] Implement `app/Modules/Imports/Exports/ImportErrorReportExport.php` materializing `import_errors` from `import_rows` and streaming an Arabic-safe `.xlsx`, exposed as `GET /imports/{id}/errors/download` and auditing `import.error_report_downloaded`
- [X] T237 [P] [US5] Implement `app/Modules/Imports/Policies/ImportJobPolicy.php` mapping each endpoint to `import.central.run`, `import.section.run`, or `import.view`, and requiring job ownership
- [X] T238 [P] [US5] Store a notification row on queued-job completion in `app/Modules/Imports/Notifications/ImportFinished.php` so the later notification-centre feature surfaces it without rework — **no email is sent in this feature**

### Screens for User Story 5 — the six-step wizard

- [X] T239 [US5] Build `resources/views/imports/index.blade.php` to the `ImportsScreen` reference — `x-track-data-table` of past jobs with mode, file, actor, timestamp, `x-track-status-badge` for the seven job states, the five counters as numerics, a **Resume** ghost button on `failed` rows, and an error-report download button where `errors > 0`
- [X] T240 [US5] Build `resources/views/imports/_wizard.blade.php` as the shared wizard frame — `x-track-stepper` with the six approved Arabic step labels (رفع الملف · مطابقة الأعمدة · الفحص · المعاينة · الاستيراد · التقرير) and the `x-track-card` body, driven by `resources/js/components/import-wizard.js`
- [X] T241 [US5] Build step 1 `resources/views/imports/step-upload.blade.php` — drag-and-drop zone with the accepted types and limits stated up front, `FileMeta` panel showing name and size after selection, term `x-track-select` for central mode, and the `duplicate_of` re-upload `x-track-alert` tone warning
- [X] T242 [US5] Build step 2 `resources/views/imports/step-mapping.blade.php` — a two-column mapping table pairing each required target with a source-column `x-track-select` pre-filled from the suggestion, required targets marked, the create-or-skip `x-track-switch` pair for unknown courses and unregistered instructors, and a disabled Continue until every required target is mapped
- [X] T243 [US5] Build step 3 `resources/views/imports/step-validating.blade.php` — `x-track-progress` with the 5-second poller, a row counter, and the **queue-waiting** `x-track-alert` tone info shown when the job has not been picked up, saying the import is waiting and will be reported when it finishes (C-13)
- [X] T244 [US5] Build step 4 `resources/views/imports/step-preview.blade.php` — a summary grid of `x-track-stat-card` tiles for will-create versus will-match versus warnings versus errors, then `x-track-data-table` of the first 100 rows with a per-row `x-track-status-badge` (valid / warning / error) and its localized reasons, and a confirmation `x-track-dialog` restating the counts before commit (FR-082)
- [X] T245 [US5] Build step 5 `resources/views/imports/step-committing.blade.php` — `x-track-progress` with the chunk counter ("الدفعة 2 من 4") and the same poller
- [X] T246 [US5] Build step 6 `resources/views/imports/step-report.blade.php` — the four approved counters (processed · imported · duplicates · errors), the error-report download button, and a success `x-track-toast`
- [X] T247 [P] [US5] Add the `imports` translation keys — including every `import.*` row-reason key from contracts/imports.md and the queue-waiting copy — to `lang/ar/imports.php` and `lang/en/imports.php`

**Checkpoint**: A real 5,000-row roster loads end to end with an error report — the PRD Phase 1 gate's second check.

**Implementation notes (2026-10-08).** Recorded because each one is a decision the task text did not
make, or a place where the task text and the code differ on purpose.

- **A programme for new sections.** FR-052's ten columns predate FR-090, so a section created by an
  import had no programme to belong to, and "create unknown courses" could never succeed. Two
  additions, both written into contracts/imports.md: an optional eleventh target `program_code`, and a
  mapping-step choice `new_course_program_id` for courses the import creates. A new section otherwise
  takes its course's programme when the course has exactly one, and is reported as
  `import.unresolvable_section` when it cannot be known — never attached by guess.
- **What an import never does.** It never changes an existing section's instructor (that is
  ChangeSectionInstructor's history row and audit entry), never renames a student (FR-069), and never
  reopens a TRACK withdrawal — a re-sent roster counts the row as matched and the withdrawal stands.
- **FR-067 is strict.** A file already imported for the term commits nothing at all, even rows the first
  run skipped and the second run would now create; every row counts as a duplicate, as the reference
  does. A job abandoned before commit is not "previously imported".
- **`import.committed` is written at completion**, attributed to the operator, carrying the final
  processed / imported / duplicate / error counts (FR-073). The queued chunks adopt the job owner as the
  authenticated user, so every student, section and enrollment they create is audited to that person.
- **Six job columns arrive in their own migration**, `2026_10_08_000001_add_wizard_state_to_import_jobs_table`
  — `summary`, `link_only`, `preview_fetched_at`, `commit_requested_at`, `queued_at`, `heartbeat_at` —
  plus Laravel's standard `notifications` table for T238. (They were first added by editing the
  already-run 000018 migration; the review caught it, see below.) `php artisan migrate` is enough. The
  queue-waiting state (C-13) is a heartbeat older than the queueing, not elapsed time.
- **Ownership versus visibility.** Reading a job is `import.view` plus the global scope; every step that
  moves a job forward is the mode's run permission **and** ownership, so a second Super Admin can follow
  a colleague's import but not confirm it.
- **T215** is split by what each half needs: the four index definitions are read from the migrations in
  `tests/Unit/DuplicateDetectionTest.php` (no framework, as that suite requires), and the row-level
  violation runs against a database in `tests/Feature/Imports/RowViolationDoesNotFailChunkTest.php`.
  Level 4 (`initiative_targets`) is skipped by name until Phase 12's T287 creates the table.
- **T218** covers eight of the contract's nine endpoints. The ninth, `POST /sections/{id}/imports`, is
  US6's (T254) and its allow/deny pair arrives with it.
- **T219** measured 49.8 s for 10,000 rows end to end against the 300 s budget (SQLite, sync queue).
- **Two defects outside this phase's files were found and fixed on the way:** the stepper's connecting
  lines carried the browser's list numbering (first visible now, its first real screen), and OpenSpout's
  XLSX reader keeps its file handle after an early stop until the cycle collector runs — a lock on
  Windows (`SpreadsheetReader::release()`).
- **Not built here, deliberately:** Phase 15 (registrar mode) interleaves after T233 and was not part of
  this run; T221 guards the central mode's refusal of that file in the meantime.

**Post-review corrections (2026-10-09).** An engineering review of this phase found ten issues over a
green suite, all fixed. As with Phase 7, they are recorded because each marks a place where a test
passed while the behaviour it named was absent — here, because every test ran one request at a time,
on one worker, on the choices validation had seen. Regression cover lives in
`tests/Feature/Imports/ReviewRegressionsTest.php`; each test there was confirmed to fail with its fix
undone, except the row-lock one noted below.

- **The commit could act on choices nobody validated.** `POST /commit` accepted new create-or-skip
  options, so it wrote courses and accounts the validator never checked and the preview never counted —
  an orphan course with no programme, an account for an empty email. The commit now refuses `options`
  (422); a choice changes only by mapping again.
- **A skipped row could leave a course behind.** The resolver created the missing course before
  deciding the row's instructor; a skip does not throw, so the savepoint committed the course. Every
  decision is now made before the first write.
- **Two commits, or two resumes, could start two chains.** Check-then-save let a double click pass both
  checks. Commit, resume, validate and mapping now claim the job with a compare-and-set UPDATE, and each
  chunk reads the checkpoint under a row lock on the job. The lock cannot be exercised on SQLite (it
  ignores FOR UPDATE, and the suite uses one connection); it holds on MySQL and PostgreSQL.
- **A dead worker left an import stuck forever.** No `failed()` handler, `tries = 1`, and timeouts
  longer than the queue's `retry_after` of 90. Both jobs now have `failed()` and `failOnTimeout`; the
  chunk job's timeout is 80 s, validation's 840 s, and `retry_after` defaults to 900 for the database and
  redis queues. A worker that dies outright runs no handler, so a `committing` job whose heartbeat is
  over 15 minutes old can be resumed, and a stalled validation can be re-mapped; the screens offer both.
- **Link-only could switch on halfway through a commit.** `duplicateOf()` was asked again in every
  chunk, and required the other job to have been uploaded earlier. It is now decided once at commit and
  stored as `link_only`, and the warning counts any other completed import of the file.
- **Downloading the error report rewrote it.** Each GET deleted and rebuilt `import_errors`, then paged
  it by offset, so concurrent downloads could get mixed languages or missing lines. The download now
  streams `import_rows` by id; `import_errors` is written once, at completion.
- **A revoked or deactivated owner kept committing.** Each chunk now re-checks the owner, and stops the
  import as failed with `imports.failure.owner_revoked`.
- **"١", "1" and "01" were three sections.** Section numbers are now normalised in the in-file check,
  the lookup and the stored value (`RowValidator::sectionNumber`).
- **Per-row queries inside each chunk.** The resolver now preloads a chunk's students and existing
  enrollments in two queries, and caches section groups and course programmes.
- **The migration was edited in place.** Moved to a new migration, as above.

---

## Phase 9: User Story 6 - Instructors Load Their Own Section Lists (Priority: P6)

**Goal**: An instructor imports a two-column student list into a section they teach, through the same validated six steps, and cannot touch anyone else's section.

**Independent Test**: As an instructor, upload a file with one new student, one existing student whose name differs, and one duplicate row; then attempt the same against another instructor's section.

### Tests for User Story 6

- [X] T248 [P] [US6] Write `tests/Feature/Imports/SectionModeImportTest.php` asserting the same six steps apply, nothing is saved before confirmation, and only `university_id` and `student_name` are required
- [X] T249 [P] [US6] Write `tests/Feature/Imports/LinkExistingStudentTest.php` asserting an existing student is linked rather than duplicated, and that a differing name keeps the stored value and raises a warning (FR-069)
- [X] T250 [P] [US6] Write `tests/Feature/Imports/InFileDuplicateRejectedTest.php` asserting a repeated student in one file has its second occurrence rejected naming its row number
- [X] T251 [P] [US6] Write `tests/Scope/InstructorCannotImportForeignSectionTest.php` asserting **404** plus an `access.denied` audit entry when an instructor targets a section they neither teach nor hold a grant for (FR-054)
- [X] T252 [P] [US6] Write `tests/Feature/Sections/InstructorWithNoSectionsTest.php` asserting the empty state naming whom to contact, not a blank page
- [X] T253 [P] [US6] Write `tests/Visual/us6.spec.ts` screenshotting the section-page import entry, the two-column template download, and the instructor's empty state against the section page's «استيراد كشف» action and the ImportWizard section mode (SectionsScreens.jsx, ImportScreens.jsx), both locales. *(Corrected 2026-10-09: the `SectionFilePicker` this task first named is the initiatives screen's section picker, not a roster import.)*

### Implementation for User Story 6

- [X] T254 [US6] Implement `POST /sections/{id}/imports` in `app/Modules/Imports/Http/Controllers/SectionImportController.php`, resolving the section through the scoped query so a foreign section yields 404, and creating the job with `mode: section`
- [X] T255 [US6] Extend `app/Modules/Imports/Pipeline/RowValidator.php` with the section-mode rule set — `university_id` and `student_name` only, with the section fixed by the job rather than read from the file
- [X] T256 [US6] Extend `app/Modules/Imports/Pipeline/Resolver.php` for section mode: attach students to the one job section only, never creating or resolving a section from file values
- [X] T257 [P] [US6] Build `resources/views/sections/import.blade.php` to the section page's «استيراد كشف» action and the ImportWizard section mode *(corrected 2026-10-09 — not `SectionFilePicker`, which is the initiatives screen's picker)*, reusing the wizard frame with a two-column template download button and the section fixed in the header
- [X] T258 [P] [US6] Add the instructor-facing `imports` and `sections` empty-state translation keys to `lang/ar/` and `lang/en/`

**Checkpoint**: Instructors are unblocked from the central office for routine roster corrections.

**Implementation notes (2026-10-09).**

- **The reference T253 and T257 name is the wrong screen.** In the design kit `SectionFilePicker` is
  the initiatives screen's "pick sections from a file" control (InitiativesScreen.jsx), not a roster
  import. The approved entry for this story is the section page's «استيراد كشف» action opening
  ImportWizard in section mode (SectionsScreens.jsx line 91, ImportScreens.jsx), and the page and its
  baselines are built to that.
- **Two columns, not the reference's four.** The kit's section template carries sequence and registrar
  status too; FR-053 and T255 say university id and student name only, and the registrar's columns
  belong to Phase 15's registrar mode. The template is xlsx with headers in the reader's language, so
  the mapping step suggests both columns with no correction.
- **Who may import into a section** is `SectionPolicy::importRoster`: `import.section.run` plus teaching
  the section, holding a grant for it, or `scope.bypass_all` (the reference lets a Super Admin act on
  any section). A foreign section is a 404 from the scoped binding before the policy runs, audited as
  `access.denied` by AuditDeniedAccess; a coordinator who can see the section but teaches none gets
  403. Every commit chunk asks `importRoster` again, so an instructor whose section is reassigned
  mid-import stops writing to it.
- **The section is the job's.** A section job's term is its section's, the file cannot name another
  section (extra columns are ignored), and the resolver never resolves or creates a section, course or
  account in this mode. "Previously imported" (FR-067) compares within the same section only.
- **The ninth endpoint** is now covered in `ImportEndpointsTest` and the route matrix; T218's deferral
  is closed.
- **Fixture change.** `UiFixtureSeeder` gains user 11, an Arabic-preference instructor with no section,
  so the empty state has an Arabic baseline (user 10 is the English one). The instructor empty state now
  names whom to contact, which changes the existing `*-sections-instructor` baselines' wording.

**Post-review corrections (2026-10-09).** A review of this phase found ten issues, all fixed, with
regression cover in `tests/Feature/Imports/SectionReviewRegressionsTest.php` (each test confirmed to fail
with its fix undone).

- **The preview disclosed any student's name.** The section mode's student lookup is unscoped (it must
  be, to link rather than duplicate), and the name-mismatch reason quoted the stored name — so made-up
  ids with a placeholder name read real names off the preview without ever committing. In this mode the
  stored name is now quoted only for a student already in the job's section.
- **Later steps did not re-check the section.** `ImportJobPolicy::run` now applies
  `SectionPolicy::importRoster` to a section job's section at the HTTP edge too, so mapping, validate,
  preview, commit and resume refuse once the owner loses the section, rather than accepting with 202 and
  failing in the worker.
- **Names were compared raw.** FR-069's check now folds hamza and alef forms, tatweel, diacritics,
  spacing and Latin case (`RowValidator::sameName`) and compares against both stored names.
- **An instructor's list restored deleted students.** In the section mode a soft-deleted student is
  refused with `import.student_unavailable`, at validation and again at commit; the central mode, run
  from the registration unit's roster, still restores.
- **An archived term took a section list.** The section upload now refuses one (409), as the central
  upload's term validation does.
- **An authority-lost stop left no audit entry.** It now writes `access.denied` (reason
  `import_authority_lost`) in the same transaction as the status change.
- **Section jobs stored central-only options.** The mapping endpoint refuses `options` on a section job.
- **Section chunks loaded every course.** The resolver skips the catalogue in the section mode.
- **A section number with a slash broke the template download** (500 from the Content-Disposition
  header); the filename is now sanitised.
- **Documentation** — the T253/T257 lines above, and contracts/imports.md, now carry the corrections.

---

## Phase 10: User Story 7 - Everyone Works In Their Own Language (Priority: P7)

**Goal**: Every screen is complete, correctly mirrored, accessible, and behaviourally identical in Arabic and English, with the design system's states applied throughout.

**Independent Test**: Switch language on each delivered screen and confirm full translation, correct mirroring, and identical behaviour; then confirm every status carries a label and every form validates, loads, errors, and confirms as specified.

**Note**: This story is cross-cutting — its components ship in Phase 3 and its strings ship with each screen. This phase is the sweep that verifies and closes the gaps.

### Tests for User Story 7

- [X] T259 [P] [US7] Write `tests/Feature/Localization/EveryScreenRendersBothDirectionsTest.php` asserting every registered `web` route renders in both `ar` and `en` with the correct `lang` and `dir` attributes and the matching stylesheet
- [X] T260 [P] [US7] Write `tests/Feature/Localization/NoUntranslatedOutputTest.php` asserting no rendered screen contains a raw translation key or a fallback-language string while the other locale is active
- [X] T261 [P] [US7] Write `tests/Feature/Ux/StatusBadgeRequiresLabelTest.php` asserting `x-track-status-badge` cannot render colour without a text label (FR-077)
- [X] T262 [P] [US7] Write `tests/Feature/Ux/ConfirmationRequiredTest.php` asserting student withdrawal, account deactivation, instructor change, and term status change each require a confirmation restating the impact (FR-082)
- [X] T263 [P] [US7] Write `tests/Feature/Ux/ValidationStatesTest.php` asserting server-side rules repeat every client-side rule and that errors render beside their field in the active language (FR-078)
- [X] T264 [P] [US7] Write `tests/Visual/screens-all.spec.ts` running the full screen regression across all 20 in-scope screens × both locales × 1280 / 1440 / 1920 / 390, at ≤ 1% pixel difference against the reference ([design-system.md](./design-system.md) §13)
- [X] T265 [P] [US7] Write `tests/Visual/axe-all.spec.ts` running axe-core over all 20 screens in both locales, requiring **zero** WCAG 2.1 AA violations (SC-014)
- [X] T266 [P] [US7] Write `tests/Visual/rtl-mirroring.spec.ts` asserting per screen that the sidebar sits right in RTL and left in LTR, breadcrumb chevrons point correctly, button icons sit on the correct side, and the toast anchors to the correct corner
- [X] T267 [P] [US7] Write `tests/Performance/PageLoadTest.php` asserting first contentful render under 2 seconds at p95 (SC-015), in the `performance` group, excluded from the default run

### Implementation for User Story 7

- [X] T268 [US7] Sweep every Blade view for hard-coded user-facing text, moving each string into `lang/ar/` and `lang/en/` until `NoHardcodedStringsTest` and `LocalizationParityTest` both pass
- [X] T269 [US7] Fix every RTL mirroring defect `rtl-mirroring.spec.ts` reports, in `resources/css/app-rtl.scss` and the affected views, using CSS logical properties rather than direction-specific overrides wherever Bootstrap allows
- [X] T270 [P] [US7] Implement `app/Modules/Shared/Support/Format.php` emitting Western Arabic digits 0-9 and `DD/MM/YYYY` in both locales, plus the "2026/2027 - الأول" term format from the design system's content rules, and apply it to every rendered number and date (FR-084)
- [X] T271 [P] [US7] Verify and correct the responsive behaviour at 1280 and 1920, and the <768px stacked-card and drawer behaviour, against the design system's layout rules (FR-085)
- [X] T272 [US7] Complete WCAG 2.1 AA and full keyboard operation across every screen — focus order, the `--green-600` visible focus ring, ARIA labels on every icon-only control, dialog focus trapping and restoration, and the shell skip link — until `axe-all.spec.ts` is clean (SC-014)
- [X] T273 [P] [US7] Verify the loading, error, success, empty, and confirmation states are applied uniformly across all 20 screens against the §10 state matrix, and fix every gap
- [X] T274 [P] [US7] Build `resources/views/profile/preferences.blade.php` with the locale control persisting `users.locale`, matching the topbar switch's approved segmented-control styling

**Checkpoint**: All seven stories are independently functional, visually faithful, and accessible in both languages.

**Implementation notes (2026-10-09).**

- **One screen registry, two suites.** `tests/Support/Screens.php` lists the in-scope screens (23 keys:
  §9 rows 1–20 plus the wizard's steps and the instructor's own section list) for the PHP sweeps, and
  `tests/Visual/support/screens.ts` mirrors it for the browser sweeps. A screen added to one and not the
  other is a review finding; the PHP side also asserts every named `web` GET route is either listed or
  excluded with a reason.
- **T264 compares against the Blade baselines, not the React kit.** The kit renders in its own harness
  with its own reset and CDN fonts, so a pixel diff between the two measures the harness
  (components.spec.ts records the same decision). Each screen was reviewed against the kit when it was
  built; the baselines hold that result. `screens-all.spec.ts` photographs only what no story spec covers
  in both locales at every width — section detail, student detail, preferences — and asserts per project
  that every §9 row has a baseline, so nothing is photographed twice.
- **T265 scans at 1440 and 390 only.** Those two render every distinct markup (tables vs stacked cards,
  docked sidebar vs drawer); the width-dependent markup is CSS only — no width-conditional Blade, no
  `d-*-*` classes, no JS breakpoint — so 1280 and 1920 serve 1440's DOM. Per screen it scans the page,
  each of its dialogs opened, and one dialog form sent back refused; per locale and width, a toast of
  each tone and, at 390, the open drawer. No rule is disabled.
- **T267 is measured in two halves.** `tests/Performance/PageLoadTest.php` takes the server's render
  time, p95 per screen and locale over twenty warm renders, on the fixtures plus a 400-student roster
  and 5,000 audit entries (slowest p95 ≈ 120 ms on SQLite — a floor, not a production forecast).
  `tests/Visual/fcp.spec.ts` measures what SC-015 names, first contentful paint, in Chromium on one
  fixed profile — cold cache, 4× CPU slowdown, 40 ms / 9 Mbps — per screen and locale (slowest ≈ 1.8 s,
  the login page). Neither alone proves SC-015; together they bound it.
- **T270** — `Format::date`/`dateTime` (`d/m/Y`, `d/m/Y H:i`), `Format::number` and `Format::term`
  ("2026/2027 - الأول") replace `isoFormat('LL')`, which wrote Arabic month names, on every screen and in
  the term selects. Timestamps are stored in UTC and shown in `app.display_timezone` (Asia/Riyadh);
  calendar dates are not shifted. Date inputs keep `Y-m-d`.
- **T274** — the topbar, login and profile share one `x-track-locale-switch` segmented control; the
  preferences page (`/profile/preferences`) is the settings home for it.

**What the sweeps found, and the fixes (2026-10-09).** Each is covered by the test that found it.

- **Nine dialogs submitted nothing.** Their footer submit buttons sat outside the `<form>` (the dialog
  component renders the footer slot after the body). Every dialog form now has an id and its footer
  button names it with `form=""`; `ConfirmationRequiredTest` asserts every dialog submit button belongs
  to a form.
- **Arabic pages showed English validation messages** — there was no `lang/*/validation.php`. Both
  locales now carry the full rule set and an attribute map, with parity enforced.
- **The term status-change reason** accepted two characters and answered a missing reason with 409. It
  is now a field rule (min 3, max 500, required when the transition needs one) answering 422 beside the
  field; `TermLifecycleTest` was updated to match.
- **Arabic lists paginated in English** ("« Previous"), through Laravel's stock Tailwind view, whose
  disabled step was also a labelled span axe rejects. Lists now use `resources/views/pagination/track.blade.php`,
  translated, with direction-aware chevrons (`PaginationTest`).
- **Contrast below AA** on the language switch and the dropzone hint: `--text-secondary` measures 4.3:1
  on the `--gray-100` track. Both now use `--gray-600`.
- **The toast corner contradicted itself.** design-system.md §5 row 11 said "bottom-start (bottom-left in
  RTL)", which is two different corners; the CSS took "start" and put toasts over the sidebar. The
  concrete corner wins — bottom-end, bottom-left in Arabic — and §5 is corrected.
- **Page-header actions overflowed at 390** (activity panel); the actions row now wraps.
- **`x-track-status-badge` never shows a bare colour** (FR-077): an unknown status without a label reads
  «حالة غير معروفة» / "Unknown status" in the neutral tone and is logged *(corrected after review — it
  first threw, which made one unlabelled row a 500 for the whole page)*.
- **T272 keyboard operation** — `tests/Visual/keyboard.spec.ts` checks the skip link, the dialog focus
  trap and restoration (including a dialog opened from a table row), and the focus ring in both
  locales; axe covers names and contrast.

**Post-review corrections (2026-10-09).** A review of this phase found ten issues, all fixed, each with a
regression test confirmed to fail with its fix undone.

- **A refused dialog came back shut.** Every dialog form posts and redirects back, so a 422 beside a
  field — or a 409 flashed as `conflict` — reloaded the page with the dialog closed, and the term status
  dialog's reason had no error binding at all. Dialog forms now post `_dialog` (`x-track-dialog-marker`);
  the dialog it names reopens with the refusal in it and focus on the first invalid field, and a refusal
  whose dialog is no longer on the page is shown at the top of the page. The withdraw and account dialogs
  also come back on the same record — rebuilt from its id on the server, never from a posted URL — with
  what was typed (`DialogReturnsWithErrorsTest`, and the refused state in `axe-all.spec.ts`).
- **A refused term transition was answered as a missing reason.** `ChangeTermStatus::needsReason` said yes
  to transitions that are not permitted, so closed → draft got 422 "reason required"; it now asks
  `permits()` first and the 409 stands. The 409 names the states by their labels, not their keys, and the
  reason message no longer says "reopening" for an archive.
- **Date columns sorted by day of month.** `data-sort-value` had become the DD/MM/YYYY text; rows now pass
  `_sort` timestamps and the table prefers them (`DateColumnsSortTest`).
- **Times read three hours early.** See T270 above; the audit date filters and the audit export use the
  same zone, so a filtered day matches the dates the list shows.
- **The badge threw on an unknown status.** See the corrected bullet above. `returned`, `rejected`,
  `committed` and `skipped` — allowed by the schema, absent from the vocabulary — now have labels, and a
  test reads every status enum in the migrations.
- **"Term name" was hard-coded** on the create-term dialog, and both sweeps were blind to it.
  `NoHardcodedStringsTest` now also reads spoken attributes (`label`, `placeholder`, `title`,
  `aria-label`, `hint`, …); `NoUntranslatedOutputTest` matches any string with a real word (phrases
  anywhere, with placeholders as wildcards; single words only as a whole label, so a student called
  صالح is not a leak) and any `group.key`, written or not. The wider net found the audit disclosure
  printing `audit.<field>` as each side's heading — a variable reused inside its own loop — and
  English column names in the Arabic diff; both fixed, with labels for every audited field.
- **PageLoadTest pooled every screen into one p95** and measured only the server. See T267 above.
- **axe-all scanned only the page as it loads.** See T265 above.
- **The withdraw dialog lost the row's focus** — it recorded its wrapper `<div>` as the trigger. The open
  is now relayed from the row's button.
- **A dead locale-switcher script** ran on every click on every page; removed.

Found while reviewing the visual diffs afterwards, also fixed:

- **Date-times read time-first in Arabic.** "09/10/2026 05:25" painted as "05:25 09/10/2026" in every
  Arabic table: the space between two digit runs took the paragraph's direction. `Format::dateTime`
  now wraps the value in LRI … PDI (`FormatTest`).
- **"Name in Arabic" above English names.** The roster and the accounts list show the reader's-language
  name under the Arabic-name label; both now say «الاسم» / "Name" (`ColumnLabelsTest` reads every
  language-named column and checks its script).
- **Two US1 baselines photographed no failure.** The login-failure and deactivated-refusal shots clicked
  the page's first submit button — the language switch, since T274 — so they captured a language
  change, not a refusal (the old Arabic baseline was in English). They now click the login form's own
  button and assert the alert before the screenshot.
- **Stale baselines passed under the 1% threshold.** Several pre-US7 baselines still showed the old
  topbar and navigation and passed because the change was under 1%. Every baseline was regenerated
  from the current render after the diffs above were reviewed, and a separate run confirmed them.
- **The first-sign-in banner has one baseline** (ar, 1440): photographing it consumes the fixture's one
  never-signed-in account. `screens-all.spec.ts` records the exception and its reason; the English
  banner has no baseline.

---

## Phase 11: Polish & Cross-Cutting Concerns

- [X] T275 [P] Implement structured JSON application logging with request id, user id, and route in `app/Modules/Shared/Logging/` and `config/logging.php` (PRD 8.8)
- [X] T276 [P] Implement the `/health` endpoint in `app/Modules/Shared/Http/Controllers/HealthController.php` checking database, Redis, queue workers, and storage
- [X] T277 Implement `app/Modules/Shared/Http/Middleware/ContentSecurityPolicy.php` with a strict policy and register it on the `web` group — and confirm it needs **no** exemption for `unpkg.com`, `cdn.jsdelivr.net`, or `fonts.googleapis.com`, since fonts, icons, and scripts are all self-hosted (T058, T059, T066)
- [X] T278 [P] Verify every state-changing route carries CSRF protection, and add `tests/Feature/Security/CsrfCoverageTest.php`
- [X] T279 Run the OWASP Top 10 review against this feature's surface and record the findings in `docs/security-review-001.md`
- [X] T280 [P] Write the deployment runbook in `docs/runbook-deploy.md` covering the Supervisor worker configuration, the audit-table grant verification for staging and production, backup and restore, the 5-year audit retention policy, and the asset build step that copies `design-system/assets/`
- [X] T281 [P] Write `docs/architecture-001.md` recording the decisions from [research.md](./research.md) and the six design-system decisions from plan.md, so they survive the key-person risk in PRD R-05
- [ ] T282 Request vector originals (SVG or AI/EPS) for the logo set from the brand author, per [ui-conflicts.md](./ui-conflicts.md) C-06 — the shipped assets are ~3× raster upscales and Phase 3's PDF export headers will show it
- [ ] T283 Walk the design author through `design-system/ACCESSIBILITY-DELTA.md` and obtain their acknowledgement of the 7 palette corrections (C-17) *(corrected 2026-10-09: the file is `specs/001-foundation-access-roster/ACCESSIBILITY-DELTA.md`, since `design-system/` is vendored read-only, and there are **nine** corrections — A11Y-08 and A11Y-09 were added in Phase 3; the walkthrough sheet is `docs/requests/C-17-accessibility-acknowledgement.md`)*
- [X] T284 Execute the full gate from [quickstart.md](./quickstart.md): `pint --test`, `phpstan analyse`, `php artisan test`, `php artisan test --group=performance`, `--testsuite=Architecture`, and the Playwright visual + axe suites
- [X] T285 Walk quickstart.md validation scenarios **1-10** (Phases 1-17 scope; Scenarios 11-21 are verified by T462) manually and record the outcome against the four PRD Phase 1 gate checks and the seven constitution Definition-of-Done items

**Implementation notes (2026-10-09).**

- **T275** — `AssignRequestId` (global, first) puts the id in Laravel `Context`, so it is on every log
  record and in every job the request queues; an upstream `X-Request-Id` is kept only if it looks like
  an id (log-injection guard). `RequestContextProcessor` adds user id, route, method and path, asking
  each guard whether it *has* a user rather than resolving one. The `json` channel (daily,
  `storage/logs/track.json-*.log`) is the default stack; `.env.example` ships `LOG_STACK=json`.
- **T276** — `/health` sits outside the `web` group (no session row per probe) with `throttle:60,1`;
  each check reports `ok` / `fail` / `skipped` and a timing, never a reason (reasons go to the log).
  Redis is `skipped` where no driver uses it. Queue workers are judged by a heartbeat written from the
  worker loop (`QueueHeartbeat`, every 30 s, stale after 120 s) **and** by the oldest waiting job
  (> 5 minutes fails). `/up` stays as the bare liveness probe.
- **T277** — `ContentSecurityPolicy` is **prepended** to `web`, so refusal pages thrown by a scoped
  binding carry it too. `'self'` only; no third-party host; `'unsafe-eval'` is the one concession
  (Alpine's standard build); style *attributes* allowed via `style-src-attr`, style *elements* not —
  the `[x-cloak]` rule moved into the bundle. The Vite dev origin is admitted only while `npm run dev`
  runs; the local-only `dev.*` routes are exempt. Companion headers: nosniff, Referrer-Policy,
  X-Frame-Options, Permissions-Policy, HSTS on HTTPS. The visual suite sets `bypassCSP` (its harness
  injects a style tag to freeze animations); `tests/Visual/csp.spec.ts` turns it off and finds zero
  violations on every screen in both locales.
- **T278** — Laravel 13's CSRF middleware is `PreventRequestForgery` (token, or a same-origin
  `Sec-Fetch-Site`). The coverage test reads every POST/PUT/PATCH/DELETE route, and found one outside
  it: the framework's `PUT storage/{path}`, registered by the `local` disk's `serve => true` over the
  same directory as the import files. `serve` is now off; a test asserts the private tree has no URL.
- **T279** — `docs/security-review-001.md`: 10 of 10 categories, 3 fixed in the review (the storage
  route; the language switch's Referer-following redirect, now same-host only; the missing CSP),
  5 accepted residual risks with owners, 0 open defects; `composer audit` and `npm audit` clean.
- **T280 / T281** — `docs/runbook-deploy.md` (Supervisor with the coupled timeouts, the audit-grant
  check as the application user, backup and restore with the 4-hour RTO, the 5-year retention rule,
  the asset build) and `docs/architecture-001.md`. Both note that production runs PHP 8.4, not the
  8.3 plan.md and PRD §9.5 still state.
- **T282 / T283 — not done; they need people.** The request to the brand author
  (`docs/requests/C-06-logo-vector-originals.md`) and the acknowledgement sheet for the design author
  (`docs/requests/C-17-accessibility-acknowledgement.md`) are written and ready to send. The sheet lists
  **nine** corrections, not seven: A11Y-08 and A11Y-09 were added in Phase 3.
- **T284** — gate run on 2026-10-09: Pint and PHPStan clean; `php artisan test` 852 passed, 2 skipped;
  `--testsuite=Architecture` 29 passed; `--group=performance` 7 passed (page load, FCP budget's server
  half, import throughput ~20 s for 10k rows, audit filter, the PRD roster gate); `npx playwright test`
  1,360 passed, 768 skipped by design (width- and project-scoped), 0 failed. The quickstart gate's
  Amendment 2 lines (`tests/Feature/Support/*`, `PublicSurfaceBoundedTest`) have no tests yet — they
  arrive with Phases 18–25.
- **T285** — `docs/validation-001.md`. Scenarios 1–8 walked by the test harness and browser
  automation (not a person); every step names its test. The PRD's roster gate is now its own walk,
  `tests/Performance/PrdGateRosterTest.php`: 5,008 rows, 7 rejected, 99.9 % imported, the error report
  matching every rejected row, the re-upload creating nothing. Scenarios 9–10 are N/A until Phases
  12–16 are built. All four PRD gate checks are met; the seven Definition-of-Done items are met for
  US1–US7. A staging click-through by a person is recorded as still worth doing.

**Follow-up fixes (2026-10-09)** — the open items from the Phase 11 report, closed where code or
documents could close them; each with a test confirmed to fail with its fix undone.

- **`'unsafe-eval'` removed from the CSP** (security review R-01, closed). Alpine runs as
  `@alpinejs/csp`, whose parser takes one expression — no statements, arrow functions, template
  literals, `?.`/`??` or browser globals. The inline logic moved into components
  (`resources/js/components/forms.js`: login form, mapping form, account dialogs, withdraw dialog; plus
  methods on the dialog, data-table and wizard components), and object data now reaches components in
  `data-*` attributes — `@js([...])` compiles to `JSON.parse`, out of the parser's reach.
  `csp.spec.ts` fails on any violation or Alpine error, at load and through every rewritten handler.
- **Alerting** (R-02, closed in code). `track:monitor` every five minutes (error rate from per-minute
  request counts, queue backlog, unreachable queue, disk) and a `JobFailed` listener raise PRD 8.8
  alerts to `track.alerts-*.log` and `ALERT_EMAIL`, once per condition per 30 minutes; Sentry installed,
  inert until `SENTRY_LARAVEL_DSN` is set. Needs the scheduler cron on the server.
- **Audit grants** (R-03). `track:audit-grants --strict` probes UPDATE and DELETE as the application
  user with `WHERE 1 = 0` and is a deploy step; the revoke migration logs CRITICAL on staging and
  production when it cannot apply itself.
- **Sign-out button contrast** about 1.3:1 on the sidebar gradient — invisible to axe, which files text on
  a gradient as "needs review". Fixed; `axe-all.spec.ts` now measures sidebar text against both gradient
  stops.
- **The idle timeout is tested by the clock** (59 minutes keeps the session, 61 more ends it), not only
  by reading the configuration.
- **The English first-sign-in banner has a baseline**: fixture user 12, owned by en-1440.
- **The roster's Actions column no longer offers sorting**; a sweep asserts every sortable column has
  values to sort on.
- **Document discrepancies**: PHP 8.4 (not 8.3) in plan.md and PRD.md; `.env.example` sessions on Redis
  as the plan specifies; T283 and C-17 corrected to nine corrections in the spec folder's delta file.

**Still open — outside what code can do:** T282 and T283 (the brand and design authors; requests are
ready in `docs/requests/`), a person's staging click-through, the hosting items (R-04 encryption at
rest and TLS, R-05 private Redis), the server settings (DSN, `ALERT_EMAIL`, cron, the one-time DBA
revoke), and quickstart scenarios 9–10, which need Phases 12–16 built.

---

## Phase 12: Typed EAV Capacity Spike

**Ungated 2026-09-27.** The Product Owner confirmed that the PRD's 15 dynamic field types stand and
that the UI's ordered stages are one preset of Yes/No fields ([ui-conflicts.md](./ui-conflicts.md)
C-08). The spike therefore measures the design that will ship, and runs as originally specified.

Runs in parallel with Phases 4–11; depends only on Phase 2. **Must complete before this feature is
accepted**, because Phase 2 planning depends on the answer.

**⚠️ These tables carry no controller, route, policy, or view in this feature.** Containment is
asserted by T291.

- [X] T286 [P] Create migrations for `initiatives`, `initiative_schema_versions` (**unique `(initiative_id, version_number)`**), `initiative_fields` (**unique `(schema_version_id, field_key)`**), and `initiative_sections` (**unique `(initiative_id, section_id)`**) per [data-model.md](./data-model.md) Group 6
- [X] T287 [P] Create migrations for `initiative_targets` (**unique `(assignment_id, student_id)`**), `student_initiative_records` (**unique `(assignment_id, student_id)`**), and `student_initiative_values` (**unique `(record_id, field_id)`**) with the typed columns `value_text`, `value_number`, `value_date`, `value_bool`, `value_json`, and `attachment_id`
- [X] T288 Add the two indexes under measurement — `student_initiative_values(field_id, value_bool)` and `student_initiative_records(assignment_id, is_complete)` — as a separate toggleable migration, since whether they ship is decided by the spike result
- [X] T289 Implement `database/seeders/EavSpikeSeeder.php` generating, with chunked raw multi-row inserts rather than Eloquent, 1 term, 6 branches, 3,000 sections, 30,000 students, 30,000 enrollments, 10 initiatives × 5 schema versions, 50 field definitions, 30,000 records, and **1,500,000** values distributed ≈45% `value_text`, ≈25% `value_number`, ≈15% `value_bool`, ≈10% `value_date`, ≈5% `value_json`
- [X] T290 Implement `tests/Performance/EavSpikeTest.php` measuring each of the five queries in [research.md](./research.md) R-06 as p95 over 10 cold runs against its threshold: assignment grid of 200 records × 15 fields pivoted **< 2 s**; per-section completion counts across ≈3,000 sections **< 3 s**; initiative-wide student-weighted completion at 30,000 targets **< 3 s**; full recalculation sweep over 30,000 records **< 2 min**; single value upsert **< 500 ms**
- [X] T291 [P] Write `tests/Architecture/EavSpikeContainmentTest.php` asserting no route, controller, policy, or Blade view in this feature references the Group 6 tables
- [X] T292 Write `specs/001-foundation-access-roster/spike-eav-results.md` recording the schema measured, the index set, row counts, `EXPLAIN` output per query, measured p95s, and a PASS/FAIL per threshold
- [X] T293 On any FAIL, record the finding and the proposed remedy in `spike-eav-results.md` before Phase 2 planning begins, selecting from the ranked candidates in research.md R-06 — and state the reason if a new remedy is proposed instead (FR-088)

> **Status: PROVISIONAL.** T286–T293 are done on SQLite, but FR-087 is met only when the design is
> demonstrated on the engine it ships on. **Open acceptance item:** run `EavSpikeTest` on MySQL /
> MariaDB (`EAV_SPIKE_MYSQL_DATABASE=<own schema>`, with and without the indexes), record it in
> [spike-eav-results.md](./spike-eav-results.md), decide the two indexes, and change its status. The
> feature is not accepted until then.

**Implementation notes (2026-10-09, corrected after the Phase 12 review).**

- **SQLite result: PASS on all five thresholds**, with and without the measurement indexes — grid
  0.011 s, per-section 0.051 s, weighted 0.026 s, sweep 0.55 s, upsert 0.005 s (with); tightest margin
  59×. Warm-cache numbers: each run opens a fresh connection, but nothing empties the OS cache unless
  `EAV_SPIKE_COLD_COMMAND` is set (a MySQL restart, for the deciding run). Ten samples, so p95 is the
  slowest run. Full table, plans, machine and findings: [spike-eav-results.md](./spike-eav-results.md).
- **F-1 — a real FAIL, then its cause.** Measured with no planner statistics, SQLite chose the
  `(field_id, value_bool)` index for a one-record completion check (~150,000 rows walked): upsert p95
  0.737 s (FAIL), sweep 35 s. After `ANALYZE`, 2 ms. InnoDB recalculates statistics in the background
  after ~10 % of a table changes, so the window exists in production after a bulk import; the results
  assign `ANALYZE TABLE` to Phase 2's bulk value writer.
- **T293 — no remedy needed.** Recommendation, pending MySQL: ship **neither** measurement index (no
  corrected query uses either; F-2, F-3); refresh statistics after bulk value loads; settle **Q-1** —
  the schema does not stop a value carrying two typed columns or a field from another initiative or
  version (data-model gives records no version link), so Phase 2's write path must.
- **Review corrections** (each with a test confirmed to fail with its fix undone):
  - the sweep's "agrees" check compared the sweep with flags it had just written — it now compares the
    re-derived set with the stored set taken before the sweep, id for id, on every run;
  - the grid now joins students and active targets in roster order, and the per-section count runs over
    active targets with each section's number and branch — what the screens will run;
  - the upsert now writes `completed_at`, `updated_by` and an audit entry in the same transaction, and
    writes a Yes/No field's own column (it had once put a Yes/No answer on the text field — caught by
    the new integrity check, which runs on every spike run);
  - text values lengthened to realistic Arabic notes (avg 76 characters, up to 483; database 214 MB);
  - the seeder refuses any connection but `eav_spike` and refuses production; quickstart scenario 10 no
    longer tells operators to seed the application database;
  - the MySQL schema refusal is case-insensitive, strips backticks, and covers every configured
    connection's database and the system schemas; the schema is created if missing;
  - the measurement-index migration is a no-op for the application (`eav_spike.measurement_indexes`
    false); the spike switches it on for its own database only;
  - plans are captured for the completion-rule queries too, with the engine version and machine;
  - containment also scans config/, bootstrap/ and resources/js, and catches raw SQL on `initiatives`;
  - level-4 duplicates (T215) are now proven at the database (`SpikeToolingSafetyTest`), and the stale
    skip on the source-text check is gone.
- **Shape** (`EavSpikeSeeder`): initiative 1 runs in all 3,000 sections and carries the 30,000 records,
  each with all 50 fields' values (23 text, 12 number, 7 Yes/No, 5 date, 3 JSON ≈ R-06's mix) — a
  deliberate worst case for one initiative; section 1 holds 200 students for the grid query; values from
  a seeded generator; `is_complete` seeded consistent with the values. Seeding 1.5M values: ~20 s.
- **Where it runs:** its own connection (a SQLite file in `storage/app`, git-ignored and outside deploys
  and backups, or a MySQL schema of its own) — never the suite's in-memory database or the application's.
- **T291** — `EavSpikeContainmentTest` scans what the running application loads for the six Group 6 table
  names, `initiatives` used as a table (builder or raw SQL) and `Initiative*` models, asserts no
  initiative route, and proves its own patterns match references and ignore the navigation stub.
- **Visual suite, alongside this phase.** The Phase 11 follow-ups' baselines were regenerated and
  reviewed (382 changed, every one explained: the sign-out label's new colour, user 12's row and count,
  the roster's Actions column without sort arrows, the new English banner shot, and sub-threshold
  anti-aliasing), then confirmed by a separate run. That run showed the first-paint budget failing at
  2.1–3.3 s *only under the suite's own load* — the second worker was loading the same single-process
  dev server while a page was timed. First paint now runs in its own `first-paint` project, after every
  other project and alone (in isolation the slowest page is 1.6 s); a timing taken under someone else's
  load measured the queue, not the page. A dependent project is skipped when a dependency fails, so the
  gate also runs `npx playwright test --project=first-paint --no-deps` on its own (quickstart.md). One
  regeneration run lost its dev server part-way (connection refused from test ~1,180); a 1,500-request
  load test and three full runs since did not reproduce it. Plausibly the Windows built-in server's
  single process (`PHP_CLI_SERVER_WORKERS` has no effect on Windows); CI runs on Linux, where it does.

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

- [X] T294 Create the `programs` migration with `organization_id`, `code` varchar(20) stored uppercase with **unique `(organization_id, code)`**, `name_ar` and `name_en` varchar(255) both required, `unit` enum(`admin`,`tech`), `is_active` boolean default true, and `deleted_at` (FR-089)
- [X] T295 Create the `course_program` pivot migration with `course_id` and `program_id` cascade foreign keys and **unique `(course_id, program_id)`** (FR-091)
- [X] T296 Add `program_id` to the `sections` migration as a **non-nullable** FK → `programs.id` with **restrict** on delete, plus an index `(program_id)`. Non-nullable from the start is deliberate: adding it after 3,000 sections exist would require a backfill with no source of truth (FR-090, FR-092, FR-094)
- [X] T297 [P] Add `degree` varchar(40) default `bachelor` and `activity` varchar(40) default `lecture` to the `sections` migration — **not** database enums, so adding a value later needs no migration (FR-108, FR-109, constitution IV)
- [X] T298 [P] Add `academic_status` enum(`regular`,`withdrawn`,`excused`) nullable and `roster_sequence` int unsigned nullable to the `section_enrollments` migration, **separate from** `status` (FR-110, FR-112)
- [X] T299 [P] Extend the `import_jobs` migration: `mode` enum gains `registrar`, `course`, `user`; add nullable `instructor_id` FK → `users.id` and `file_metadata` json (FR-096, FR-101, FR-103, FR-104)
- [X] T300 Create `app/Models/Program.php` with `SoftDeletes`, the `Auditable` trait, the `courses` belongs-to-many and `sections` has-many relations, and a `scopeActive()`
- [X] T301 [P] Add the `program` belongs-to relation to `app/Models/Section.php` and the `programs` belongs-to-many to `app/Models/Course.php`
- [X] T302 Extend `database/seeders/PermissionSeeder.php` with `program.view`, `program.create`, `program.edit`, `import.registrar.run`, `import.course.run`, and `import.user.run` — **47 permissions total** (the 42 first written here predated Amendments 2 and 4; see the count in `PermissionSeeder`) — and grant all six to `SUPER_ADMIN`, plus `program.view` to `BRANCH_COORDINATOR`. **Folded into T045**, which seeds the whole 47-permission set in one list rather than having a second seeder append to it
- [X] T303 [P] Extend `database/seeders/SettingSeeder.php` with `section_degrees` = `[{"value":"bachelor","ar":"البكالوريوس","en":"Bachelor"},{"value":"intermediate_diploma","ar":"الدبلوم المتوسط","en":"Intermediate Diploma"}]` and `section_activities` = `[{"value":"lecture","ar":"محاضرة","en":"Lecture"}]`
- [X] T304 [P] Implement `app/Modules/Shared/Rules/InSetting.php`, a validation rule checking a value against a named settings list — used by the section form, the section import, and the registrar import for both `degree` and `activity` (FR-108, FR-109)
- [X] T305 [P] Extend `database/seeders/UiFixtureSeeder.php` and the factories with the 9 programs from `design-system/ui_kits/web-app/store.js` (HR, ACC, LOG, MKT, CS, APP, GD, RE, EC with their admin/tech units), their course associations, each section's program, and the degree, activity, academic-status, and sequence values the reference screens display

**Verification and corrections (2026-10-09).** These tasks were implemented with Phase 2 (commit
`80280e0`) and already checked. Re-verified each against its text: migrations, models and relations,
the six permissions and their grants, the two settings lists and the nine fixture programmes match.
Three gaps were found and fixed, each with a test confirmed to fail with its fix undone
(`tests/Feature/AcademicStructure/ProgramsAndSettingsTest.php`):

- **T294 — the programme code was not stored uppercase.** Nothing normalised it, so on a case-sensitive
  collation `hr` and `HR` passed the `(organization_id, code)` key as two programmes. `Program::code` now
  stores it trimmed and uppercase whoever writes it.
- **T304 — `InSetting` was not used.** The section form validated `degree` and `activity` with its own
  `Rule::in(Settings::values(...))`; it now uses `InSetting`, the shared rule the registrar import
  (Phase 15) will use too. The rule's message, `validation.in_setting`, did not exist in either language
  — added.
- **T304 — `InSetting` printed the raw field name.** It passed `attribute => 'degree'` to its message,
  overriding the translated name, so an Arabic page read «في degree». It now leaves `:attribute` to the
  lang file's attribute map.

**Review corrections (2026-10-10).** A code review of the phase found ten more; each is covered in
the same test file.

- **Organization isolation.** The section form's `program_id` was a bare `exists:programs,id`, and both
  programme pickers (section form, import mapping) listed every organization's programmes. Validation and
  pickers now take the caller's organization's active, undeleted programmes only. An edited section may
  keep its own programme after that programme is deactivated. The import mapping's `new_course_program_id`
  is scoped the same way.
- **T294, every write path.** The code mutator covered only Eloquent writes. The programs migration now
  adds an engine-level guard (a CHECK on MySQL/PostgreSQL, triggers on SQLite) that refuses an empty,
  untrimmed or lowercase code, so query-builder `update()`/`insert()`/`upsert()` (Phase 15 bulk writes)
  cannot bypass it. A null code now fails NOT NULL instead of being stored as `''`.
- **Soft delete × restrict FK.** The unique key deliberately keeps `deleted_at` out (FR-089: unique
  within the institution); reusing a code means restoring. `Section::program()` loads a soft-deleted
  programme instead of null. `Program::forceDelete()` refuses with a translated 409 (FR-092) when the
  programme holds sections, instead of raising a raw FK error.
- **Seeders.** `SettingSeeder` uses `firstOrCreate`, so re-seeding no longer overwrites a Super Admin's
  edits. `UiFixtureSeeder` refuses to run outside `local`/`testing`.
- **Factories.** `SectionFactory` takes the course and programme from the branch's organization.
- **T302 count.** It is 47, not 42, and a test now pins the count, the grants, and seeder idempotency.

---

## Phase 14: Programs — Endpoints & Screens *(interleaves with Phase 7 / US4)*

### Tests

- [X] T306 [P] [US4] Write `tests/Feature/Programs/ProgramCrudTest.php` covering creation, the duplicate-code refusal naming the code, editing, and deactivation (FR-089, FR-092)
- [X] T307 [P] [US4] Write `tests/Feature/Programs/CannotDeleteProgramWithSectionsTest.php` asserting permanent removal is refused at both the route level (no `DELETE` exists) and the database level (restrict FK), and that deactivation is offered instead (FR-092, SC-021)
- [X] T308 [P] [US4] Write `tests/Feature/Programs/CourseProgramAssociationTest.php` asserting a course associates with several programs and a program holds several courses (FR-091)
- [X] T309 [P] [US4] Write `tests/Feature/Sections/SectionRequiresProgramTest.php` asserting section creation without `program_id` is refused, that `program_id` cannot be set to null on edit, and that **zero** sections exist without a program after the roster import (FR-090, SC-019)
- [X] T310 [P] [US4] Write `tests/Feature/Sections/DegreeAndActivityValidationTest.php` asserting a value outside the settings-defined set is refused, and that adding a new value to the setting makes it immediately valid with no migration (FR-108, FR-109)
- [X] T311 [P] [US4] Write `tests/Feature/Sections/DeactivatedProgramTest.php` asserting a deactivated program is refused on section create but retained and displayed on an existing section (spec Edge Cases)
- [X] T312 [P] [US4] Write `tests/Authorization/ProgramEndpointsTest.php` with allow and deny cases for all 5 program endpoints in [contracts/academic.md](./contracts/academic.md)
- [ ] T313 [P] [US4] Write `tests/Visual/programs.spec.ts` screenshotting the programs list and its create/edit dialog against the `ProgramsScreen` reference, both locales, all four widths

### Implementation

- [X] T314 [US4] Implement `app/Modules/AcademicStructure/Http/Controllers/ProgramController.php` with `GET /programs`, `GET /programs/{id}`, `POST /programs`, `PUT /programs/{id}`, and `PUT /programs/{id}/courses`, uppercasing the code and refusing duplicates with 422
- [X] T315 [P] [US4] Implement `app/Modules/AcademicStructure/Policies/ProgramPolicy.php` mapping each action to its `program.*` permission, and register it. **Note**: `Program` takes **no** scope trait — it is institution-wide reference data with no branch dimension, so a coordinator may read programs while still seeing only their own branches' sections within them (plan.md Amendment 1 re-check)
- [X] T316 [P] [US4] Add the `program.created`, `program.updated`, `program.deactivated`, and `program.courses_changed` action keys to the audit label translations in `lang/ar/audit.php` and `lang/en/audit.php`
- [X] T317 [US4] Build `resources/views/programs/index.blade.php` to the `ProgramsScreen` reference — `x-track-page-header` with the subtitle "كل شعبة في الكلية مرتبطة ببرنامج أكاديمي واحد", a create action and an import-courses action, and `x-track-data-table` showing code in LTR bold, both names, specialty unit, course count, section count, and an active `x-track-status-badge`
- [X] T318 [US4] Build `resources/views/programs/_dialog.blade.php` to the `ProgramsScreen` dialog reference — `x-track-dialog` at 560px with a two-column grid: code in LTR, specialty-unit `x-track-select`, both names, an active `x-track-switch` spanning both columns, and the cannot-delete `x-track-alert` tone info shown when editing
- [X] T319 [US4] Add the **required** program `x-track-select` to `resources/views/sections/_dialog.blade.php`, plus the degree and activity selects populated from settings, and add the program column and filter to `resources/views/sections/index.blade.php` (FR-094)
- [X] T320 [P] [US4] Add both statuses to the section roster table in `resources/views/sections/show.blade.php` — TRACK's `status` and the registrar's `academic_status` as **separate** columns, each with its own `x-track-status-badge`, plus `roster_sequence` as the leading numeric column where present (FR-111)
- [X] T321 [P] [US4] Add the `programs` translation keys to `lang/ar/` and `lang/en/`, and restore the `programs` entry to `config/navigation.php` for `SUPER_ADMIN` with the `graduation-cap` icon, matching the approved sidebar

**Implementation notes (2026-10-10).**

- **T313 is open.** `tests/Visual/programs.spec.ts` is written and `programs` is in the visual screen
  registry, but the suite has not been run, so no baselines exist. Restoring the sidebar entry (T321)
  also changes every Super Admin baseline. Capture both with `npx playwright test --update-snapshots`,
  then review the diffs.
- **T316** needed no change: the four audit labels were already in both languages. `program.deactivated`
  and `program.courses_changed` are no longer in `actionsAwaitingTheirPhase()`. ActionCoverageTest
  now exercises both through the endpoints.
- **Beyond the task text:**
  - **Sections:** the section detail header names the programme (FR-094), and the section-form
    programme select has a "Select program…" placeholder instead of preselecting the first programme.
  - **Roster:** sorted by `roster_sequence` where present.
  - **Status badges:** labels and tones for `regular` and `excused`.
  - **Programme dialog:** collapses to one column below 768px (`.track-form-grid`).

**Review corrections (2026-10-10).** A code review of the phase found ten more:

- **Duplicate-code race.** The `(organization_id, code)` unique key is now the final word. A
  violation it raises is answered with the same 422 naming the code, not a 500.
- **Code locked once used.** A programme holding sections keeps its code (422 `code_locked`), and the
  edit dialog makes the field read-only for it. Reports and registrar files name a programme by code.
- **Section create dialog: organization boundary.** Courses, branches and instructors come from the
  caller's organization only, and instructors must be active. Option lists are read as plain columns,
  not hydrated models.
- **`PUT /sections/{id}`: every field optional, as the contract says.** An omitted field keeps its
  stored value, and the combination it produces is still checked for uniqueness. An explicit null
  programme is still refused.
- **Coordinators and `/programs`.** A coordinator may read the screen, with no create, edit or
  import control. Only Super Admin manages programmes (FR-095). Recorded in `config/navigation.php`
  and pinned by ProgramEndpointsTest.
- **Coordinator section counts.** A test now pins that a coordinator sees only their own branches'
  sections in the counts (Amendment 1).
- **Programme search.** The list searches on the server, so a programme on page 2 is found. Unit and
  status filters were added, and the empty state reflects an active search.
- **Roster order.** Whether a roster is sequenced is decided per section, not per page. A sequenced
  roster follows the registrar's order: a withdrawn student keeps their place, unsequenced rows
  follow, and id breaks ties. New index `(section_id, roster_sequence)`.
- **Performance.** Filter and create options select id and label only. Short-TTL caching was left
  out: nothing invalidates it when an import writes sections through the query builder, and a stale
  filter list is a correctness bug, not a speed-up.
- **Icons.** Students uses `user`, so `graduation-cap` means Programs only.

**Visual baselines to review when T313 runs.**

- **Every Super Admin screen:** the sidebar gains Programs between Branches and Courses, and the
  Students icon changes to `user`. Check that this is the only sidebar difference, then check the
  rest of each page separately.
- **Sections list:** gains the programme column and filter.
- **Section detail:** the header names the programme, and the roster gains the academic-status
  column (plus the sequence column where the fixture has one).
- **New baselines:** the programmes list, filter row, and create and edit dialogs, at all four widths
  in both languages. At 390px the dialog must be one column.

---

## Phase 15: Registrar Import Mode *(interleaves with Phase 8 / US5, after T233)*

### Tests

- [X] T322 [P] [US5] Write `tests/Feature/Imports/RegistrarMetadataBlockTest.php` asserting the metadata block above the header row is parsed into campus, degree, course, activity, and section number, and that a file without one is refused at upload with a message naming the other modes (FR-097, spec Edge Cases)
- [X] T323 [P] [US5] Write `tests/Feature/Imports/ResolveBranchFromCampusTest.php` asserting "المجمعة (طلاب)" resolves to exactly one branch, and that a campus resolving to none or to several rejects the file before any row is processed (FR-099)
- [X] T324 [P] [US5] Write `tests/Feature/Imports/RegistrarCourseMatchTest.php` asserting an Arabic course-name match succeeds unambiguously, that no match rejects the file, and that several matches flag the rows for the operator rather than guessing (FR-100)
- [X] T325 [P] [US5] Write `tests/Feature/Imports/RegistrarRealFileTest.php` importing `design-system/uploads/filedownloadservlet(21).xlsx` **unmodified** end to end and asserting the 22 students land in the resolved section (the file holds 22; "19" was the UI fixture's count, which seeds the first 19) with their academic status and sequence — this supersedes T221, which only recorded why the file could not be mapped to the central mode (SC-020)
- [X] T326 [P] [US5] Write `tests/Feature/Imports/AcademicStatusNeverOverwritesWithdrawalTest.php` asserting that importing a registrar status of `regular` over a TRACK-withdrawn enrollment leaves `status`, `withdrawn_at`, and `withdraw_reason` untouched, and that a TRACK withdrawal leaves `academic_status` untouched (FR-111, SC-022)
- [X] T327 [P] [US5] Write `tests/Authorization/RegistrarImportEndpointTest.php` with allow and deny cases for `POST /imports/registrar`
- [ ] T328 [P] [US5] Write `tests/Visual/us5-registrar.spec.ts` screenshotting the registrar upload step and its parsed-metadata confirmation panel. **No React reference exists** — the mode was accepted after the design system was produced — so the baseline is taken from the Blade implementation and reviewed by the design author, as the queue-waiting state is ([design-system.md](./design-system.md) §9 row 26)

### Implementation

- [X] T329 [US5] Implement `app/Modules/Imports/Pipeline/MetadataBlockReader.php` streaming the rows above the header row and extracting campus, degree, course, activity, and section number by Arabic label match (FR-097)
- [X] T330 [US5] Implement `app/Modules/Imports/Pipeline/ResolveBranchFromCampus.php` splitting a campus string such as "المجمعة (طلاب)" into campus and gender and resolving it to exactly one branch, returning a distinct no-match and ambiguous-match result so the caller can reject with the right reason (FR-099)
- [X] T331 [US5] Implement `POST /imports/registrar` in `app/Modules/Imports/Http/Controllers/RegistrarImportController.php` requiring `term_id` and `instructor_id`, parsing and resolving at upload so the operator sees what the file was understood to mean before mapping, and returning the `file_metadata` and `resolved` payloads (FR-096, FR-101)
- [X] T332 [US5] Implement the registrar mode in `app/Modules/Imports/Modes/RegistrarMode.php` — four data columns (sequence, university id, student name, academic status), no mapping step needed beyond confirmation, and the three additional row-level reason categories from [contracts/imports.md](./contracts/imports.md) (FR-098)
- [X] T333 [US5] Extend `app/Modules/Imports/Pipeline/Resolver.php` for registrar mode: write `academic_status` and `roster_sequence` on the enrollment and **never** touch `status`, `withdrawn_at`, or `withdraw_reason` (FR-111)
- [X] T334 [P] [US5] Build `resources/views/imports/step-upload-registrar.blade.php` — the upload zone plus an `x-track-card` confirmation panel showing the parsed campus, degree, course, activity, and section alongside the resolved branch, course, and section, with term and instructor `x-track-select` controls, and `x-track-alert` danger for an unresolvable campus or course
- [X] T335 [P] [US5] Add the registrar-mode translation keys — including `import.ambiguous_course`, `import.unknown_academic_status`, `import.invalid_sequence`, and the not-a-registrar-export message — to `lang/ar/imports.php` and `lang/en/imports.php`

**Implementation notes (2026-10-10).**

- **T328 is open.** `tests/Visual/us5-registrar.spec.ts` is written, and both screens are in the
  visual registry. The fixture gained registrar jobs 17 (ar) and 18 (en) at the confirmation step.
  The suite has not been run, so no baselines exist. No React reference exists for this mode, so the
  first capture must be reviewed by the design author before it is accepted (design-system.md §9
  row 26). The two new jobs also add rows to the imports-list baselines.
- **T325 counts 22, not 19.** The real file holds 22 students; 19 is what the UI fixture seeds from
  it.
- **Choices are made at the confirmation step, not at preview.** The contract says the operator
  picks an ambiguous course "at preview", but every import choice is made at mapping, and commit
  refuses `options`. So the course choice (and the programme for a new section whose course has no
  single programme) is made on the confirmation panel. The rows are still flagged
  `ambiguous_course` at preview, and the operator can go back to remap. Without a choice, the rows
  are skipped and listed in the report.
- **Upload refusals.** As T323 and T324 and the contract's 422s require, a missing block, an
  unresolvable campus and an unknown course are refused at upload, and nothing is kept.
- **Re-import (FR-111).** A re-imported student has only `academic_status` and `roster_sequence`
  written, through the query builder, so no `enrollment.updated` audit entry is written. An
  unreadable value does not erase an earlier one.
- **Upload audit entry.** `import.uploaded` for this mode carries the parsed block and the
  instructor.
- **Pre-existing bug fixed (`x-track-select`).** Any integer-keyed map submitted its label instead
  of its key. That broke every id-keyed select posted from a browser: the section dialog, the upload
  term selects, and the section and audit filters. It now uses `array_is_list()`, covered by
  `tests/Feature/Shared/SelectOptionValuesTest.php`.

**Review corrections (2026-10-10).** A code review of the phase found these; each is covered in
`tests/Feature/Imports/RegistrarReviewCorrectionsTest.php` unless noted.

- **H1 — no new section under a deactivated programme.** "The course's single programme" now counts
  active programmes only, through `Course::activeProgramIds()`, in the registrar validator, the
  resolver, and the central validator (the central mode had the same gap).
- **M1 — the programme choice is limited to the course's own active programmes**, at the
  confirmation step and again when the check runs. The panel offers only those programmes.
- **M2 — docs.** `contracts/imports.md` now describes the confirmation-step choices, the re-import
  write, and the `duplicate_sequence` warning. `plan.md` records the decision.
- **M3 — degree/activity notices.** A degree or activity the settings list lacks, or an existing
  section's differing one, becomes a notice on the preview and in `import.committed`, instead of
  being swallowed.
- **M4 — the values a re-import replaces** are kept on the import row as `previous_*`. A value equal
  to the stored one is not written; it is already primed in the chunk's one query, so no extra query
  is needed.
- **L1 — fragile select inference.** The programmes status filter passes value/label rows, and the
  component documents the one shape it cannot tell apart. The select tests cover a leading `''` key,
  0..n-1 maps, and re-selection.
- **L3 — docs.** `design-system.md` row 26 has the real route and a status note. T221 is annotated
  as superseded by T325.
- **L4 — focus.** A refused upload's message takes focus.
- **L5 — repeated sequence.** A sequence repeated in one file is a `duplicate_sequence` warning; the
  value is kept.
- **L6 — zip expansion.** An xlsx declaring more than 15 × the upload limit once unpacked is refused
  before it is parsed.
- **Not changed — L2.** Re-import updates stay one per changed row. A registrar file is one section,
  and unchanged rows are no longer written at all.
- **Out of scope for T328.** The ambiguous-course and refused-upload states are covered by feature
  tests, not screenshots. The refused state needs a session error, which a fixture cannot hold.
- **Not changed — `AGENTS.md` / `CLAUDE.md`.** Both contain only the Laravel Boost bootstrap, which
  `boost:install` regenerates, so phase status is kept here.

---

## Phase 16: User Story 8 - Courses And Staff Load In Bulk (Priority: P8) *(after Phase 9 / US6)*

**Goal**: Courses and staff accounts load through the same six-step wizard, with created accounts unusable until their password is reset.

**Independent Test**: Upload a course file with one new course, one existing code, and one unknown program; then a staff file with one new instructor, one duplicate email, and one coordinator with no branch. Confirm nothing is written before confirmation and every created account is refused sign-in.

### Tests for User Story 8

- [X] T336 [P] [US8] Write `tests/Feature/Imports/CourseImportTest.php` asserting new courses are created, existing codes are matched rather than duplicated, and program associations are applied (FR-103)
- [X] T337 [P] [US8] Write `tests/Feature/Imports/CourseImportProgramCasesTest.php` asserting an unknown program flags the row for create-or-skip and a deactivated program associates only on confirmation (spec Edge Cases)
- [X] T338 [P] [US8] Write `tests/Feature/Imports/UserImportTest.php` asserting accounts are created with their role and, for coordinators, their branches (FR-104)
- [X] T339 [P] [US8] Write `tests/Feature/Imports/UserImportUnusableUntilResetTest.php` asserting **every** created account is refused sign-in until a Super Admin reset sets a real hash (FR-105, SC-024)
- [X] T340 [P] [US8] Write `tests/Feature/Imports/UserImportRejectionsTest.php` asserting a duplicate email — including against a soft-deleted account — a coordinator with no branch, an unknown role, and an unknown branch are each rejected with their own reason (FR-104, FR-005, FR-004)
- [X] T341 [P] [US8] Write `tests/Authorization/BulkImportEndpointsTest.php` with allow and deny cases for `POST /imports/courses` and `POST /imports/users`, including that an **instructor** is refused both (FR-107)
- [X] T342 [P] [US8] Write `tests/Performance/BulkImportTest.php` asserting 40 courses and 30 staff accounts load in under 10 minutes total (SC-023), in the `performance` group, excluded from the default run
- [ ] T343 [P] [US8] Write `tests/Visual/us8.spec.ts` screenshotting both wizard entry points against the `CourseImportDialog` and `UserImportDialog` references, both locales

### Implementation for User Story 8

- [X] T344 [US8] Implement the course mode in `app/Modules/Imports/Modes/CourseMode.php` — column contract (code, name AR, name EN, programs), the four row-level reason categories from contracts/imports.md, and resolution that links existing codes and applies program associations
- [X] T345 [US8] Implement `POST /imports/courses` in `app/Modules/Imports/Http/Controllers/CourseImportController.php` behind `import.course.run`
- [X] T346 [US8] Implement the user mode in `app/Modules/Imports/Modes/UserMode.php` — column contract (name AR, name EN, email, role, branches), its four row-level reason categories, and account creation with an **unusable** password hash and `first_login_at` null (FR-105)
- [X] T347 [US8] Implement `POST /imports/users` in `app/Modules/Imports/Http/Controllers/UserImportController.php` behind `import.user.run`
- [X] T348 [P] [US8] Build `resources/views/imports/step-upload-courses.blade.php` and `step-upload-users.blade.php` to the `CourseImportDialog` and `UserImportDialog` references, reusing the wizard frame with a template-download button for each shape
- [X] T349 [P] [US8] Add the course-import entry point to `resources/views/programs/index.blade.php` as the "استيراد المقررات من Excel" secondary action the reference shows, and the staff-import entry point to `resources/views/users/index.blade.php`
- [X] T350 [P] [US8] Add the course- and user-import translation keys, including every new reason key, to `lang/ar/imports.php` and `lang/en/imports.php`

**Checkpoint**: Term setup no longer requires typing each course and account by hand.

**Implementation notes (2026-10-11).**

- **T343 is open.** `tests/Visual/us8.spec.ts` is written and both upload steps are in the visual
  registry. It has not been run, so no baselines exist. The references are single dialogs, while
  the build opens the six-step wizard (FR-106), so the dialog body is compared as the wizard's
  first step.
- **Wizard, not dialog.** The CourseImportDialog / UserImportDialog references check and import in
  one go. Here the same upload runs through check, preview and confirmation, which FR-106 requires.
- **No term.** A catalogue and a staff list are the college's. The job's `term_id` is null, and
  FR-067's duplicate check is judged within the organization (`ImportJob::duplicateOf`).
- **Existing courses** are matched. Their stored names stand, and the row's programmes are added,
  never removed.
- **Unknown programmes** are created, or the row is skipped (`create_missing_programs`). A created
  programme's code is the name when it is a valid code, otherwise a generated `IMP-` code.
  Deactivated programmes are associated only when `associate_inactive_programs` is on.
- **Unusable accounts (FR-105).** These follow FR-059: a hash of 64 random characters nobody is
  told. Tested by trying likely passwords, then a reset.
- **Beyond the contract.**
  - Reason keys: `import.ambiguous_program` (a name two programmes share), `import.too_long`, and
    the informational `import.existing_course`.
  - A branch name without a gender that matches two branches is `unknown_branch`.
  - `CreateUser` now accepts an `organization_id`, so imported accounts land in the operator's
    organization rather than "the first college".
- **Entry points.** The imports list also offers both imports, beside the programmes page and the
  users page (T349).

**Review corrections (2026-10-11).** A code review found ten issues, all fixed and covered in
`tests/Feature/Imports/BulkImportReviewCorrectionsTest.php`:

1. **Organization of created accounts.** Every account now lands in its creator's organization.
   `CreateUser` defaults to the acting user's organization; the form passes it, and so does the
   roster import's instructor creation, which runs with nobody signed in.
2. **Re-uploads.** Course and staff jobs are never committed link-only. A re-uploaded file still
   gets `duplicate_of`, but it creates whatever the last run did not.
3. **No bare courses.** A new course never leaves with no programme. Only-deactivated programmes,
   unconfirmed, are a skip at the preview and at commit.
4. **Conflict column.** A commit conflict names the row's own column (email, course code), not a
   university id these rows do not have.
5. **Concurrent programme creation.** A programme created concurrently is matched (savepoint, then
   re-read by code) instead of failing as a conflict. Generated codes are `IMP-` plus 10 hex digits.
6. **Programme lookup at commit.** Programmes load once per chunk under the validator's own key,
   respecting ambiguity and `associate_inactive_programs`.
7. **Password hashing.** One unusable hash per chunk, shared by its accounts, instead of one per
   account. It is equally unusable.
8. **Email lookup.** The taken-email lookup uses the `users.email` index.
9. **Branches.** Branches are resolved for coordinators only, as the contract says.
10. **Roles.** Only staff roles may be imported. `SUPER_ADMIN` is refused as `unknown_role`.

Also covered by tests now: no programme, association, account or branch link is written before
confirmation, and a committed job cannot be committed again.

---

## Phase 17: `last_login_at` *(interleaves with Phase 4 / US1, with T116)*

- [X] T351 [P] [US1] Write `tests/Feature/Authentication/LastLoginTest.php` asserting `last_login_at` is updated on every successful sign-in, is not set by a failed attempt, and renders on the accounts list (FR-113)
- [X] T352 [US1] Set `last_login_at` in `app/Modules/Authentication/Http/Controllers/LoginController.php` on every successful sign-in, alongside the existing `first_login_at` handling
- [X] T353 [P] [US1] Add the `last_login_at` column to `resources/views/users/index.blade.php` as "آخر دخول" formatted through `Format::dateTime()`, and to the profile screen as the reference's "آخر دخول" line

**Implementation notes (2026-10-11).** T352 and T353 had already been built with T116 in Phase 4,
and were verified against their text rather than rebuilt. `LoginController` writes `last_login_at`
in the same `saveQuietly()` as `first_login_at`, after `Auth::login()` succeeds. The users list's
«آخر دخول» column and the profile's line both go through `Format::dateTime()` and say "never" when
the value is null. T351 is the new `tests/Feature/Authentication/LastLoginTest.php`. It covers:

- every successful sign-in moving the value, with `first_login_at` left alone;
- three failed paths not touching it: wrong password, unknown email, deactivated account;
- the browser form as well as the API;
- both screens, in both languages.

**Review corrections (2026-10-11).** A code review found ten issues, all fixed and covered in
`tests/Feature/Authentication/LastLoginTest.php`:

1. **Organization boundary.** The accounts list, the API and the per-account actions are limited to
   the viewer's organization. Another organization's account is a 404. The list previously showed
   every college's staff and their sign-in times.
2. **A sign-in that cannot be recorded does not stand.** If the timestamp or `auth.login` write
   fails, the session is signed out again before the error.
3. **Midnight sign-ins.** `Format::dateTime()` always converts to the display timezone; a sign-in at
   00:00 UTC no longer shows as 00:00. `date()` keeps its calendar-date rule.
4. **Sorting.** Sorting by last sign-in is done by the server, across all pages ("not signed in
   longest" first), with a new `(organization_id, last_login_at)` index. The column no longer sorts
   only the page on screen.
5. **Page size.** `per_page` is capped at 100.
6. **Tests.** Exact status codes and frozen time. New cases cover throttled attempts, imported
   accounts, the profile in both languages and with no value, and organization isolation.
7. **First sign-in.** "First sign-in" is decided by a conditional update, so two simultaneous first
   sign-ins record one.
8. **No edit stamp.** A sign-in no longer changes `updated_at`; both writes go through the base
   query builder.
9. **Duplicate key removed.** The unused `common.labels.never` is gone; "never" has one key.
10. **One null rule.** Both screens use `Format::dateTime()`'s own empty value. The mixing of other
    tasks' work into these files predates this phase and cannot be separated after the fact.

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

- [X] T354 Create the module skeleton at `app/Modules/Support/` with subdirectories `Http/Controllers`, `Http/Controllers/Public`, `Http/Requests`, `Http/Middleware`, `Actions`, `Services`, `Services/Challenge`, `Policies`, and `Tests`, and register it in `app/Providers/ModuleServiceProvider.php`
- [X] T355 Create `routes/public.php` and register it in `bootstrap/app.php` on the `web` middleware group **without** `auth:sanctum`, deliberately separate from `routes/web.php` so the entire public surface of the platform is readable in one short file
- [X] T356 [P] Register the `support` translation namespace and create empty `lang/ar/support.php` and `lang/en/support.php`
- [X] T357 [P] Add `tests/Feature/Support/`, `tests/Feature/Support/Enumeration/`, `tests/Feature/Support/Throttle/`, and `tests/Feature/Support/Retention/` directories, and register the `Enumeration` group in `phpunit.xml` so it can be run alone
- [X] T358 [P] Vendor the self-hosted proof-of-work challenge widget into `resources/js/vendor/` and `public/js/`, and **confirm it makes no request to any external origin** — if it does, use the hand-rolled fallback described in [research.md](./research.md) R-08 instead. Record which was chosen in `README.md`
- [X] T359 [P] Confirm the strict Content-Security-Policy from 001's `ContentSecurityPolicy` middleware needs **no new exemption** for this feature, and add `tests/Feature/Security/NoNewCspExemptionTest.php` asserting the policy is unchanged

**Implementation notes (2026-10-11).**

- **T358: the hand-rolled fallback, not ALTCHA.**
  - ALTCHA 3.3.0 was inspected, not installed. It requests no external origin; `altcha.org` is a
    footer link. But it starts its workers from `blob:` URLs, falling back to `data:` URLs. This
    platform's CSP refuses both (`worker-src` falls back to `script-src 'self'`), so allowing it
    would be the exemption T359 forbids.
  - Used instead: a hand-rolled solver, first written as two files. The review replaced them with
    `resources/js/pow/`; see the corrections below.
  - Recorded in `README.md`, and guarded by `tests/Architecture/ChallengeSolverIsSelfHostedTest.php`.
- **T357: a testsuite, not a group.** `Enumeration` is a PHPUnit testsuite, so it runs alone
  (`php artisan test --testsuite=Enumeration`). It is excluded from `Feature` so a full run does
  not execute it twice. A placeholder test keeps the suite resolvable until Phase 21.
- **T356.** `support.php` is a flat, per-feature lang file like every other module's, read as
  `__('support.…')`. No module-namespace registration is needed.
- **T359.** The test pins the policy directive for directive, not by containment, and checks that
  a public page carries it.

**Review corrections (2026-10-11).** Ten findings, all fixed.

- **The public route group (T355).**
  - `routes/public.php` is registered on a named `public` group, `['web', 'throttle:public']`,
    defined in `bootstrap/app.php`.
  - `throttle:public` is a floor of 60 requests per minute per IP (`AppServiceProvider`). It is not
    the support limits: the two independent limiters of R-09 still arrive with T371.
- **"No session" means no authenticated session.** The `web` group starts Laravel's guest session,
  and that is deliberate: CSRF on a public form needs it (contracts/support-public.md). FR-124
  forbids an account, a credential or a signed-in session, and T377 asserts that.
- **One map of the public surface.** `tests/Architecture/PublicSurfaceTest.php`:
  - Every route without `auth` is either one of the pre-Amendment-2 routes it lists (sign-in,
    locale, health, CSRF cookie, dev pages) or on the `public` group.
  - Every `public` route is unauthenticated, carries `throttle:public`, and its controller sits under
    a module's `Http/Controllers/Public`.
  - A `Public` controller is reachable only through that group.
  - So a new public route cannot appear anywhere except `routes/public.php` without failing CI.
- **CSP (T359).** `NoNewCspExemptionTest` registers a probe route on the `public` group and asserts
  it carries the policy unchanged and `X-Frame-Options: DENY`.
- **The solver (T358).** **Deviation:** it lives in `resources/js/pow/`, not `resources/js/vendor/`
  plus `public/js/`.
  - The first version held the hashing loop twice, once in each file, and the copies could drift.
    Now `puzzle.js` is the one definition and the one `search`; `worker.js` and the in-page fallback
    in `solve.js` both import it.
  - `crypto.subtle` exists only in secure contexts. A pure-JS SHA-256 is the fallback, and a test
    checks it against Node's on lengths 0 to 200.
  - The search yields every 16 ms, reports progress, has an iteration cap, and honours an
    `AbortSignal`. It rejects with `invalid`, `exhausted` or `aborted`, never with a wrong nonce.
  - The worker URL is no longer a parameter. `solve.js` uses
    `new Worker(new URL('./worker.js', import.meta.url), { type: 'module' })`, and Vite
    (`worker.format: 'es'`) emits it as its own hashed file. A scratch build confirmed the bundle
    loads `/assets/worker-<hash>.js`, with no `blob:` or `data:`.
- **The puzzle is now exact, and T370 must verify exactly this.**
  - `challenge` matches `[A-Za-z0-9_-]{16,256}`.
  - `difficulty` is from 1 to 32, and the server stores it and never reads it from the request.
  - `nonce` is from 0 to 2^53 − 1.
  - The answer is valid when `SHA-256(UTF-8(challenge ":" canonical-decimal nonce))` has at least
    `difficulty` leading zero bits, counted MSB-first across bytes.
  - Any valid nonce is accepted, so "smallest" is dropped: requiring it would make the server repeat
    the whole search. Recorded in research.md R-08 and the README.
- **JS tests.** `tests/js/pow.test.mjs` uses `node:test` with no framework (`npm run test:js`). CI
  runs it after "Build assets".
- **`ChallengeSolverIsSelfHostedTest`** scans every `.js` file in `resources/js/pow/`, so a new file
  is covered automatically. With comments stripped, it forbids:
  - absolute and protocol-relative URLs;
  - `blob:` and `data:`, and `createObjectURL`;
  - `fetch`, `import()`, `importScripts`, `XMLHttpRequest`, `sendBeacon`, `WebSocket` and
    `EventSource`.

  It also pins the fixed `new Worker(new URL(...))` call. It was checked by dropping a violating
  file into the directory, which failed.
- **The Enumeration placeholder** is a `->todo()`, not an always-true assertion, so the suite reports
  "1 todo" until Phase 21. The `phpunit.xml` header now counts seven suites and names Enumeration.

---

## Phase 19: Foundational (Blocking)

**Purpose**: Schema, permissions, settings, and the four security services every story relies on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

### Migrations

- [X] T360 Create the `support_requests` migration in `database/migrations/` with: `reference` **char(12) UNIQUE** · `student_id` FK → `students.id` **nullable, restrict** (nullable **only** so retention can anonymise; never null on creation) · `term_id` FK → `academic_terms.id` restrict · `category` **varchar(40)** · `description` **text** · `contact` **varchar(255) nullable** · `status` **enum(`new`,`in_progress`,`resolved`,`closed`,`rejected`)** · `rejection_reason` **varchar(500) nullable** · `assigned_to` FK → `users.id` nullable · `submitted_ip` **varchar(45)** · `anonymised_at` **timestamp nullable**
- [X] T361 Add the indexes to the `support_requests` migration: `reference` unique · `(status, created_at)` for the queue's oldest-first default · `(student_id)` for the branch-scope join · `(category, created_at)` · `(assigned_to)` · `(created_at)` for the retention sweep
- [X] T362 [P] Create the `support_responses` migration with `support_request_id` FK cascade · `user_id` FK → `users.id` · `body` **text** · `created_at` and **no `updated_at` column**, plus index `(support_request_id, created_at)`. The absent `updated_at` is what makes the table append-only at the schema level

### Models

- [X] T363 Create `app/Models/SupportRequest.php` applying **001's existing `BelongsToBranchScope` unchanged**, resolved through the student's active enrollments; the `status` cast; the `responses` and `student` and `assignee` relations; a computed `waiting_time`; and **no update path for `reference`**
- [X] T364 [P] Create `app/Models/SupportResponse.php` with `$timestamps = false` except `created_at`, guarded against mass assignment, and **no update or delete path** reachable from application code
- [X] T365 [P] Apply 001's `Auditable` trait to `SupportRequest` with an `auditedAttributes()` allowlist that **excludes `description`** — the body lives in the request row, and copying free-text personal data into a 5-year audit payload would defeat the 2-year retention

### Permissions and settings

- [X] T366 Create `database/seeders/SupportPermissionSeeder.php` seeding `support.view`, `support.respond`, `support.assign`, `support.close` — taking the platform total from 42 to **46** — granting all four to `SUPER_ADMIN`, and **only** `support.view` and `support.respond` to `BRANCH_COORDINATOR`. `INSTRUCTOR` receives none
- [X] T367 Create `database/seeders/SupportSettingSeeder.php` seeding the seven keys from [data-model.md](./data-model.md): `support_categories` (5 entries with AR/EN labels) · `support_description_max` = **2000** · `support_throttle_ip` = **5 per hour** · `support_throttle_identifier` = **3 per hour** · `support_status_throttle_ip` = **10 per hour** · `support_min_response_ms` = **400** · `support_retention_months` = **24**

### The four security services

- [X] T368 Implement `app/Modules/Support/Services/VerifyStudentIdentity.php` resolving roster existence **and** active enrollment in the current term in **one query** returning a **single boolean**. It MUST NOT expose which condition failed, and no caller may branch on the reason ([research.md](./research.md) R-10)
- [X] T369 Implement `app/Modules/Support/Services/ReferenceGenerator.php` producing **10 characters of Crockford base32 from a CSPRNG plus a check character**, rendered uppercase and hyphenated (`K7Q2-M9XV-T3`), excluding `I`, `L`, `O`, `U`, retrying on collision. **Must not** use ULID, UUIDv7, Snowflake, or any time-ordered scheme — they leak creation order and time (R-11)
- [X] T370 Implement `app/Modules/Support/Services/Challenge/` — issue a signed single-use proof-of-work challenge stored in Redis with a 10-minute expiry and difficulty from settings, and verify-and-consume it, checking **exactly** the puzzle in [research.md](./research.md) R-08 (any valid nonce, difficulty from the stored challenge, one hash). It MUST **fail closed**: when the store is unavailable, verification returns false rather than true (FR-129)
- [X] T371 Implement `app/Modules/Support/Http/Middleware/SupportSubmissionThrottle.php` evaluating **two independent limiters**, both of which must pass: per originating IP against `support_throttle_ip`, and per submitted University ID **hashed before use** against `support_throttle_identifier`. A refusal MUST state a limit was reached and when to retry and MUST NOT reveal **which** limit (FR-130, R-09)
- [X] T372 Implement `app/Modules/Support/Http/Middleware/ConstantResponseTime.php` padding every public response to `support_min_response_ms` (default **400 ms**), because the not-found path returns after one index lookup while the not-enrolled path returns after a join, and that difference is measurable over enough samples (R-10)
- [X] T373 [P] Implement `app/Modules/Support/Policies/SupportRequestPolicy.php` mapping each action to its `support.*` permission, with `close` requiring `support.close` and `assign` requiring `support.assign`, and register it in `app/Providers/AuthServiceProvider.php`

### Public layout

- [X] T374 Create `resources/views/layouts/public.blade.php` — a standalone layout carrying **no navigation to any authenticated area**, no sidebar and no topbar (FR-163), with the brand logo, the language switch available **without a sign-in**, and `<html lang dir>` from the requested locale
- [X] T375 [P] Create `resources/css/_public.scss` for the public layout using **only** design tokens, and add it to both the RTL and LTR Vite entry points
- [X] T376 [P] Add the shared `support` translation keys to `lang/ar/support.php` and `lang/en/support.php`: the five category labels, the five state labels, the two generic failure messages, and the throttle message

**Checkpoint**: Schema migrates, 47 permissions and 9 settings seed (46 and 7 as first written; see the notes below), all four security services exist and fail closed, and the public layout renders in both directions.

**Implementation notes (2026-10-11).**

- **Deviations, each recorded where the behaviour lives.**
  - **T360: `reference` is `char(11)`, stored canonical.** R-11 says ten random characters plus a check
    character, which is 11 symbols and the 50 bits it states. Its example `K7Q2-M9XV-T3` has ten symbols
    in all, and `char(12)` was sized to that example. Stored without hyphens, uppercase; displayed as
    `K7Q2-M9XV-T3X`. So a student's typing (case, hyphens, spaces, `I`/`L`/`O`) reaches the same row.
    data-model.md and R-11 are updated.
  - **T366: no `SupportPermissionSeeder`.** The four permissions and the coordinator's two grants were
    already seeded with the base set (`PermissionSeeder`, `RoleSeeder`; T045, T473). A second seeder
    would be a second source of truth for the same rows. The total is **47**, not 46: Amendment 4's
    `section.approve` landed first. `SecurityServicesTest` asserts the grant matrix.
  - **T367: eight keys, not seven.** `support_challenge_difficulty` (default 16 bits) is added. T370
    reads the difficulty "from settings" and no key existed; a literal would break constitution IV. The
    seeder runs from `SettingSeeder`, so every path that seeds settings gets these too. Rate limits are
    one row each, `{max_attempts, decay_minutes}`, read with the new `Settings::limit()`, which throws on
    a malformed row rather than read as zero or unlimited.
  - **T369: the check character is Luhn mod 32**, not Crockford's mod-37 symbol, which needs `U`.
    It catches every single substitution and every adjacent swap except `0`↔`Z`. A unit test pins that
    blind spot exactly, and R-11 records it.
  - **T376: no category labels in the lang files.** They live with the categories in the
    `support_categories` setting. A key per category would be a second list, and a sixth category
    added through settings would never reach it.
- **T363: `SupportRequest` also carries the section scope, constrained to nothing.**
  - `BranchScope` stands aside for a caller with sections and no branches, so with the branch scope
    alone an instructor's query would have been unconstrained.
  - A mutation run confirmed the instructor test fails without it.
  - A coordinator who also teaches therefore sees no request. That is the fails-safe direction
    `SectionScope` already documents for dual claims, and it is flagged with that question.
- **T365: the audit allowlist excludes `contact` as well as `description`.** Both are student free text
  under the 2-year retention. `submitted_ip` stays in, as contracts/support-public.md requires.
  - Creation is keyed `support.request_created`, with a null actor when no one is signed in.
  - The five Amendment 2 keys are now in contracts/audit.md (43 keys), the transcribed
    `contractActionKeys()`, the deferred list (Phases 20, 21, 25) and both label files.
- **T368** returns the match (`VerifiedStudent`: student and term) or `null`, never a reason.
  - It is one query from the active enrollment outward, asserted with the query log.
  - T390 needs the student and the term, and a second query to find them would be a second timing path.
- **T370: the challenge is signed, then stored.**
  - It is a random id plus an HMAC under the app key. The HMAC is checked before the store is touched,
    so forged challenges never reach Redis.
  - It is consumed on the first attempt, right or wrong. `forget` returning true decides any race.
  - The difficulty is read back from the store, never from the request or the current setting.
  - Every failure is `false`. Issue throws `ChallengeUnavailable`, and T392 turns that into a 503.
  - An out-of-range difficulty refuses to issue rather than clamp.
  - `ProofOfWork` is the pure rule. A vector the JS solver produced is checked by both
    `tests/Unit/Support/ProofOfWorkTest.php` and `tests/js/pow.test.mjs`.
- **T371: two limiters, both checked before either is counted.**
  - The identifier key is an HMAC of the normalised ID under the app key. A plain SHA-256 of nine
    digits can be reversed by enumeration.
  - A refusal throws `SupportLimitReached`, which renders the one message for every limit: JSON for
    JSON clients, `support/public/limit-reached` otherwise. Status 429 with `Retry-After`.
- **T372** pads with `Sleep` (fakeable) from middleware entry. It falls back to 400 ms if settings
  cannot be read, and never slows a response that is already past the floor.
- **T374–T375.** The layout has no link into the authenticated side; even the logo is not a link.
  A test asserts the only `<a>` is the skip link. It is `noindex` and uses the existing guest-capable
  language switch. `_public.scss` is tokens and logical properties only.
- **Tests:**
  - `tests/Feature/Support/FoundationModelsTest.php` (13) and `SecurityServicesTest.php` (35);
  - `tests/Unit/Support/ProofOfWorkTest.php` and `ReferenceGeneratorFormatTest.php` (34).
  - Phase 20's end-to-end tests (T378–T385) still own the behaviour through the real routes.
- **Badge vocabulary.** `StatusBadgeRequiresLabelTest` exempts `support_requests` the same way it
  exempts `import_jobs`: the five states have their own labels (`support.statuses.*`), and a new test
  asserts every one exists in both languages, so the exemption cannot hide a missing label.

**Review corrections (2026-10-11).** Ten findings, all fixed. Six of the fixes were mutation-checked:
reverting each one makes its test fail.

1. **The limits held only one request at a time.** The throttle checked and then counted in two
   steps, so a simultaneous burst all read zero. `SupportLimits` now counts with
   `RateLimiter::increment` (Redis `INCR`) and refuses on the count it returns. A test freezes the
   race: the reads say 0 and the shared counter says 4.
2. **No trusted proxies.** Behind a load balancer every visitor shared one address, and five students
   an hour, institution-wide, would have been refused. `config/trustedproxy.php` reads
   `TRUSTED_PROXIES` (explicit addresses or CIDRs) and **refuses `*`**, which would let a client pick its
   own `X-Forwarded-For`. It is in `.env.example` and the runbook, and tested both ways.
3. **Anyone could lock any student out.** The identifier was counted on arrival, before the challenge.
   Now it is only checked on arrival and counted after the challenge (`consumeIdentifier`; T391's
   text updated). R-09 records the residual trade: three solved challenges still spend a student's
   limit.
4. **The padding held a worker on every refusal.** The throttle now sits outside
   `ConstantResponseTime` (T394's text updated), and the middleware returns any 429 at once, wherever
   it was raised, so the two limits cannot be told apart by timing either. A floor of 0 or below is now
   a broken setting that falls back to 400 ms, not a silent "off". The runbook sizes the PHP pool.
5. **A third refusal shape.** The `public` floor returned Laravel's 429 with `X-RateLimit-*` headers,
   and its 60 was a literal. It now reads the `public_throttle` setting (nine keys now) and refuses
   through `SupportLimitReached`. Both submission limits send the same `Retry-After`: the longer
   configured window, not the time left on whichever counter fired.
6. **Generic `support.updated` / `.deleted` entries.** `SupportRequest` now wires only the trait's
   `created` event. A later change is audited only by its named action, so the retention sweep cannot
   copy the cleared `student_id` and `submitted_ip` into a 5-year entry.
7. **`student_id` "never null on creation" was not enforced.** A `creating` guard refuses a request
   without its student or its reference.
8. **A coordinator who teaches saw an empty queue.** The section constraint now stands aside for a
   caller who holds branches. Teaching is no claim on a support request, so it neither widens nor
   narrows the branch claim. Instructors still see nothing.
9. **"Active" was one condition, not three.** Verification and the branch scope now also require an
   active section (not pending, returned or rejected; Amendment 4) and no registrar withdrawal
   (`academic_status`). Both use the same definition.
10. **References pasted from an Arabic page failed.** `normalise()` now strips `\p{Cf}` (bidi marks),
    `\p{Pd}` (Unicode dashes) and `\p{Z}`, and folds Arabic-Indic digits as `UniversityId` does. A unit
    test covers RLM/LRM, isolates, the en dash, the no-break space and both digit sets. The checkpoint
    line and plan.md's counts are corrected.

- **Tests added:** 23 cases. `tests/Feature/Support` went from 50 to 72, and `tests/Unit/Support`
  from 34 to 35.
  - Covered: the race, the lockout, identical `Retry-After`, unpadded refusals, the public floor's
    shape, trusted proxies, the floor fallback, the unapproved-section and registrar-withdrawn
    verification, seeder idempotency (47 permissions, a Super Admin's edit kept), no generic audit,
    the creation guard, the coordinator who teaches, and Arabic-page references.
- **`NoNewCspExemptionTest` now seeds settings.** Its `public`-group probe reaches the flood floor,
  which reads `public_throttle`. A deployment missing that row refuses public pages with a 500 rather
  than run them unthrottled. That is fail-closed, and it is the same contract as every other setting.

---

## Phase 20: User Story 9 - A Student Reports A Problem (Priority: P1) 🎯 MVP

**Goal**: A student submits a support request through a public page that verifies them against the roster, and receives a reference number. No account is created.

**Independent Test**: Submit one request as a genuinely enrolled student and confirm it is recorded with a reference; submit as a non-existent identifier and as a student with no active enrollment, and confirm both are refused identically. No staff-facing work needs to exist.

### Tests for User Story 9

- [X] T377 [P] [US9] Write `tests/Feature/Support/SubmitRequestTest.php` covering successful submission by an enrolled student, the recorded reference, the recorded IP, the empty-description refusal, and that **no account, credential, or session is created** (FR-124)
- [X] T378 [P] [US9] Write `tests/Feature/Support/Enumeration/SubmissionFailuresIdenticalTest.php` asserting **byte-identical bodies, identical status codes, and no varying header** across all three verification failures: identifier absent · identifier present with no enrollment in the current term · identifier present with only a withdrawn enrollment (FR-119, SC-026). **This is the single most important test in the feature**
- [X] T379 [P] [US9] Write `tests/Feature/Support/Enumeration/ResponseTimeFloorTest.php` asserting every public response takes at least `support_min_response_ms`, so the one-lookup path and the join path are indistinguishable by stopwatch (R-10)
- [X] T380 [P] [US9] Write `tests/Feature/Support/FormatFailureIsSpecificTest.php` asserting a University ID failing the configured pattern gets a **specific** message naming the expected format, and that it is returned **before** the verification query runs — the one deliberate exception to the generic-failure rule (FR-117)
- [X] T381 [P] [US9] Write `tests/Feature/Support/Throttle/IpThrottleTest.php` asserting sustained submission from one IP with **varying** identifiers is refused at `support_throttle_ip`
- [X] T382 [P] [US9] Write `tests/Feature/Support/Throttle/IdentifierThrottleTest.php` asserting sustained submission for one identifier from **varying** IPs is refused at `support_throttle_identifier`, **independently** of the IP limit. A single combined limiter passes T381 and fails this — which is the point (R-09)
- [X] T383 [P] [US9] Write `tests/Feature/Support/Throttle/ThrottleMessageHidesWhichLimitTest.php` asserting neither refusal reveals which limit was reached (FR-130)
- [X] T384 [P] [US9] Write `tests/Feature/Support/ChallengeTest.php` asserting submission is refused with no solution, with a replayed solution, with an expired solution, with the honeypot completed, within the minimum elapsed time, and — critically — **when the challenge store is unavailable** (FR-128, FR-129)
- [X] T385 [P] [US9] Write `tests/Feature/Support/ReferenceGeneratorTest.php` generating 1,000 references and asserting no sequence, no timestamp correlation, no growing shared prefix, and none of the characters `I`, `L`, `O`, `U` (SC-027)
- [X] T386 [P] [US9] Write `tests/Feature/Support/AuditOnPublicSubmissionTest.php` asserting exactly one `support.request_created` entry with a **null actor**, `channel: public`, the IP, and the resolved student and term — and that the entry contains **no** description, challenge solution, or honeypot value
- [X] T387 [P] [US9] Write `tests/Feature/Support/NoAttachmentRouteTest.php` asserting no upload route exists for this feature and the form carries no file control (FR-125)
- [ ] T388 [P] [US9] Write `tests/Visual/support-public-form.spec.ts` screenshotting the form, the confirmation, and the generic refusal in both locales at desktop and phone width

### Implementation for User Story 9

- [X] T389 [US9] Implement `app/Modules/Support/Http/Requests/SubmitSupportRequestRequest.php` validating `university_id` against 001's shared `UniversityId` rule, `category` against 001's shared `InSetting` rule reading `support_categories`, `description` present and within `support_description_max`, `contact` optional, and the honeypot and issued-at guards
- [X] T390 [US9] Implement `app/Modules/Support/Actions/SubmitSupportRequest.php` extending 001's `TransactionalAction`: resolve the student through `VerifyStudentIdentity`, generate the reference, create the request in `new`, record the IP, and audit `support.request_created` with a null actor — all in one transaction so a failed audit aborts the submission (constitution II)
- [X] T391 [US9] Implement `app/Modules/Support/Http/Controllers/Public/SupportRequestController.php` with `GET /support` and `POST /support`, applying the **contractual order of evaluation** from [contracts/support-public.md](./contracts/support-public.md): rate limits first, then challenge and honeypot and elapsed time, then **format** validation, then other fields, then verification. Refusing first means no later step can be used as an oracle. **(Phase 19 review, moved by the Phase 20 review)** Once the challenge is verified and consumed **and the field rules have passed**, call `SupportLimits::consumeIdentifier($universityId)`: the throttle only *checks* the identifier on arrival, and this is where it is *counted* (research.md R-09)
- [X] T392 [US9] Implement `GET /support/challenge` in `app/Modules/Support/Http/Controllers/Public/ChallengeController.php` returning the challenge, its difficulty, and its expiry, and **503** with a generic message when the store is unavailable
- [X] T393 [US9] Implement `GET /support/submitted` reading the reference from a **single-render flash**, not from the URL, so it does not land in browser history, a proxy log, or a shared link
- [X] T394 [US9] Register all four routes in `routes/public.php` with the `SupportSubmissionThrottle` and `ConstantResponseTime` middlewares — **in that order, the throttle outside** (Phase 19 review: a refusal returns at once, unpadded) — CSRF **enabled** (a public form is not an excuse to disable it), and no `auth` middleware
- [X] T395 [P] [US9] Build `resources/views/support/public/form.blade.php` on the public layout — `x-track-input` for the University ID, `x-track-select` for the category from settings, a description textarea with a live character count against `support_description_max`, an optional contact input, the challenge widget, the hidden honeypot, and `x-track-button` primary with `loading`
- [X] T396 [P] [US9] Build `resources/views/support/public/submitted.blade.php` displaying the reference **prominently** with guidance to retain it, and a link to the status page — **the link is carried to T432a** (the status page is Phase 22; a link to a route that does not exist yet would 404)
- [X] T397 [P] [US9] Build `resources/views/support/public/refused.blade.php` rendering the generic failure through `x-track-alert` tone danger, with **identical** copy for all three verification failure modes
- [X] T398 [P] [US9] Add the US9 translation keys to `lang/ar/support.php` and `lang/en/support.php` — form labels, the character-count text, the guidance to retain the reference, and the two generic messages

**Checkpoint**: A student can raise a request and nothing about the roster leaks. This is the MVP — but see the note under US10 about why it should not ship alone.

**Implementation notes (2026-10-11).**

- **T388 is written and not run.** `tests/Visual/support-public-form.spec.ts` captures the form, the
  confirmation (its random reference masked) and the refusal in both locales at every project width.
  No baseline has been captured; `--update-snapshots` is the reviewer's call, as for T313, T328 and
  T343. `global-setup.ts` widens the two submission limits on the throwaway visual database, because
  every project posts from localhost.
- **The order of evaluation (T389, T391).**
  - The throttle counts the address and checks the identifier.
  - `SubmitSupportRequestRequest::validateResolved()` runs the guards before any field rule:
    challenge, honeypot, and the signed `_issued_at`. It then counts the identifier
    (`consumeIdentifier`), then the rules run.
  - The rules check the format first, with `bail` and its specific message, then the other fields.
  - The action then verifies the student.
  - The three guards fail with **one** message under one key, so a script is not told which guard
    caught it. The challenge is always consumed, even when the honeypot already failed.
- **New setting `support_min_elapsed_seconds` (3).** The contract names "the configured minimum age"
  of a form, and no key held it. That makes **ten** support settings. `_issued_at` is
  `<seconds>.<HMAC>` under its own key prefix (`FormStamp`), so a script cannot backdate it.
- **T397: the refusal page takes no input.** The three verification failures render a page built from
  nothing the visitor sent — no identifier, no category, no description, no form stamp. So it is
  byte-identical within one browser session. The trade: the student retypes their description.
  Re-rendering the form would put a fresh timestamp and their input in the body.
- **T378 found a leak, now fixed.** `throttle:public` added `X-RateLimit-Limit` and
  `X-RateLimit-Remaining` to **every** response it let through, not only to refusals. That is a live
  readout of the caller's counter, and the three failures differed by exactly that header.
  - The new `HideRateLimitState` middleware sits in the `public` group, around the throttle, and
    strips them.
  - It is placed on Laravel's middleware priority list just before `ThrottleRequests`. Without that,
    the priority sort moved the throttle outside it and the strip did nothing.
  - `PublicSurfaceTest` pins the new group: `web`, `HideRateLimitState`, `throttle:public`.
- **Routes (T394).** Only `POST /support` is padded and carries the submission limits, outside the
  padding (the three GETs were padded too until the Phase 20 review; see below). The resolved order is
  `… HideRateLimitState > ThrottleRequests:public > … SupportSubmissionThrottle > ConstantResponseTime`.
  A page view is not a submission: a student who has used up their submissions can still open the
  form.
- **T396: no status-page link yet.** The status page is US11 (Phase 22), and a link to a route that
  does not exist would 404. The confirmation offers "send another request". The link is task **T432a**
  in Phase 22, so it is planned there rather than remembered from this note.
- **T395.**
  - The challenge widget is `resources/js/components/support-form.js`. It imports the solver
    dynamically, so the solver loads on this page only, as its own same-origin chunk.
  - It re-solves a minute before the challenge expires.
  - Submit stays disabled until the challenge is solved, and stays disabled with a message if it
    cannot be (FR-129).
  - The honeypot is hidden from sight and from assistive technology (`aria-hidden`, `tabindex=-1`),
    not `.track-sr-only`, which is meant to be read aloud.
  - The button does not use the server-side `loading` spinner. The spinner exists only when it is
    rendered server-side, so toggling its class client-side would hide the label with nothing in its
    place. A disabled button plus the status line stand in for it.
- **T390 / T386.** The action returns `null` on any verification failure and writes nothing, so
  neither a row nor an audit count moves. The model's creation entry now carries `channel: public`.
  `support.request_created` moved from the deferred list to `ActionCoverageTest`'s performers.
  - Under the test runner the audit row's own `ip_address` column is `0.0.0.0`, because the Auditor
    treats the console as no request. The test therefore asserts the payload's `submitted_ip`.
- **The Enumeration placeholder is gone.** The suite now runs 12 real tests
  (`php artisan test --testsuite=Enumeration`).
- **Category labels** fall back to the other language, then to the machine value, when a setting row
  lacks one (`SupportRequestController::categoryOptions`).
- **The sweeps know the form.** The form is in the both-directions screen registry as a guest
  screen. The JSON challenge and the flash-only confirmation are excluded, each with its reason.
  `ValidationStatesTest` gives the support form a solved, aged submission, because the guards
  rightly refuse anything else before a field rule can answer.

**Review corrections (2026-10-11).** Ten findings, all fixed. No enumeration channel was found. Five
of the fixes were mutation-checked: reverting each one makes its test fail.

1. **Padding the page views parked workers.** The form, the challenge and the confirmation take no
   identifier, so padding hid nothing. At 60 requests a minute per address, about 125 addresses could
   fill a 50-worker pool with sleeping challenge fetches. Only `POST /support` is padded now. The
   contract, the runbook and T379 are updated, and T379 asserts the GETs never sleep.
2. **A harvested form stamp lived forever.** `FormStamp` now also refuses a stamp older than the
   session it was served in (`session.lifetime`). Past that the form's CSRF token is dead anyway, so
   no real submission is refused by it.
3. **CRLF counted twice.** Browsers submit textarea line breaks as CRLF, which the counter and
   `maxlength` count as one character. `prepareForValidation` normalises to LF, so a description the
   counter accepted is accepted.
4. **Typos spent a student's limit.** `consumeIdentifier` now runs after the field rules pass. It is
   still before verification, and runs whatever the roster says. A malformed identifier is not
   counted and makes no key. R-09 and T391 are updated.
5. **Constitution II is now proven for this action.** With the audit table unavailable, the
   submission throws and no request row remains.
6. **The challenge race is frozen in a test.** Both calls read the record and only the store's
   delete separates them; exactly one passes. A second test pins `CACHE_STORE=redis` in `.env.example`
   and the runbook, which now says why: the database store's delete does not report success to one
   caller only.
7. **Back from the confirmation, the form was stuck.** A `pageshow` listener resets the form and
   re-solves when the browser restores the page from its back-forward cache.
8. **T396's missing link is now task T432a** in Phase 22.
9. **`support_min_elapsed_seconds` was read with a bare cast**, so `-1`, `''` and `'three'` silently
   switched the guard off. Out of range (1–600) or not an integer, the 3-second default holds and the
   bad row is logged.
10. **The floor's `Retry-After` was a countdown.** It is now the whole window. The floor is per
    address and says nothing about an identifier, so it may differ from the submission limits' 3600.
    Those two do not differ from each other.

---

## Phase 21: User Story 10 - Staff See And Answer Requests (Priority: P2)

**Goal**: Staff work the queue: list, filter, open, respond, transition, reassign — every action recorded.

**Independent Test**: With three requests seeded, open the queue as Super Admin, respond to one, reject one with a reason, reassign the third, and confirm each action is recorded and the state changed.

**Note on shipping**: User Story 9 alone captures requests nobody answers. US9 + US10 is the smallest honest release.

### Tests for User Story 10

- [X] T399 [P] [US10] Write `tests/Feature/Support/QueueTest.php` asserting each row carries the student, their branch, the category, the state, the assignee, and the **waiting time**, ordered oldest-first by default
- [X] T400 [P] [US10] Write `tests/Feature/Support/QueueFiltersTest.php` asserting the `status`, `category`, `branch_id`, `assigned_to`, `date_from`, `date_to`, and `q` filters each narrow correctly and that totals count only matching rows
- [X] T401 [P] [US10] Write `tests/Feature/Support/OpenTransitionsToInProgressTest.php` asserting opening a `new` request moves it to `in_progress` and records who did so (FR-147)
- [X] T402 [P] [US10] Write `tests/Feature/Support/RespondTest.php` asserting a response is recorded against its author, becomes visible to the student, and that a response on a **closed** request is refused (FR-150, FR-151)
- [X] T403 [P] [US10] Write `tests/Feature/Support/StateMachineTest.php` asserting every permitted transition in [data-model.md](./data-model.md) succeeds and every other is refused — explicitly including `new` → `closed`, `new` → `resolved`, and anything out of `closed` (FR-148)
- [X] T404 [P] [US10] Write `tests/Feature/Support/RejectRequiresReasonTest.php` asserting rejection without a reason is refused and that the reason is visible to the student (FR-149)
- [X] T405 [P] [US10] Write `tests/Feature/Support/ReassignTest.php` asserting both previous and new assignee are recorded, that a coordinator is refused, and that assigning to a user without `support.view` is refused because it would silently strand the request (FR-152)
- [X] T406 [P] [US10] Write `tests/Feature/Support/ContactMarkedUnverifiedTest.php` asserting the student-typed contact is flagged unverified **in the payload**, not only in the view, so a client cannot render it as confirmed (FR-145)
- [X] T407 [P] [US10] Write `tests/Feature/Support/AuditCoverageTest.php` performing respond, transition, and reassign once each and asserting exactly one entry per action with the correct key from [contracts/README.md](./contracts/README.md)
- [X] T408 [P] [US10] Write `tests/Authorization/SupportEndpointsTest.php` with an allow case and a deny case for each of the **5** staff endpoints in [contracts/support-staff.md](./contracts/support-staff.md)
- [X] T409 [P] [US10] Write `tests/Feature/Support/NoMarkupInterpretedTest.php` asserting a description and a response containing markup or script are rendered as plain text to staff (FR-153)
- [ ] T410 [P] [US10] Write `tests/Visual/support-staff.spec.ts` screenshotting the queue, the request detail with its response thread, and the reject confirmation in both locales

### Implementation for User Story 10

- [X] T411 [US10] Implement `app/Modules/Support/Http/Controllers/SupportQueueController.php` with `GET /api/v1/support-requests` supporting all seven filters, defaulting to oldest-waiting-first, and returning the computed waiting time
- [X] T412 [US10] Implement `GET /api/v1/support-requests/{reference}` in the same controller, bound by **`reference` rather than `id`** so the URL carries no sequential identifier a coordinator could iterate, returning the full response thread, the state history, and the contact flagged unverified
- [X] T413 [US10] Implement the open-side-effect in that action: a `new` request becomes `in_progress` on first open, audited as `support.status_changed`. Stated in the contract because a read with a side effect is unusual
- [X] T414 [US10] Implement `app/Modules/Support/Actions/RespondToSupportRequest.php` extending `TransactionalAction`, refusing on a `closed` request with 409, and auditing `support.responded`
- [X] T415 [US10] Implement `app/Modules/Support/Http/Controllers/SupportResponseController.php` with `POST /api/v1/support-requests/{reference}/responses`
- [X] T416 [US10] Implement `app/Modules/Support/Actions/TransitionSupportRequest.php` enforcing the state machine, requiring `rejection_reason` for `rejected`, requiring `support.close` to reach `closed`, and auditing `support.status_changed` with old and new values
- [X] T417 [US10] Implement `app/Modules/Support/Http/Controllers/SupportStateController.php` with `PATCH /api/v1/support-requests/{reference}/status` and `PATCH /api/v1/support-requests/{reference}/assignee`, the latter behind `support.assign`
- [X] T418 [P] [US10] Build `resources/views/support/staff/queue.blade.php` — `x-track-page-header`, a filter `x-track-card`, and `x-track-data-table` with `x-track-status-badge` per state, the waiting time as a numeric column, and row click-through
- [X] T419 [P] [US10] Build `resources/views/support/staff/show.blade.php` — `x-track-breadcrumbs`, the request panel, the response thread, and the contact rendered with a **visible unverified marker**
- [X] T420 [P] [US10] Build `resources/views/support/staff/_respond.blade.php` and `_transition.blade.php` — the response composer, the state control, and an `x-track-dialog` danger confirmation for rejection that restates the impact and requires the reason (FR-167)
- [X] T421 [P] [US10] Add the support queue entry to `config/navigation.php` for `SUPER_ADMIN` and `BRANCH_COORDINATOR` with the `life-buoy` icon, and **not** for `INSTRUCTOR`
- [X] T422 [P] [US10] Add the US10 translation keys and the five `action_label` entries for the new audit keys to `lang/ar/` and `lang/en/`

**Checkpoint**: Requests arrive and get answered. US9 + US10 is the smallest honest release.

**Implementation notes (2026-10-11).**

- **T410 is written and not run.** `tests/Visual/support-staff.spec.ts` captures the queue, request
  FX00000002J with its reply, and the rejection dialog. No baseline yet; same position as T313, T328,
  T343 and T388. Waiting times are masked, because they are relative to now. `UiFixtureSeeder` now
  seeds three support requests with fixed, check-valid references (`FX00000001M` new,
  `FX00000002J` in progress with one reply, `FX00000003G` rejected).
- **State history (T412) is read from the audit log**, not a new table. The `support.request_created`
  and `support.status_changed` entries are already the append-only record of who moved a request and
  when. A history table would be a second record that could disagree with them.
- **The state machine lives in `TransitionSupportRequest::TRANSITIONS`.** The action, the screen's
  options and `StateMachineTest`'s 25 pairs all read it.
  - Refusals: a non-permitted pair is 409. `resolved` with no reply on record is 409. `closed`
    without `support.close` is 403. `rejected` without a reason is 422.
  - The row is locked for the read-then-write.
- **Responding to a `new` request opens it first** (`new` → `in_progress`, audited), as viewing it
  would: a request with a reply still labelled "new" misleads the next person in the queue. The
  response's audit entry carries its id, not its body.
- **The response body uses `support_description_max`**, one length setting for messages in this
  channel, rather than an eleventh setting nobody would set differently.
- **Reassignment (T405/T417) also refuses** an assignee who is deactivated, or a coordinator whose
  branches exclude the student. The contract names only "no `support.view`". The other two strand a
  request the same way, so they are refused the same way. The screen offers only people who could
  open the request.
- **An instructor gets 404, not 403, on a bound request.** The section constraint on
  `SupportRequest` hides every request from them before `can:` runs, which is the platform's
  404-not-403 rule. The queue, which binds nothing, answers 403. `SupportEndpointsTest` pins both.
- **Binding by reference (T412).** `SupportRequest::getRouteKeyName()` is `reference`, and
  `resolveRouteBinding` normalises the hyphenated or lower-case form, through the scoped query.
  Out of scope and nonexistent are the same 404, recorded as `access.denied` by the existing
  middleware. The numeric id is a 404.
- **`branch_id` for a coordinator** narrows only to one of their own branches. Any other value is
  ignored and their own scope comes back, so the filter cannot ask whether another branch has
  requests.
- **Sidebar (T421).** `support` sits between `imports` and `audit` for SUPER_ADMIN and
  BRANCH_COORDINATOR, with the `life-buoy` icon. `nav.php` already had an unused `support` label from
  the reference, which is now reworded. The icon sprite gains `life-buoy`, `send`, `message-square`
  and `user-check`. `send` was used by the Phase 20 form button and rendered nothing until then.
- **T422.** The five audit labels were added in Phase 19. The staff keys are under `support.staff.*`.
- **Wiring.**
  - `support.responded`, `support.status_changed` and `support.reassigned` moved from the deferred
    list to `ActionCoverageTest`'s performers. Only `support.anonymised` (Phase 25) is still deferred.
  - The queue and the in-progress request joined the both-directions screen sweep.
  - `NoAttachmentRouteTest` now selects the public form's routes exactly, so it no longer catches
    `/support-requests`.
- **Tests.** `tests/Feature/Support` has eleven new files (T399–T407, T409), and there is
  `tests/Authorization/SupportEndpointsTest.php` (T408). The queue is checked to issue the same
  number of queries for 3 rows as for 9.

**Review corrections (2026-10-12).** Ten findings, all fixed. No cross-branch access, stored XSS or
missing audit entry was found. Six fixes were mutation-checked: reverting each makes its test fail.

1. **Two people opening a `new` request at once: the second got a 409.**
   `TransitionSupportRequest::openIfNew` decides under the row lock, and a row already open is
   returned unchanged with no entry. `show()` and respond both use it. A test freezes the race.
2. **`StateMachineTest` could not catch a wrong table.** It read its expectations from
   `TRANSITIONS`. The 25 cells are now written out by hand from data-model.md, and a second test
   checks the constant against them cell by cell.
3. **Date filters were three hours off in Riyadh.** The picked day's first and last instants in
   `app.display_timezone` are now converted to UTC before comparing. There is a boundary test at
   01:30 Riyadh, which is 22:30 UTC the day before.
4. **Malformed or reversed dates.** Dates now need `date_format:Y-m-d`, and `date_to` must be
   `after_or_equal:date_from`. Otherwise the request gets a 422.
5. **A coordinator could list other branches' staff in the assignee filter.** The options are now
   themselves plus staff sharing one of their branches. The Super Admin still sees everyone.
6. **The reassignment control ran an EXISTS query per staff member.**
   `SupportRequest::studentBranchIds()` (the scope's own three conditions) is asked once, and
   candidates' branches are eager-loaded. `canOpen` uses the same method. A test checks the show
   page's query count does not grow with the staff count.
7. **`q` ran a leading-wildcard LIKE as a correlated subquery, twice per page.** It now tries a
   reference, then a University ID, and only otherwise a name. The name match runs once against
   students, capped at 500 ids.
8. **Finished requests "waited" forever.** `waiting_seconds` and `waiting_label` are null once a
   request is resolved, rejected or closed. The screen shows "—".
9. **A reason given with a non-rejection transition was dropped.** It is now in the
   `support.status_changed` entry (not a column), and the history shows it.
10. **The thread and the history were unbounded.** Each now shows the latest 50, with
    `responses_total` and `history_total`, and the screen says when it shows fewer than exist.

- **A flaky padding test.** The faked-clock padding tests now use a 60-second floor. On a loaded
  machine an error page's first render took longer than 5 seconds, so no padding was due and the
  test failed for the wrong reason. The clock is faked, so a long floor costs nothing.
- **contracts/support-staff.md** is updated for each behaviour above. T408's link label read
  `contracts/staff.md`; corrected to `contracts/support-staff.md`, the authoritative file.

---

## Phase 22: User Story 11 - A Student Finds Out What Happened (Priority: P3)

**Goal**: A student retrieves their request's state and any response using the reference number and their University ID, and sees nothing else.

**Independent Test**: With one responded-to request, retrieve it with the correct pair; then with a correct reference and the wrong ID, and with a reference that does not exist. Both failures must be identical.

### Tests for User Story 11

- [X] T423 [P] [US11] Write `tests/Feature/Support/StatusRetrievalTest.php` asserting a correct reference plus matching University ID returns the request, its state, and every response
- [X] T424 [P] [US11] Write `tests/Feature/Support/Enumeration/RetrievalFailuresIdenticalTest.php` asserting **byte-identical** responses for a non-existent reference and for a valid reference with a mismatched University ID (FR-135, SC-029)
- [X] T425 [P] [US11] Write `tests/Feature/Support/StatusExposesNothingElseTest.php` asserting the response carries **no** assignee, no internal note, no originating IP, no echoed identifier, and nothing belonging to another student (FR-134)
- [X] T426 [P] [US11] Write `tests/Feature/Support/Throttle/StatusThrottleTest.php` asserting retrieval is limited per IP at `support_status_throttle_ip`, tighter than submission, because an attacker holding a stolen reference would otherwise brute-force the identifier against it (FR-131)
- [X] T427 [P] [US11] Write `tests/Feature/Support/StatusNoResponseYetTest.php` asserting a request with no response shows its state with an explanation that it is still being handled (FR-136)
- [ ] T428 [P] [US11] Write `tests/Visual/support-status.spec.ts` screenshotting the status form, a result with responses, a result with none, and the generic failure in both locales

### Implementation for User Story 11

- [X] T432a [P] [US11] Add the link to the status page to `resources/views/support/public/submitted.blade.php`, beside the reference — **carried over from T396** (Phase 20), which could not link to a route that did not yet exist
- [X] T429 [US11] Implement `app/Modules/Support/Http/Controllers/Public/SupportStatusController.php` with `POST /support/status` querying on **both** `reference` and the student's University ID in one query, so a mismatch is indistinguishable from a missing reference
- [X] T430 [US11] Register the route in `routes/public.php` with its own tighter throttle and the `ConstantResponseTime` middleware, and **no audit entry** — a read exposing only the caller's own request with no state change is not a sensitive action, and logging every poll would fill the audit log with entries carrying the identifier, becoming a disclosure surface itself
- [X] T431 [P] [US11] Build `resources/views/support/public/status-form.blade.php` and `status-result.blade.php` on the public layout, the result showing the reference, category, submitted date, `x-track-status-badge`, the rejection reason where present, and the response thread with dates
- [X] T432 [P] [US11] Add the US11 translation keys, including the still-being-handled message and the generic retrieval failure

**Checkpoint**: The loop closes — a student can find out what happened.

**Implementation notes (2026-10-12).**

- **T428 is written and not run.** `tests/Visual/support-status.spec.ts` covers the form, a result with
  a reply (`FX00000002J`, student 101), a result with none (`FX00000001M`, student 100) and the generic
  failure. There is no baseline yet, like T313, T328, T343, T388 and T410. `global-setup.ts` now also
  widens `support_status_throttle_ip` on the visual database.
- **Routes (T430).**
  - `GET /support/status` is the form: a page view, not padded, under the `public` floor only.
  - `POST /support/status` is the lookup: `SupportStatusThrottle` (its own per-address limit,
    `support_status_throttle_ip`, outside the padding), then `ConstantResponseTime`.
  - A refusal is 429 with the window's whole `Retry-After`, through the same `SupportLimitReached`
    as every public limit.
  - The lookup is never audited.
- **One query, one failure (T429).** The reference is normalised (any case, hyphens or none, Crockford
  confusables) and then matched together with the normalised University ID in one statement. A
  missing reference, a mismatched ID, an anonymised request (no student, so no ID can match, R-12) and
  a wrong check character all give the same 422. The page re-renders the form with one alert and
  echoes nothing the visitor typed, so it is byte-identical within a session; JSON is
  `{message}` exactly. Only an empty field is told so by name, which reveals nothing about any record.
- **What the student sees (FR-134).** The reference, category, date, state badge, rejection reason, and
  each reply's body and date. Not the assignee, not the reply's author, not the IP, not the contact,
  and not the ID they typed. With no reply yet, the page says the request is still being handled
  (FR-136). The result is `Cache-Control: no-store`.
- **T432a (from T396).** The confirmation page now offers "check its status" beside "send another".
- **Wiring.** The status form joined the both-directions screen sweep, and `NoAttachmentRouteTest`
  lists the two new routes. The form's route is `support.status.form`, not `support.status`:
  `CsrfCoverageTest` treats a GET route named `….status` as one that writes, and this one only
  reads. The lookup is `support.status.lookup`.
- **Tests.**
  - New files: `StatusRetrievalTest`, `Enumeration/RetrievalFailuresIdenticalTest` (four failure
    modes, HTML and JSON, padded found or not), `StatusExposesNothingElseTest`,
    `Throttle/StatusThrottleTest` and `StatusNoResponseYetTest`.
  - The Enumeration suite now covers submission and retrieval.

**Review corrections (2026-10-12).** Ten findings, all fixed. Seven fixes were mutation-checked:
reverting each makes its test fail.

1. **The lookup limit was looser than submission's** (10 vs 5), contrary to T426.
   - It is now 4 failed lookups an hour per address.
   - The test compares it with submission's limit instead of pinning one number.
   - data-model.md is updated.
2. **An address limit alone could be walked.**
   - A new per-reference counter (`support_status_throttle_reference`, 5 an hour) stops guessing
     against one stolen reference from any number of addresses.
   - It counts invented references the same as real ones, which a test asserts.
   - IPv6 is counted by its /64 on every public limit, the `public` floor included.
3. **Successful lookups used up the budget**, so a campus behind one NAT was locked out.
   - Both counters still count on arrival (Redis INCR, so a burst cannot slip past) and are refunded
     on a match, so only failures stay counted.
   - `TRUSTED_PROXIES` is already in `.env.example` and the runbook (Phase 19).
4. **The failure paths did different work.**
   - A malformed reference now runs the same statement as the others, looking up `''`, which matches
     nothing.
   - A test checks the lookup SQL is identical across all four failures.
   - `ConstantResponseTime` logs, at most once a minute, when real work outruns the floor, and the
     runbook says to raise the floor above p99 if it does.
5. **Padded lookups could take the whole worker pool.**
   - `config/support.php` `padding_max_concurrent` (env `SUPPORT_PADDING_MAX_CONCURRENT`, default
     40) caps sleeping padded responses.
   - Past it, a request gets an immediate generic 503 before any work, the same for every request.
   - The count lives in a per-minute cache bucket, so a killed worker cannot wedge it shut.
   - The runbook sizes the pool for the status lookup too.
6. **An empty field echoed the other one back.**
   - A validation failure now redirects without flashing input.
   - A test checks `getOldInput()` is empty and the ID is not on the page.
7. **The padding test only checked that a sleep happened.** It now checks that each failure's pause tops
   it up to the floor.
8. **Accessibility.**
   - The failure message sits under the reference as its field error (role=alert).
   - Both fields are `aria-invalid` and point at it, and focus lands on the reference.
   - **Platform-wide:** `x-track-field` rendered its hint and error with no `id`, so every input's
     `aria-describedby` pointed at nothing. They now carry `{id}-hint` / `{id}-error`.
9. **The message blamed the reference alone.** It now names both fields, in both languages, still as
   one message for every failure.
10. **`?lang=` handling was copied in two controllers.** It now lives in the `HonoursRequestedLocale`
    trait.
    - Accepted, not changed: switching language on the result page returns to the empty form. The
      result is never kept between requests, by design, so showing it again means typing the pair
      again.

---

## Phase 23: User Story 12 - A Coordinator Sees Only Their Own Branch (Priority: P4)

**Goal**: A coordinator's queue shows only requests from students enrolled in their branches; anything else behaves as nonexistent.

**Independent Test**: With requests from students in two branches and a coordinator assigned to one, confirm the queue, its totals, its filters, and any direct reference attempt all show only their own branch.

### Tests for User Story 12

- [X] T433 [P] [US12] Write `tests/Scope/SupportRequestScopeTest.php` asserting a coordinator's queue and totals contain only requests from students enrolled in their branches (FR-140)
- [X] T434 [P] [US12] Write `tests/Scope/SupportOutOfScopeReturns404Test.php` asserting a reference from another branch returns **404, never 403**, through the API route and the Blade route, and that the attempt writes `access.denied` (FR-143, FR-158)
- [X] T435 [P] [US12] Write `tests/Scope/SupportBranchFilterCannotWidenTest.php` asserting a coordinator passing another branch's `branch_id` receives **their own scope, not an error** — so the filter cannot be used to probe
- [X] T436 [P] [US12] Write `tests/Scope/SupportMultiBranchStudentTest.php` asserting a student with active enrollments in two branches has their request visible to coordinators of **both** (FR-141)
- [X] T437 [P] [US12] Write `tests/Scope/SupportInstructorHasNoAccessTest.php` asserting an instructor has no queue and every support address returns 404 (FR-142, SC-038)
- [X] T438 [P] [US12] Write `tests/Scope/SupportScopeFollowsEnrollmentTest.php` asserting that when a student's active enrollment moves to another branch, the new branch's coordinator gains access and the old one loses it

### Implementation for User Story 12

- [X] T439 [US12] Verify `BelongsToBranchScope` on `SupportRequest` resolves through `student.enrollments.section.branch` filtered to **active** enrollments, and correct the relation path until T433–T438 pass. No new scope type is introduced
- [X] T440 [US12] Configure route-model binding for `SupportRequest` by `reference` in `app/Providers/RouteServiceProvider.php` to resolve **through** the scoped query, so an out-of-scope reference raises `ModelNotFoundException` → 404 before any policy runs
- [X] T441 [P] [US12] Constrain the `branch_id` filter in `SupportQueueController` to the caller's own branches for a coordinator, returning their scope rather than an error
- [X] T442 [P] [US12] Add the Super Admin-only branch column and filter to the queue view, hidden for a coordinator whose queue is already single-scoped

**Checkpoint**: Handling is distributed and the data-protection boundary holds.

**Implementation notes (2026-10-12).**

- **Most of the boundary was already built**, and this phase proves it through the routes.
  - **T439, verified:** `SupportRequest::constrainToBranches` resolves through the student's
    enrollments, filtered to active enrollments, no registrar withdrawal, and an active section
    (Phase 19 and its review). No new scope type.
  - **T441** landed in Phase 21: a coordinator's `branch_id` narrows only within their own branches.
- **T440.** The binding is now registered in `RouteServiceProvider` beside the platform's other scoped
  bindings (`section`, `student`, `import`). It delegates to the model's normalising
  `resolveRouteBinding`, which runs through the scoped query, so an out-of-scope or missing reference
  is one `ModelNotFoundException` → 404 before any policy runs.
- **SC-038: the queue is a 404 for an instructor, not a 403.**
  - `SupportRequestPolicy::viewAny` returns `Response::denyAsNotFound()`, and the queue routes carry
    `can:viewAny,SupportRequest`.
  - Every bound address was already a 404 through the scope. Now the one unbound address agrees, and
    "an instructor has no route to any request" holds for typed addresses too.
  - `AuditDeniedAccess` records the refusal's real status (404) for a policy that denies as not found.
  - A coordinator refused assignment or closing still gets the contract's 403.
- **T442.** The branch column and filter show for the Super Admin and for a coordinator of more than one
  branch. They are hidden for a single-branch coordinator, whose queue is already one branch.
- **Tests (T433–T438), six files in `tests/Scope`, all through the real routes.**
  - Every filter is aimed at another branch's request: status, category, its assignee, its reference,
    its student's ID, its branch, and all of them at once.
  - An out-of-scope reference gives the same 404 bytes as a nonexistent one, recorded as
    `access.denied` with status 404.
  - Acting on another branch's request by reference is a 404 and changes nothing.
  - Opening another branch's `new` request does not move it.
  - A two-branch student is seen and answerable from both branches, and a withdrawn second enrollment
    does not widen it.
  - For an instructor, all seven support addresses are a 404, even for a student they teach, and
    there is no sidebar entry.
  - An enrollment that moves takes the request with it in both directions, immediately.
- Two mutation checks (the queue answering 403, the branch column always shown) each fail their test.

**Review corrections (2026-10-12).** Ten findings, all fixed. The boundary held for every bound address.
Seven fixes were mutation-checked: reverting each makes its test fail.

1. **The queue's 404 could be told apart from a real one.** `denyAsNotFound` became a plain
   `HttpException(404)`, with an English "Not Found" body and HTML for `/api` without Accept. It
   confirmed the queue exists.
   - `bootstrap/app.php` now maps a 404-status `AuthorizationException` to `NotFoundHttpException`.
   - A test compares the queue's 404 with a missing request's 404 for JSON, no-Accept and the screen:
     the same bytes and content type.
2. **The `branch_id` filter doubled the scope.** A single-branch coordinator naming their own branch
   now adds nothing, and the SQL is identical to the unfiltered queue (a test checks this). Otherwise
   the filter is one semi-join.
3. **The scope was a correlated EXISTS per row.** `constrainToBranches` is now one uncorrelated
   semi-join, `student_id IN (SupportRequest::holdersIn(branches))`.
   - The new index `sections (branch_id, status)` serves the active-sections lookup.
4. **Soft-deleted students were visible.** The `withoutGlobalScopes()` on `Student` dropped
   `SoftDeletingScope`. `holdersIn` now excludes deleted students, and a test pins it.
5. **"Active enrollment" was written three times, and they drifted.**
   `SectionEnrollment::scopeCurrentlyHeld` (active, no registrar withdrawal, active section) is now
   the one definition. The scope, `studentBranchIds` and the queue's branch column all use it. A test
   checks the column no longer names a branch that grants no visibility.
6. **T442 was recorded only in tasks.md.** contracts/support-staff.md and plan.md now record it:
   - the column and filter are shown to the Super Admin and to multi-branch coordinators;
   - they are hidden for a single-branch coordinator;
   - there are tests for all three.
7. **The name search could be crowded out.** It ran across all branches, capped at 500, before the
   scope. It now runs through the caller's own `Student` scope before the cap. The test creates 600
   same-named strangers with lower ids first.
8. **The screens were unthrottled.** Each refusal writes `access.denied`, so one account could flood
   a five-year log. The two Blade routes now carry `throttle:api`, the per-user limit the API already
   has, and a test checks they do.
9. **Missing tests.**
   - The queue's 404 bytes against a real 404.
   - Reassigning another branch's request is a 404 even for a holder of `support.assign`, and is
     recorded.
   - Closing another branch's request is a 404.
   - Every spelling of an out-of-scope reference gives the same 404 bytes.
10. **The binding was duplicated.** The explicit `Route::bind` is gone, and the model's
    `resolveRouteBinding` is the one binding. It normalises only the `reference` key. Another key,
    such as `{supportRequest:id}`, binds normally, still through the scope; a test covers it.

- **A flaky test, fixed.** A whole-response comparison carried a waiting time that ticked between the
  two requests, so the test now freezes the clock.

---

## Phase 24: User Story 13 - Everyone Works In Their Own Language (Priority: P5)

**Goal**: All 8 screens complete and accessible in Arabic (RTL) and English (LTR), including the public pages where the visitor has no stored preference.

**Independent Test**: Walk every screen in both languages and confirm complete translation, correct mirroring, a text label beside every status colour, and full keyboard operation.

### Tests for User Story 13

- [X] T443 [P] [US13] Write `tests/Feature/Support/Localization/PublicPagesBothLocalesTest.php` asserting the public pages render in both locales with correct `lang` and `dir`, and that the language switch works **while signed out** (FR-161)
- [X] T444 [P] [US13] Write `tests/Feature/Support/Localization/NoUntranslatedOutputTest.php` asserting no support screen renders a raw translation key or a fallback-language string
- [X] T445 [P] [US13] Write `tests/Visual/support-axe.spec.ts` running axe-core over all 8 screens in both locales, requiring **zero** WCAG 2.1 AA violations (SC-036)
- [X] T446 [P] [US13] Write `tests/Visual/support-rtl.spec.ts` asserting per screen that the layout, breadcrumb chevrons, button icon side, and toast corner all mirror correctly

### Implementation for User Story 13

- [X] T447 [US13] Sweep every view under `resources/views/support/` for hard-coded user-facing text until 001's existing `NoHardcodedStringsTest` and `LocalizationParityTest` both pass for the `support` namespace
- [X] T448 [US13] Complete keyboard operation and WCAG 2.1 AA across all 8 screens — focus order, the visible focus ring, ARIA labels on icon-only controls, the challenge widget's accessible labelling, and dialog focus trapping — until `support-axe.spec.ts` is clean
- [X] T449 [P] [US13] Apply 001's `Format` helper to every rendered date and number in the support channel so digits are 0-9 and dates read DD/MM/YYYY in both locales
- [X] T450 [P] [US13] Verify and correct the responsive behaviour of the public pages and the queue at desktop and phone width in both locales, with the queue table becoming stacked cards below 768px

**Checkpoint**: All five stories functional, faithful, and accessible in both languages.

**Implementation notes (2026-10-12).**

- **The eight screens:**
  - public: the form, the confirmation, the refusal, the status form, the status result, and the
    limit page;
  - staff: the queue and the request detail.
  - `tests/Feature/Support/Localization/SupportScreens.php` reaches each one the way a person does:
    a GET, a submission, a failed verification, a lookup, a spent limit, a staff sign-in.
- **T445, T446 and T448: the first Playwright run (2026-10-13).**
  - Both specs share `tests/Visual/support-screens.ts`, which reaches all eight screens and opens the
    rejection dialog for axe.
  - Result: axe found zero WCAG 2.1 AA violations on all eight screens and the dialog, in both locales
    at 1440 and 390. Mirroring passes on every screen in both locales at 1440. The visual baselines
    T313, T328, T343, T388, T410 and T428 remain open.
  - **Selector fix.** `form button[type="submit"]` matched three buttons, because the public
    layout's language switch is a form with two submit buttons. So the click switched the language
    instead of sending. The selectors are now scoped to `main form ...` here and in
    `support-public-form.spec.ts` (T388) and `support-status.spec.ts` (T428).
  - **RTL bug found by T446.** RTLCSS flipped `.track-code`'s `direction: ltr` to `rtl` in the
    Arabic build, overriding `dir="ltr"`. As a result every reference, University ID and IP address
    rendered right to left in Arabic, on every platform screen and not just support. Fixed with
    `/* rtl:ignore */` on the rule in `_components.scss`. Arabic screenshots showing a `.track-code`
    will differ from any baseline taken before this fix.
  - T448 needed no code changes beyond these two fixes. axe was clean on the first run.
- **T443.**
  - All eight screens carry the right `<html lang dir>` in both locales.
  - The language switch works signed out: `POST /locale`, then every public page is English.
  - `?lang=` is remembered for the visit, and anything but `ar`/`en` is ignored.
- **T444, checked on the rendered screens.**
  - No raw key (`support.` / `validation.` / `common.` / …) appears in the readable text.
  - No Arabic character appears on an English screen, apart from the switch's «عربي», which names the
    language in itself by design.
  - No English support string (12+ characters, placeholders removed) appears on an Arabic screen.
  - Two mutations each fail their test: an Arabic comma on an English page, and an Arabic key removed
    so the English one shows through.
- **T447 fixes.**
  - The branch lists were joined with a hard-coded `، ` on English pages too. They now use
    `support.staff.list_separator` (`، ` / `, `).
  - `NoHardcodedStringsTest` and `LocalizationParityTest` pass for the `support` namespace.
- **T449.**
  - Dates print through `Format::date` / `Format::dateTime` (DD/MM/YYYY, a Western-digit isolated
    run), and the "showing latest" counts through `Format::number`.
  - The waiting time is the platform's own `SupportRequestPresenter::duration`: two units, Arabic
    plural forms with «و», Western digits. Carbon's Arabic `diffForHumans` read «ساعة 30 دقيقة».
  - A test asserts no Arabic-Indic digit on any of the eight screens in either locale.
- **T450.**
  - The queue's data table is in stacked mode (`track-table--stack`, `data-label` per cell), so it
    becomes cards below 768px.
  - The public layout's column narrows at 575px (`_public.scss`), and the detail's two columns stack
    below 992px.
  - Visual confirmation at both widths is the open specs' job.

---

## Phase 25: Retention, Hardening & Cross-Cutting

**Purpose**: The retention obligation the constitution added, plus the guards that keep the public surface bounded

**Retention is a requirement, not polish** — constitution v1.1.0 mandates it and SC-037 gates on it.

- [X] T451 Implement `app/Modules/Support/Actions/AnonymiseExpiredRequests.php` clearing **exactly three columns** — `student_id`, `contact`, `submitted_ip` — stamping `anonymised_at`, and preserving the reference, category, status, state history, and **all** responses (FR-159)
- [X] T452 Schedule the anonymisation sweep daily as a queued job in `routes/console.php`, reading `support_retention_months` (default **24**), with the clock running from **creation**, not closure, so an abandoned request is not retained indefinitely
- [X] T453 [P] Write `tests/Feature/Support/Retention/AnonymiseTest.php` asserting the three columns are cleared, `anonymised_at` is stamped, and the category, state history, and responses **survive** (SC-037)
- [X] T454 [P] Write `tests/Feature/Support/Retention/AuditSurvivesAnonymisationTest.php` asserting the audit entries are **not** touched — they follow the 5-year audit retention, which is longer (FR-160)
- [X] T455 [P] Write `tests/Feature/Support/Retention/AnonymisedVisibilityTest.php` asserting an anonymised request becomes visible to the **Super Admin only** — it has no student, so no enrollments, so no branch — and that public retrieval by its old reference returns the **same generic failure** as a non-existent reference
- [X] T456 Write `tests/Architecture/PublicSurfaceBoundedTest.php` failing the build if any route is registered outside the authenticated stack other than the **five** declared in [contracts/public.md](./contracts/support-public.md). A sixth requires a constitution amendment, not a pull request
- [X] T457 [P] Write `tests/Architecture/NoRawOutputInSupportViewsTest.php` banning `{!! !!}` anywhere under `resources/views/support/`, so no description or response can ever be interpreted as markup (FR-153)
- [X] T458 [P] Write `tests/Architecture/NoStudentAuthColumnsTest.php` asserting `students` carries no credential, contact, or activation column — the central constraint of this feature, and the reason feature 001 needed no change
- [X] T459 [P] Add `support_requests` and `support_responses` retention and the anonymisation sweep to the deployment runbook at `docs/runbook-deploy.md`
- [X] T460 Run the security review of this feature's public surface against the OWASP Top 10 and record it in `docs/security-review-002.md`, giving particular attention to the enumeration channels and the accepted impersonation risk recorded in spec "Accepted Risks"
- [ ] T461 Execute the full gate from [quickstart.md](./quickstart.md): `pint --test`, `phpstan analyse`, `php artisan test`, `--testsuite=Architecture`, `--testsuite=Scope`, and `npx playwright test`
- [ ] T462 Walk all 11 quickstart.md validation scenarios manually and record the outcome against the 8 Definition-of-Done items
- [X] T463 Add the link to `/support` on the sign-in screen in `resources/views/auth/login.blade.php`, behind a `support_channel_enabled` setting defaulting to **false**, with its translation key in both locales — and write `tests/Feature/Support/SignInLinkTest.php` asserting the link is absent while the flag is false and present when true. **This is the only task in Phases 18–25 that edits a file built by Phases 1–17**, and it closes FR-114's requirement that the platform "MUST link to it from the sign-in screen", which the pre-merge analysis found uncovered

**Implementation notes (2026-10-13).**

- **T451, the sweep.** `AnonymiseExpiredRequests` handles one request per transaction, re-read under a
  lock, and writes one `support.anonymised` entry (actor null, `channel: system`).
  - That entry carries **no old values**. Copying the cleared student and IP into it would keep them
    for the audit log's five years.
  - A `support_retention_months` below 1 is refused rather than read as "keep nothing", which would
    anonymise requests a coordinator is answering now.
  - **Deviation: `submitted_ip` was NOT NULL.** R-12 and data-model.md say the sweep clears it. Migration
    `2026_10_13_000001` makes it nullable instead of storing `''`. Creation still always sets it. The
    detail screen shows a dash for a cleared address. data-model.md is updated.
- **T452.**
  - `AnonymiseExpiredRequestsJob` is queued, `ShouldBeUnique` for a day, with 3 tries.
  - It is scheduled in `routes/console.php` as `support:anonymise`, `daily()->onOneServer()`.
  - The clock runs from `created_at`.
- **T453–T455: 11 tests** (`tests/Feature/Support/Retention`), sharing `agedSupportRequest()` and
  `runRetentionSweep()` in `tests/Support/SupportStaff.php`. The history they check is real: a reply
  and a transition made through the API.
  - Mutation checks: four mutants each fail a test. They are a sweep leaving the contact, one leaving
    the IP, one using a shorter period, and one copying the IP into its audit entry.
  - `support.anonymised` moves from `actionsAwaitingTheirPhase()` to a performer in
    `ActionCoverageTest`, so all contract keys for support are now performed.
- **T456: six routes, five entries.** `GET /support/status` is the page `POST /support/status` is
  posted from, and contracts/support-public.md documents both under one entry.
  - The bound pins the `public` group by method and URI, maps each route to its contract heading, and
    has a probe that proves a seventh route is caught.
  - The contract intro and quickstart Scenario 21 now say this.
- **T457** scans `resources/views/support` for `{!!` and has a probe. **T458** pins `students` to its
  exact seven columns, refuses credential-like names, and refuses any `Schema::table('students')`
  migration.
- **T459.** The runbook has a new §5a: what each support table keeps and for how long, how the sweep
  runs, changing the period, running it by hand, and backups. §2.3 now names the sweep.
- **T460.** `docs/security-review-002.md`: all ten OWASP categories, eight enumeration channels, and
  the accepted impersonation risk. `composer audit` and `npm audit` are clean.
  - **F-03, fixed:** the confirmation page carried the reference with Laravel's default `no-cache`, so
    a shared lab browser could keep it on disk. It now sends `no-store`, as the status result does,
    and the test asserts it.
  - **Residual risks for decision:**
    - R-02: staff replies are readable by whoever raised the request, impersonator included. Needs
      staff guidance, or a notice by the composer.
    - R-03: for the audit log's five years, after the request itself is anonymised, the creation entry
      keeps the student's identity. `student_id` and the IP are in its payload, and the address and user
      agent are in the row's `ip_address` and `user_agent` columns. Redacting the payload alone would not
      be enough. For the data-protection officer; the options are in the review.
    - R-06 (added by the code review): the description, the student's own free text, is kept
      indefinitely, as FR-159 is written. For the owner.
- **T463.**
  - The `support_channel_enabled` setting defaults to `false`, and the twelfth support key is added
    to data-model.md.
  - The link sits under the no-registration note, with `auth.support_link` in both locales.
  - The full suite found that `Settings::get` throws on an unseeded key, which would have taken down
    the **sign-in page** on any deploy that skipped the seeder. The view now reads
    `Settings::enabled()`: true only for a stored `true`, and a missing key reads as off.
  - `SignInLinkTest` covers off, on in both locales, unseeded, truthy-but-not-`true` values, and
    settings that cannot be read at all.
  - **T463 is not the only edit to a Phase 1–17 file** (Phase 25 review, L-7). T463 itself touched
    `resources/views/auth/login.blade.php` and both `lang/*/auth.php`. The setting needed
    `app/Modules/Settings/Settings.php` (the constant and `enabled()`). The coverage list in
    `tests/Pest.php` changed, and the monitor needed `app/Console/Commands/Monitor.php`,
    `config/monitoring.php` and `tests/Feature/Shared/MonitoringTest.php`. Phase 24 changed
    `resources/css/_components.scss` (`.track-code`).
- **T461, the gate (2026-10-13). Open: the PHP half is green, and Playwright waits on the baselines.**
  - `pint --test`: pass. `phpstan analyse`: 0 errors.
  - `php artisan test`: 1,620 of 1,625 passed and 1 was skipped. The 4 failures all had one cause, the
    unseeded switch on the sign-in page (T463 above). After the fix, those four files and
    `SignInLinkTest` pass (72/72).
  - Testsuites: Enumeration 19/19, Architecture 52/52, Scope 77/77, Authorization 138/138. The support
    gates (Throttle, Retention, PublicSurfaceBounded) pass 31/31.
  - `npx playwright test` was stopped at 448 of 2,450. At about 11 tests a minute, the full run on this
    machine takes 5–6 hours. Its failures were of two kinds only:
    - **screenshots**, missing or different. These are the open baselines T313, T328, T343, T388, T410
      and T428, plus `programs.spec.ts`, which has **no** baselines yet (Amendment 1, not in that list
      before the Phase 25 review, L-8). Arabic screenshots showing an identifier also change because of the Phase 24
      `.track-code` fix;
    - **30-second timeouts** from `php artisan serve` under eight parallel projects.
  - `keyboard.spec.ts`, rerun alone, passes 8/8. `support-axe` and `support-rtl` passed in full earlier
    the same day.
  - This closes when the baselines are approved (`--update-snapshots` is the owner's call) and the
    suite is run on CI or a pooled server.
- **T462, the walkthrough of Scenarios 11–21 (2026-10-13). Open for the steps that need a person or
  staging.**
  - Walked by automated suite, plus a live HTTP check on a running server:
    - **11:** `SubmitRequestTest`, `NoAttachmentRouteTest`, `FormatFailureIsSpecificTest`.
    - **12:** the Enumeration suite. Live, d (missing reference) and e (real reference, another
      student's ID) gave byte-identical bodies, statuses and headers, both as JSON and in the browser
      form. The matching pair differed, and no `X-RateLimit-*` header was sent.
    - **13:** the Throttle suite. **14:** `ChallengeTest`, including the store-down case.
      **15:** `ReferenceGeneratorTest`.
    - **16:** `StateMachineTest`, `RespondTest`, `ReassignTest`, `ContactMarkedUnverifiedTest`,
      `AuditCoverageTest`.
    - **17:** `StatusRetrievalTest`, `StatusExposesNothingElseTest`, `StatusNoResponseYetTest`.
    - **18:** Scope and `SupportEndpointsTest`. **19:** the Retention suite.
    - **20:** the Localization tests, plus `support-axe` and `support-rtl` at 1440 and 390.
      **21:** `PublicSurfaceBoundedTest`.
  - Steps that still need a person or staging:
    - 11.2: submission under 2 minutes, timed by a person;
    - 13.5: staff response times under a sustained flood (SC-031), which needs the pooled server;
    - 14.6: Redis actually stopped;
    - 16.3: response under 3 minutes;
    - 20.4–20.5: a keyboard-only pass and the four width × language combinations, by eye.
  - Definition of Done:
    - Items 8–11 (enumeration, independent limits, fail-closed, retention) are **met** by the suites
      above.
    - Items 1–7 are 001's own and are unchanged by this phase. Item 5 (performance) is not re-run here.

**Code review fixes (2026-10-13).** The Phase 25 review found two High, five Medium and eight Low
issues. Every one that needed no owner decision is fixed:

- **H-1, the sign-in page could fail.** `Settings::enabled()` had no guard, so with the settings cache
  empty and the database unreachable, or the table not yet migrated, `/login` returned 500.
  `SetLocale` already falls back on purpose. `enabled()` now reads as off when settings cannot be
  read. Security review F-04; the test drops the table, and a mutant without the guard fails it.
- **H-2, one row stopped the sweep for good.** No row's failure was caught, and rows go in id order.
  Now each failure is `report()`ed and the sweep continues, then the run fails as a job, which alerts.
  The write is `saveQuietly()`, so a staff-facing model rule ("rejected needs a reason") cannot block a
  legal retention write. F-05. The tests use a SQLite trigger that refuses one row's audit insert,
  plus a rejected-without-reason row. Two mutants fail them.
- **M-1, a sweep that never ran was silent.** `track:monitor` raises `support_retention_overdue` when
  any request is `ALERT_RETENTION_GRACE_DAYS` (2) past its retention, and `support_retention_unreadable`
  when it cannot check. F-06. `MonitoringTest` now seeds settings, as a deployment does.
- **M-2.** R-03 is corrected here, in the security review and in the runbook: the audit row's own
  `ip_address` and `user_agent` columns hold the student's too. The review lists options (a)–(c).
- **M-3.** The runbook (§6a, "Changing a support setting") gives one command that edits a setting
  **and** clears the forever-cached `settings.all`. It was run against the visual database: on, then
  off.
- **M-4.** The `SupportRequest` docblock claimed the description is cleared. It is kept, and that is
  now security review R-06, for the owner.
- **M-5, the sweep scanned all history.** Migration `2026_10_13_000002` adds
  `(anonymised_at, created_at)`. SQLite's plan for both the sweep and the monitor's check is now a
  `SEARCH … USING COVERING INDEX` on it. The plain `created_at` index stays for the date filters, and
  a test pins the new index.
- **L-1.** The IP migration's `down()` writes `''` into cleared addresses before NOT NULL returns.
  Both migrations were rolled back and reapplied.
- **L-2.** The model's `creating` guard now requires a non-empty `submitted_ip`; the column no longer
  refuses a missing one. `FoundationModelsTest` has two new cases.
- **L-3.** `NoRawOutputInSupportViewsTest` also finds `echo`/`print`/`->toHtml()`, with Blade comments
  stripped and line numbers kept. Every `HtmlString` in a support view is now a reviewed allowlist
  (`staff/queue.blade.php`: 3). The probe proves all three idioms are caught.
- **L-4.** `NoStudentAuthColumnsTest` checks every table with a foreign key to `students` for credential
  columns, with a control that it reaches them.
- **L-5.** New tests cover the inclusive cutoff on a month end, that the scheduled event runs on one
  server and queues the job, and the job's uniqueness and tries.
- **L-6.** The runbook says the sweep runs at 00:00 **UTC** (03:00 Riyadh).
- **L-7, L-8.** Recorded above.

Gate after the fixes: `pint --test` pass, `phpstan analyse` 0 errors, full `php artisan test` **1,643 passed, 1 skipped, 0 failed** (1,644 total).

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

- [X] T464 [P] Re-vendor `design-system/` from `TRACK Design System_V2.zip`, keeping v1 at
  `design-system-v1/` as the visual-regression baseline, and re-apply the seven accessibility
  corrections from `design-system/ACCESSIBILITY-DELTA.md`. Then run
  `tests/Architecture/TokenParityTest.php` — it must pass **without** a baseline update, because v2's
  tokens are byte-identical to v1's; a failure means the re-vendoring went wrong, not that the design
  changed
- [X] T465 [P] Correct the `programs` nav icon from `graduation-cap` to `library` in
  `config/icons.php` and `config/navigation.php`, matching v2's `Shell.jsx`, and free
  `graduation-cap` for الطلبة as v2 reassigns it
- [X] T466 [P] Add the four new navigation translation keys — `nav_approvals`, `nav_myinitiatives`,
  `nav_studentsadmin`, `nav_support` — to `lang/ar/nav.php` and `lang/en/nav.php`, taking the Arabic
  strings verbatim from `design-system/ui_kits/web-app/i18n.js`. The `approvals` and `myinitiatives`
  keys are seeded now but their nav entries stay **absent** until C-19 and C-22 are decided
- [X] T467 [P] Copy `design-system/scratch/roster_sample.xlsx` and
  `design-system/uploads/filedownloadservlet(21)-4d54b7af.xlsx` into `tests/Fixtures/rosters/` and
  extend `tests/Feature/Imports/RegistrarRealFileTest.php` (T325) to run the registrar mode against
  **all three** real exports, asserting each resolves its branch, course, and section from its own
  metadata block. Three real files with differing column shapes is materially better evidence than one
- [X] T468 Extend `resources/views/students/index.blade.php` and `show.blade.php` to match v2's
  `StudentsAdminScreen` and `StudentDialog` — the richer staff-side students view — reusing the existing
  `x-track-data-table`, adding the program column and the degree and activity columns from Amendment 1,
  and **reconciling its `StudentImportDialog` against the five import modes already specified** rather
  than adding a sixth. If it maps to an existing mode, wire it to that mode; if it does not, stop and
  raise it as a conflict rather than adding a mode
- [X] T469 [P] Update `tests/Visual/us4.spec.ts` baselines for the students screens changed by T468, and
  confirm every other baseline still matches — v2 changed no token, so any other diff is a defect in our
  implementation, not a design change

**Checkpoint**: v2 is vendored, the icon and nav keys are corrected, three real registrar files are under
test, and the students-admin screen matches the approved design — with nothing blocked touched.

**Implementation notes (2026-10-13).** T464–T468 are done, two of them with deviations. T469 is **open**
for the owner.

- **T464.**
  - v1 moved to `design-system-v1/`, and v2 was extracted into `design-system/` with a zip-slip check:
    180 files, and the zip is exactly the 11,594,160 bytes recorded.
  - Compared by SHA-256, `tokens/` (5 files), `components/` (86), `assets/` (16) and `guidelines/` (21)
    are **byte-identical** to v1. `TokenParityTest` passes with no baseline update.
  - v2 adds 6 files and changes 10, all screen-level. `design-system/VENDORED.md` records both lists.
    v1's `VENDORED.md` now says it has been superseded.
  - "Re-apply the seven corrections" needed no write. They are nine since the T061 amendment, they
    live in `specs/…/ACCESSIBILITY-DELTA.md` and `resources/css/`, and never in `design-system/`. The
    parity test reads them and v2's tokens together.
  - Vite, PHPStan, Pint and `.gitattributes` already excluded `design-system-v1/`.
- **T465.**
  - `config/icons.php` was already right. `config/navigation.php` was not: it now has `programs` →
    `library` and `students` → `graduation-cap`.
  - `ProgramEndpointsTest` pinned the v1 icons, so it now asserts v2's and adds a rule: no two sidebar
    entries share an icon unless they lead to the same route.
  - **Found on the way:** the committed icon sprite (`public/icons/lucide.svg`) lacked `life-buoy`, the
    support entry's icon since Phase 21. It was in `scripts/build-icons.mjs` but the sprite had not been
    rebuilt, so the support link drew an empty icon. The sprite is now rebuilt (85 symbols).
  - New `tests/Architecture/NavigationIconsInSpriteTest.php` checks every sidebar and concept icon
    against the built sprite. Run against the stale sprite, it fails and names `life-buoy`.
- **T466.**
  - `approvals`, `myinitiatives` and `studentsadmin` were added to both `lang/*/nav.php`, keyed by entry
    id as this platform's nav keys are (v2's `nav_` prefix dropped).
  - `support` already existed, and its Arabic is now v2's verbatim «طلبات الدعم الفني».
  - A test pins the keys in both locales, and pins that `approvals` and `myinitiatives` have **no** nav
    entry for any role.
- **T467: deviation, because the premise did not hold.** v2's "two more real exports" are the
  original export under new names. By SHA-1, `scratch/roster_sample.xlsx`,
  `uploads/filedownloadservlet(21)-4d54b7af.xlsx`, `scratch/roster.xlsx` and the original are one file
  (22 students, section 2298).
  - Copying them into `tests/Fixtures/rosters/` would run one file four times and call it four.
  - `RegistrarRealFileTest` instead collects every `.xlsx` in the vendored design system **by content
    hash**. Each distinct export must load end to end, and a pin records that there is one today, so a
    genuinely different export in a later vendoring fails the pin until it is given a world. Nothing
    was copied.
  - The C-23 backlog's "three real exports currently in hand" is corrected below. Getting genuinely
    different exports is a question for the registration unit.
- **T468: done as the task instructs, building what can be built and raising the rest.**
  - **Built** (`students/index`): the programme, degree, activity and sections-this-term columns.
    Programme, degree and activity are the student's **sections'** (Amendment 1), listed distinct.
  - **Built:** an open-requests count, shown only to a caller with `support.view`, never to an
    instructor (FR-142). The figure uses `--text-warning`, not v2's `amber-600`, which A11Y-04 corrects.
  - **Built:** search by name or University ID prefix; branch, section and programme filters that
    narrow inside the caller's scope (a coordinator naming another branch gets nothing); and a result
    count. The branch filter appears only when more than one branch is visible. For a college-wide
    caller, the section list waits for a branch.
  - **Built** (`students/show`): the enrollments table gains programme, degree and activity.
  - The page costs a fixed number of queries. A mutant without the eager load fails the test.
    `StudentsAdminListTest` has 10 tests, and axe on both screens is clean in both locales at 1440 and
    390.
  - **Not built**, by C-19 (retired) and C-23 (blocked):
    - the email, phone and password-state columns;
    - reset password and deactivate;
    - `StudentDialog` (New and Edit, which require an email and phone).
  - **Raised: C-24** in ui-conflicts.md. `StudentImportDialog` takes a flat student list with email,
    phone and a per-student programme. It fits none of the five modes: without a section the students
    would be visible to nobody (FR-043), and the programme belongs to sections. Options A–C are there;
    A, drop it, is recommended. No sixth mode was added.
  - The initiatives column and filter wait for the initiatives feature.
  - The ui-conflicts "compatible in v2" table claimed full adoption. It now says what was adopted.
- **T469: done 2026-10-13, approved by the owner.**
  - Exactly two `us4.spec.ts` tests were run with `--update-snapshots` ("the students list" and "a
    withdrawn enrollment keeps its row") on all eight screenshot projects: 18 passed and 6 skipped.
  - Exactly **18** baseline files changed, all `*-students.png` and `ar-student-detail.png`, and no
    other baseline.
  - Checked by eye: Arabic and English at 1440 show the new columns, the filter bar and count, the
    warning-coloured open request, and the corrected sidebar icons (T465). At 390 the list becomes
    stacked cards. The detail page shows programme, degree and activity, with the date masked by the
    test.
  - "Every other baseline still matches" is the full Playwright run, which T461 still holds open.
  - **Found while doing it:** Playwright writes a *missing* baseline automatically, even without
    `--update-snapshots`. The full run stopped at 448 in Phase 25 therefore left **28 unapproved baseline
    files**, untracked, written at about 09:10: `programs.spec.ts` (10), `us8.spec.ts` (4),
    `us5-registrar.spec.ts` (5), `support-public-form.spec.ts` (3), `support-staff.spec.ts` (3) and
    `support-status.spec.ts` (2), for ar-1280 and ar-1440 only. They are not approved. They are left for
    the owner to approve or delete.

**Code review fixes (2026-10-14).** The Phase 26 review found ten issues, and all ten are fixed:

1. **Real student data, copied.** All four roster names, in both folders, are one git object
   (`367236b9`), present since the initial commit, so Phase 26 adds names, not data. The repository
   answers 404 to an anonymous request, so it is private. The registrar pin now records the content
   hash and the count, not the four paths, so duplicates can be removed without editing tests.
   `design-system/VENDORED.md` records all of this. **Owner decision:** whether real student data stays
   in the repository at all.
2. **"Open" requests left out Resolved.** The count now uses `SupportRequestStatus::isOpen()`, so
   Resolved counts, as it does in the queue. Tested with resolved, closed and rejected; a mutant with
   the old list fails it.
3. **LIKE wildcards.** Backslash escaping without an ESCAPE clause does nothing on SQLite, and ESCAPE
   would be raw SQL, which PHPStan refuses under the constitution's security floor. So instead:
   - `university_id` takes **digits only**, in either script, and so cannot carry a wildcard;
   - a name search drops `%`, `_` and `\`, which no name contains;
   - a search of wildcards alone matches **nobody**.

   Tests cover each of these, and a mutant without the stripping fails 3 of 4.
4. **Wasted work.** The unused `withCount('enrollments')` is gone. The eager loads and the open-requests
   count run for the screen only. A test checks that the API's rows touch neither `support_requests` nor
   `programs`, and carry exactly the four record fields.
5. **A full scan for every number typed.** An all-digit search, Arabic-Indic digits included, now uses
   only the indexed `university_id` prefix. Names are searched only when the input is not a number.
6. **The programme filter offered the whole catalogue.** It now lists the programmes of the caller's
   current sections. A test checks that a coordinator is not offered, and does not reach, another
   branch's programme.
7. **Hidden truncation, and a stale section.** The section list fetches one more than its cap of 500 and
   tells the user, in the select's hint, when it was cut. A `section_id` outside the chosen branch is
   dropped, so the branch decides.
8. **Tests.** `StudentsAdminListTest` grows to 28 tests. New: another branch's section or programme,
   Resolved counted as open, wildcards, wildcards around a name, digit search in both scripts, the
   legacy filter's validation and prefix, a student in sections of two programmes listing each once, a
   withdrawn enrollment, a stale section, and the API's lean rows.
9. **Sprite coverage.** `NavigationIconsInSpriteTest` now also requires the built sprite to be
   **exactly** the list in `scripts/build-icons.mjs`; against the stale sprite it fails with "run
   `npm run build:icons`". It also checks every icon named literally in a view or in `app/` (49 names),
   with a control that the scan reaches them.
10. **Non-roster spreadsheets.** `vendoredRegistrarExports()` keeps only files with the registrar
    signature (a block starting «المقر», then a header with «رقم الطالب»). A new test proves the
    signature tells a roster from a block-less sheet and from a non-spreadsheet.

Gate after the fixes: `pint --test` pass, `phpstan analyse` 0 errors, full `php artisan test` 1,678 of 1,680 passed, 1 skipped, and 1 failed. The failure was `ResponseTimeFloorTest` (249.36 ms against a 250 ms floor). It is not Phase 26 code but a real gap: Windows `usleep` can wake early, and it failed one run in five. `ConstantResponseTime` now tops up once when a real sleep came up short; a faked Sleep is untouched. After the fix it passes 8 runs of 8, and the padding, enumeration and throttle suites pass 91/91.

Gate: `pint --test` pass, `phpstan analyse` 0 errors, full `php artisan test` 1,657 of 1,659 passed, 1 skipped, and 1 failed. The failure was `NoHardcodedBranchesTest`, set off by a campus name used as an example in a code comment. The comment was reworded, and that test plus `tests/Feature/Students` pass (25/25).

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

- [X] T470 Add to the `sections` migration: `status` **enum(`pending`,`returned`,`rejected`,`active`) default `active`** so pre-amendment and Super-Admin-created sections need no backfill · `created_by` FK → `users.id` · `requires_approval` boolean derived on creation from the creator's role · `reviewed_by` FK → `users.id` nullable · `reviewed_at` timestamp nullable · `review_reason` varchar(500) nullable (FR-173)
- [X] T471 Build the section-key index in its final form. **Amended 2026-10-06**: the original wording said to drop T023's four-column `UNIQUE (term_id, branch_id, course_id, section_number)` and replace it. Nothing had shipped when both ran, so creating that index only to drop it in the same deployment was pure waste; the sections migration carries the final shape directly. Add the **stored generated column** `approval_key varchar(20)` = `CASE WHEN status='rejected' THEN NULL ELSE section_number END`, and add `UNIQUE (term_id, branch_id, course_id, approval_key)`. This is what makes FR-176 work: two non-rejected sections collide, a rejected one releases its number, and several rejected ones coexist because `NULL`s do not collide. Adding `status` or `deleted_at` to the index instead **does not work** — see [data-model.md](./data-model.md) Group 8 for why each alternative fails
- [X] T472 [P] Create the `section_approval_history` migration with `section_id` cascade, `from_status` enum nullable, `to_status` enum, `actor_id`, `reason` varchar(500) nullable, `created_at` and **no `updated_at`**, plus index `(section_id, created_at)`
- [X] T473 [P] Extend `PermissionSeeder` to grant the existing `section.create` to `BRANCH_COORDINATOR` and `INSTRUCTOR` as well as `SUPER_ADMIN`, and add `section.approve` granted to `SUPER_ADMIN` and `BRANCH_COORDINATOR` — **47 permissions total** (`section.create` already existed; see T045). Do **not** add a `section.request` permission: creation and requesting are the same act, and the distinction is carried by `requires_approval`

### Tests — runs with Phase 7

- [X] T474 [P] [US4] Write `tests/Feature/Sections/CreatorDecidesApprovalTest.php` asserting a Super Admin's section is `active` immediately while an instructor's and a coordinator's are `pending`, and that the initial status is derived from the **authenticated user's role** and cannot be set from a request field (FR-171, FR-172, SC-039)
- [X] T475 [P] [US4] Write `tests/Feature/Sections/NoSelfApprovalTest.php` asserting a creator cannot decide their own section, and a coordinator cannot decide a section created by a coordinator of the **same branch** — both refused with **403**, not 404, because the caller can see it and merely may not decide it (FR-178, SC-040)
- [X] T476 [P] [US4] Write `tests/Feature/Sections/ApprovalScopeTest.php` asserting a coordinator's queue shows only **instructors'** pending sections from their own branches, and a Super Admin's shows every pending section (FR-177, FR-184)
- [X] T477 [P] [US4] Write `tests/Feature/Sections/RejectionReleasesNumberTest.php` asserting that after a rejection the same term, branch, course, and number can be used again, that two non-rejected sections with that combination are still refused, and that several rejected ones coexist (FR-176, SC-041)
- [X] T478 [P] [US4] Write `tests/Feature/Sections/ApprovalStateMachineTest.php` asserting every permitted transition in [data-model.md](./data-model.md) Group 8 succeeds and every other is refused, explicitly including that **`rejected` is terminal** and that no decision can be made on an `active` section (FR-180)
- [X] T479 [P] [US4] Write `tests/Feature/Sections/ReturnAndResubmitTest.php` asserting a return requires a reason of at least 10 characters, makes the section editable by its creator, shows the reason to them, and that resubmission returns it to `pending` (FR-179, FR-183)
- [X] T480 [P] [US4] Write `tests/Feature/Sections/PendingNotTargetableTest.php` asserting a pending, returned, or rejected section never appears in an initiative targeting preview, never receives an assignment, and never enters a completion denominator (FR-174). **This is the most consequential test in the phase** — a pending section leaking into targeting would corrupt completion figures
- [X] T481 [P] [US4] Write `tests/Feature/Sections/PendingHoldsEnrollmentsTest.php` asserting a creator can attach a roster at creation and that those enrollments survive both approval and rejection (FR-175)
- [X] T482 [P] [US4] Write `tests/Feature/Sections/RejectedExcludedFromListsTest.php` asserting rejected sections are absent from default lists but present when `status=rejected` is requested, and that pending and returned ones are present and badged (FR-181, FR-183)
- [X] T483 [P] [US4] Write `tests/Authorization/SectionApprovalEndpointsTest.php` with an allow case and a deny case for the four new routes, plus an out-of-scope 404 test and an instructor-denied test for the queue and the decision route
- [X] T484 [P] [US4] Write `tests/Feature/Sections/ApprovalAuditTest.php` asserting exactly one entry per decision from the five new action keys, each carrying the previous and new status and the reason where given (FR-182)
- [X] T485 [P] [US4] Write `tests/Feature/Sections/ApprovalVocabularyTest.php` asserting no interface string and no audit label renders a bare "approved" where both section approval and assignment review could be meant, and that the two vocabularies do not share a state name in the same view (FR-185, SC-043)
- [ ] T486 [P] [US4] Write `tests/Visual/section-approval.spec.ts` screenshotting the approval queue, the decision dialog, the return-reason dialog, and a badged pending section in the list, in both locales at all four widths, against v2's `ApprovalsScreen` and `ApprovalDialog` references

### Implementation — runs with Phase 7

- [X] T487 [US4] Extend `app/Models/Section.php` with the `status` cast, the `creator`, `reviewer`, and `approvalHistory` relations, a `scopeActive()`, and a `scopeAwaitingDecisionBy(User $u)` implementing the asymmetric rule in FR-177 and FR-178
- [X] T488 [US4] Extend `app/Modules/Sections/Actions/CreateSection.php` to derive `status` and `requires_approval` **from the authenticated user's role**, never from the request, write the first `section_approval_history` row, and audit `section.created` plus `section.submitted_for_approval` when pending
- [X] T489 [US4] Implement `app/Modules/Sections/Actions/DecideSectionApproval.php` extending `TransactionalAction` — approve, return, reject — enforcing the state machine, requiring a reason for return and reject, appending history, and auditing the matching action key
- [X] T490 [US4] Implement `app/Modules/Sections/Actions/ResubmitSection.php`, permitted only to the creator and only from `returned`, auditing `section.resubmitted`
- [X] T491 [US4] Extend `app/Modules/Sections/Policies/SectionPolicy.php` with the `approve` ability implementing FR-178: refuse when the actor is the creator, and refuse when the actor is a coordinator and the creator is a coordinator of the same branch. Both refusals are **403**
- [X] T492 [US4] Implement `GET /api/v1/sections/approvals` in `app/Modules/Sections/Http/Controllers/SectionApprovalController.php`, scoped by `scopeAwaitingDecisionBy`, ordered oldest-waiting-first, returning the creator with their role and the waiting time
- [X] T493 [US4] Implement `PATCH /api/v1/sections/{id}/approval` and `PATCH /api/v1/sections/{id}/resubmit` in the same controller, plus `GET /api/v1/sections/{id}/approval-history`
- [X] T494 [US4] Amend `POST /api/v1/sections` to accept an optional `roster` file and route it through the **existing** section import mode rather than a parallel path, and amend `GET /api/v1/sections` to exclude `rejected` by default while including pending and returned
- [X] T495 [US4] Apply the `active`-only constraint to initiative targeting as a **global scope on the targeting query**, not a filter at each call site, so a later endpoint cannot forget it (FR-174)
- [X] T496 [P] [US4] Build `resources/views/sections/approvals.blade.php` to v2's `ApprovalsScreen` reference — `x-track-data-table` with the creator, their role, the course, the branch, the enrollment count, the waiting time, and an `x-track-status-badge` per state
- [X] T497 [P] [US4] Build `resources/views/sections/_approval-dialog.blade.php` to v2's `ApprovalDialog` reference — the three decisions, with the reason textarea **required** for return and reject and an `x-track-dialog` danger variant for reject that restates that rejection is final
- [X] T498 [P] [US4] Add the status badge to the section list and detail views, and a return-reason `x-track-alert` on a returned section visible to its creator with a resubmit action (FR-183)
- [X] T499 [P] [US4] Add the `approvals` entry to `config/navigation.php` for `SUPER_ADMIN` and `BRANCH_COORDINATOR` with the `clipboard-check` icon — the entry T466 seeded and left hidden — and **not** for `INSTRUCTOR`
- [X] T500 [P] [US4] Add the section-approval translation keys to `lang/ar/sections.php` and `lang/en/sections.php`, taking the Arabic status vocabulary verbatim from v2: بانتظار الاعتماد · معادة للتعديل · مرفوضة · معتمدة — and the five new audit `action_label` entries

**Checkpoint**: instructors and coordinators can set up their own sections without queueing behind the central office, nothing enters an initiative until it is approved, and no one approves their own work.

**Implementation notes (2026-10-14).** T474–T485 and T487–T500 are done. **T486 is open for the owner**:
its spec is written and runs, and its baselines need approving.

- **A bug fixed on the way in: coordinators' sections were born active.** The existing create path read
  `section.approve`, which a coordinator holds, so FR-172's Super Admin approval was skipped.
  `CreateSection` (T488) derives the status from the role, through `scope.bypass_all`, never from a
  permission or a request field. `CreatorDecidesApprovalTest` pins all three roles, and that a posted
  `status`, `requires_approval` or `created_by` is ignored.
- **Model (T487).**
  - `SectionStatus` holds the state machine: `decisions()`, `canBecome()`, `canResubmit()`, the decision
    → state mapping, the audit key, and the label key.
  - The column stays a string, because a lot of earlier code compares it as one.
  - New: `scopeActive()`, `scopeAwaitingDecisionBy(User)` (FR-177 and FR-178's asymmetry, through the creator's
    `section.approve`), and
    `approvalHistory()`.
  - A `saving` guard refuses `returned` or `rejected` without a reason.
  - `SectionApprovalHistory` is append-only: an update or a delete throws.
  - `section.created` now carries `creator_role` and `created_by`.
- **Actions (T488–T490).**
  - `DecideSectionApproval` and `ResubmitSection` re-read the section under a lock, save quietly, and
    write one named entry each, so no generic `section.updated` appears beside it (T484).
  - A resubmission records only the fields that actually changed.
  - The roster (T494) goes through the **existing** `UploadImport` in section mode, inside the create
    transaction, so a refused file refuses the section too. The response carries `import_job_id`.
- **Policy (T491).** `approve(User, ?Section)` refuses with **403** the creator, and a coordinator
  whenever the creator could approve sections themselves (a fellow coordinator, or one who also teaches).
  Those go to the Super Admin. `resubmit` admits the creator only.
  - "Could approve" is the creator's own `section.approve`, **never a role name**. The first version
    tested the `INSTRUCTOR` role, and `NewRoleNeedsNoCodeChangeTest` (FR-020) refused it. The permission
    rule also covers a mixed-role creator without a special case.
- **Routes (T492, T493).** The queue, the decision, the resubmission and the history, plus the queue
  screen at `/sections/approvals`. Each is named, in the `UserEndpointsTest` matrix, and has allow and
  deny pairs in `SectionApprovalEndpointsTest` (T483).
  - Binding runs before `can:`, so a caller who cannot see the section gets a **404**, not a 403. That is
    the platform's scope-before-policy rule, and it is asserted.
- **Lists (T494).** A rejected section is out of every default list and the export; `status=rejected`
  asks for it. An unknown status matches nothing. `created_by` filters.
- **Scope change, recorded in data-model.md "Scoping and visibility".** An instructor's accessible
  sections now include those they **created** (FR-183): otherwise one created without naming themselves
  instructor was invisible to them, return reason included.
  - The first version applied this to coordinators too. That switched SectionScope on for them, and a
    coordinator who had created one section saw only that section in their branch. `ApprovalScopeTest`
    found it, and the rule now applies only to callers the branch scope does not govern.
- **T480, T495: deviation, because initiative targeting is not built in this release.** There is no
  preview, assignment or denominator to filter.
  - `TargetableSection` is the sanctioned targeting query: a read-only `Section` whose **global scope**
    admits `active` only.
  - `PendingNotTargetableTest` proves that pending, returned and rejected sections never appear, that
    an approval makes a section appear, that writes are refused, and that no code in `app/`, `routes/` or
    views touches `initiative_sections` or `initiative_targets` any other way.
  - The preview, assignment and denominator assertions land with the initiatives feature.
- **Screens (T496–T498).**
  - `sections/approvals`, the queue, has a fixed number of queries per page (tested).
  - `_approval-dialog` holds approve-or-return with an optional reason, plus a separate reject danger
    confirmation that restates finality and requires the reason.
  - The section number is badged in the list for non-active states. Active rows are unchanged, so the
    existing list baselines hold where nothing is pending.
  - The detail page shows the badge, the return or rejection reason, and the decision and resubmit
    actions.
  - **Found on the way:** `x-track-page-header` never rendered its default slot, so the section detail's
    FR-026 "shared" badge had never appeared. Fixed, with `.track-page-header__badges`.
- **Nav (T499).** `approvals` uses `clipboard-check`, for SUPER_ADMIN and BRANCH_COORDINATOR, after
  `sections` as v2 has it, and never for INSTRUCTOR. The icon joined `build-icons.mjs` and the sprite was
  rebuilt (86 symbols). T466's test now pins `myinitiatives` alone as absent.
- **Vocabulary (T500).**
  - The Arabic states are v2's verbatim: بانتظار الاعتماد · معادة للتعديل · مرفوضة · معتمدة.
  - The English names the section every time ("Section approved", not v2's bare "Approved"), per
    FR-185.
  - There are five audit labels per locale.
  - `contracts/audit.md` gains the five keys, and `ActionCoverageTest` performs each through its
    endpoint: 48 keys.
- **T486: open.** `tests/Visual/section-approval.spec.ts` covers the queue, the decision dialog, the
  reject confirmation, the returned section with its reason, and the badged list, in both locales at
  every width.
  - `UiFixtureSeeder::seedApprovalSections` adds section 20 (pending) and 21 (returned), both instructor
    5's.
  - It was run at 1440 in both locales with `--update-snapshots=none`, so **no baseline was written**.
    All nine flows reach their screenshot step and stop only at the missing baselines.
  - The two new fixture sections also appear in the existing section lists, so those baselines change
    as well. Approving them is the owner's call.

Gate: `pint` pass, `phpstan analyse` 0 errors, full `php artisan test` **1,770 passed, 1 skipped, 0 failed** (1,771 total). Two earlier full runs found five problems, all fixed:
  - a role name in the policy (FR-020);
  - the new screen missing from the screen registry;
  - a spike table named in a comment;
  - an older schema test rejecting without a reason;
  - the `gender()` accessor, deleted by my own edit and restored from git.

  `ChallengeTest` failed once in one of those runs and passed 3 runs of 3 alone, so it is timing-flaky and unrelated.

**Code review fixes (2026-10-14).** The Phase 27 review found ten issues. All ten are fixed, each with a
test in `ApprovalReviewRegressionsTest` (15 tests):

1. **Creators kept reading sections they did not teach (student data).** `accessibleSectionIds()` gave
   an instructor every section they had created, permanently. Every scoped model shares that list, so a
   creator kept reading the roster, students and support requests of a colleague's approved section.
   The `created` set now holds **non-active** sections only: the creator's claim lasts while the section
   awaits a decision or was refused. A mutant without the limit fails the test.
2. **Roster at creation.**
   - An instructor who left `instructor_id` empty was refused. `SectionPolicy::importRoster` now admits
     the creator of a section still awaiting approval.
   - A coordinator holds **no import permission at all** (`import.view` only), so their roster is now
     refused with a 422 **before** the section is created, not by rolling it back.
   - **Owner decision:** whether coordinators should hold `import.section.run`, which FR-175 implies.
     It would take the permission set past 47.
3. **The reason guard was skipped.** `saveQuietly()` suppresses the model's `saving` guard. The decision
   action now refuses a return or reject with a reason under 10 characters itself, whoever calls it. A
   mutant without the check fails all 3 cases.
4. **Queue and policy disagreed on a deleted creator.** `Section::creator()` is now `withTrashed()`, so
   both see the same creator, and a deleted instructor's request stays decidable.
5. **Rejected sections were still counted.**
   - The home dashboard tile now excludes rejected sections, and its enrollment counts exclude their
     enrollments.
   - The students list's "current sections", behind its filters, are **active** only, matching
     `currentlyHeld()`.
6. **A transaction was held across the spreadsheet parse.** `UploadImport` has a `stage()` step
   (validate, store, header, hash) and an `executeStaged()` insert. `CreateSection` stages the roster
   **before** its transaction, so the new section's row is locked for two inserts only. A refused or
   abandoned roster leaves no stored file (tested). `execute()` is unchanged for every other caller.
7. **A returned section could not be corrected on screen.** `_resubmit-dialog` lets the creator correct
   the course, programme, number, degree and activity, prefilled, through the same form request.
   Options are built only for the creator of a returned section. Term and branch stay, because changing
   either is a new section.
8. **A raced duplicate number gave a 500.** `CreateSection` and `ResubmitSection` turn the index's
   unique violation into the same 422 on `section_number` that the form request gives.
9. **The targeting guard missed indirect references.** It now matches the bare table name anywhere,
   plus a concatenated `'initiative_'.`, and a probe proves raw SQL, concatenation and a variable are
   caught.
10. **An array status gave a 500.** `status[]=` and any non-string status match nothing, never a 500.

Gate after the fixes: `pint` pass, `phpstan analyse` 0 errors, full `php artisan test` **1,785 passed, 1 skipped, 0 failed** (1,786 total).

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
- [ ] T-C03 Extend the registrar import to carry the contact if the export gains the column. The export
  in hand has four columns and none is a contact. *Corrected 2026-10-13 (T467): it is **one** export,
  not three; v2's "further" exports are the same file under other names (by SHA-1)*


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
