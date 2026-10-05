---

description: "Task list for Foundation — Access Control & Academic Roster"
---

# Tasks: Foundation — Access Control & Academic Roster

**Input**: Design documents from `/specs/001-foundation-access-roster/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md)

**Tests**: Test tasks are **included and mandatory**. Constitution principle VIII requires an
allow-case and a deny-case authorization test for every endpoint and an out-of-scope 404 test for
every scoped model-bound route. These are not optional follow-ups — an endpoint merged without
them is incomplete.

**Organization**: Tasks are grouped by the seven user stories in spec.md. Ordering within and
across stories follows the PRD build order: authentication → RBAC and scoping → audit → academic
structure → import wizard.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: Which user story the task belongs to (US1–US7)
- Exact file paths are given in every task

## Path Conventions

Single Laravel application, domain modules under `app/Modules`, per
[plan.md](./plan.md) "Structure Decision". Shared Eloquent models live in `app/Models`.

**Note on two deliberate placements**, both required so that User Story 1 is independently
testable:

- The **audit write mechanism** (`Auditor`, `audit_logs`, the grant revocation) sits in Phase 2,
  because account creation in US1 must already be audited. User Story 3 then delivers the audit
  *log viewing*, filtering, export, per-entity activity, and the immutability and transaction
  tests.
- **All migrations** sit in Phase 2 in the dependency order given in data-model.md, because a
  partial schema cannot be migrated cleanly and later stories share tables. Models and endpoints
  stay in their own stories.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization, tooling, and the CI pipeline

- [ ] T001 Initialize the Laravel application at the repository root with `composer create-project laravel/laravel .`, pinning the latest stable major supported on PHP 8.3, and record the pinned version and PHP version in `README.md` per [research.md](./research.md) R-07.1
- [ ] T002 Configure `.env.example` with `DB_*`, `REDIS_*`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`, `SESSION_LIFETIME=60`, `FILESYSTEM_DISK=private`, `APP_LOCALE=ar`, and add the `private` disk (outside the public root) to `config/filesystems.php`
- [ ] T003 Set Argon2id as the hashing driver in `config/hashing.php`, and set the database connection in `config/database.php` to `utf8mb4` / `utf8mb4_unicode_ci` with the `InnoDB` engine
- [ ] T004 [P] Install and configure Pint in `pint.json` using the Laravel preset
- [ ] T005 [P] Install Larastan and configure `phpstan.neon` at the highest level that passes on a bare skeleton, with `forceDelete` and raw-query helpers (`DB::raw`, `whereRaw`, `selectRaw`) added to the banned-call list per constitution principles V and the security floor
- [ ] T006 [P] Install Pest and register the `Unit`, `Feature`, `Architecture`, `Scope`, `Authorization`, and `Performance` testsuites in `phpunit.xml`, with `Performance` excluded from the default run unless `PERF_SUITE=1`
- [ ] T007 [P] Install `laravel/sanctum`, `spatie/laravel-permission`, `maatwebsite/excel`, and `openspout/openspout` and publish their configuration
- [ ] T008 Create the module directory skeleton under `app/Modules/` for `Authentication`, `UserManagement`, `Authorization`, `Audit`, `AcademicTerms`, `AcademicStructure`, `Sections`, `Students`, `Imports`, `Settings`, and `Shared`, each with `Http/Controllers`, `Http/Requests`, `Actions`, and `Tests` subdirectories
- [ ] T009 Create `app/Providers/ModuleServiceProvider.php` registering each module's routes, views, and translations, and register it in `bootstrap/providers.php`
- [ ] T010 Create `routes/api_v1.php` with the `/api/v1` prefix on the `web` + `auth:sanctum` middleware stack and a `throttle:120,1` per-user limiter, and register it in `bootstrap/app.php`
- [ ] T011 [P] Configure Vite in `vite.config.js` with the two entry points `resources/css/app-rtl.scss` and `resources/css/app-ltr.scss` plus `resources/js/app.js`, and install Bootstrap 5.3 and Alpine.js 3.x
- [ ] T012 [P] Create the GitHub Actions pipeline at `.github/workflows/ci.yml` running Pint (`--test`), Larastan, and Pest against MySQL 8 and Redis service containers on every pull request, with a failing step blocking merge

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Schema, seeded permissions, the audit write mechanism, and the bilingual shell

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Migrations — in the dependency order from data-model.md

- [ ] T013 Create the `organizations` migration in `database/migrations/` with `type` enum(`university`,`college`), `name_ar` and `name_en` varchar(255), and a nullable self-referencing `parent_id`
- [ ] T014 Create the `users` migration adding `organization_id`, `name_ar` varchar(255), `name_en` varchar(255), `email` varchar(255) **unique on `email` alone so a soft-deleted account's email stays taken**, `password` varchar(255), `locale` char(2) default `ar`, `is_active` boolean default true, `first_login_at` timestamp nullable, and `deleted_at`
- [ ] T015 Publish and run the `spatie/laravel-permission` migrations for `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, and `model_has_permissions`, unmodified
- [ ] T016 [P] Create the `branches` migration with `organization_id`, `campus_name_ar` and `campus_name_en` varchar(255), `gender` enum(`male`,`female`), `is_active` boolean default true, and `deleted_at`
- [ ] T017 [P] Create the `academic_terms` migration with `name_ar`, `name_en`, `academic_year` varchar(9), `semester` enum(`first`,`second`,`summer`), `start_date` and `end_date` dates, `status` enum(`draft`,`active`,`closed`,`archived`) default `draft`, `is_current` boolean default false, and `deleted_at`
- [ ] T018 [P] Create the `courses` migration with `organization_id`, `code` varchar(20), `name_ar`, `name_en`, and **unique `(organization_id, code)`**
- [ ] T019 Create the `sections` migration with `term_id`, `branch_id`, `course_id` (all restrict-on-delete), `section_number` varchar(20), nullable `instructor_id`, **unique `(term_id, branch_id, course_id, section_number)`**, and secondary indexes `(instructor_id)` and `(branch_id, term_id)`. No `gender` column — it is derived through `branch` (FR-050)
- [ ] T020 [P] Create the `coordinator_branches` migration with `user_id` and `branch_id` cascade foreign keys and **unique `(user_id, branch_id)`**
- [ ] T021 [P] Create the `section_access_grants` migration with `section_id` and `user_id` cascade, `granted_by`, `granted_at`, and **unique `(section_id, user_id)`**
- [ ] T022 [P] Create the `section_instructor_history` migration with `section_id` cascade, `instructor_id`, `assigned_at`, nullable `unassigned_at`, and `changed_by`
- [ ] T023 [P] Create the `students` migration with `university_id` varchar(20) **unique**, `name_ar` varchar(255), nullable `name_en` varchar(255), and `deleted_at`
- [ ] T024 Create the `section_enrollments` migration with `student_id` and `section_id` restrict, `status` enum(`active`,`withdrawn`) default `active`, `enrolled_at`, nullable `withdrawn_at`, nullable `withdraw_reason` varchar(500), **unique `(student_id, section_id)`**, and index `(section_id, status)`
- [ ] T025 Create the `audit_logs` migration with nullable `user_id`, `action` varchar(100), nullable `entity_type` varchar(100), nullable `entity_id`, `old_values` json nullable, `new_values` json nullable, `ip_address` varchar(45), nullable `user_agent` varchar(500), `created_at` and **no `updated_at` column**, plus indexes `(user_id, created_at)`, `(entity_type, entity_id)`, `(action, created_at)`, and `(created_at)`
- [ ] T026 Create the audit grant-revocation migration issuing `REVOKE UPDATE, DELETE ON <database>.audit_logs FROM <app_user>`, guarded to no-op when the connection lacks GRANT privileges, per [research.md](./research.md) R-02
- [ ] T027 [P] Create the `settings` migration with `key` varchar(100) **unique**, `value` json, and nullable `updated_by`
- [ ] T028 Create the `import_jobs` migration with `user_id`, `mode` enum(`central`,`section`), nullable `section_id`, nullable `term_id`, `original_filename` varchar(255), `file_path` varchar(500), `file_hash` char(64), `mapping` json nullable, `options` json nullable, `status` enum(`uploaded`,`mapped`,`validating`,`validated`,`committing`,`completed`,`failed`), unsigned int counters `total_rows`/`processed`/`imported`/`duplicates`/`errors` all default 0, nullable `last_committed_row_id`, nullable `failure_reason` varchar(500), nullable `started_at` and `finished_at`, and indexes `(file_hash, term_id)` and `(user_id, created_at)`
- [ ] T029 [P] Create the `import_rows` staging migration with `import_job_id` cascade, `row_number` unsigned int, `raw` json, `resolved` json nullable, `status` enum(`valid`,`warning`,`error`,`committed`,`skipped`), `reasons` json nullable, and indexes `(import_job_id, status)` and `(import_job_id, row_number)`
- [ ] T030 [P] Create the `import_errors` migration with `import_job_id` cascade, `row_number` unsigned int, nullable `column` varchar(100), nullable `value` varchar(500), and `reason` varchar(500)

### Shared models

- [ ] T031 [P] Create `app/Models/Organization.php` and `app/Models/Setting.php` with their relations and casts
- [ ] T032 Create `app/Models/User.php` with `SoftDeletes`, the `HasRoles` trait, an `Argon2id`-hashed `password` cast, hidden `password`, the `coordinatorBranches` and `taughtSections` relations, the `sectionAccessGrants` relation, and the `coordinatorBranchIds()` and `accessibleSectionIds()` helpers that [research.md](./research.md) R-01 depends on
- [ ] T033 [P] Create `app/Models/Branch.php` (SoftDeletes), `app/Models/AcademicTerm.php` (SoftDeletes), and `app/Models/Course.php` with their relations
- [ ] T034 [P] Create `app/Models/Section.php`, `app/Models/SectionInstructorHistory.php`, and `app/Models/SectionAccessGrant.php`, with `Section::gender()` reading through the `branch` relation and no stored gender attribute
- [ ] T035 [P] Create `app/Models/Student.php` (SoftDeletes) and `app/Models/SectionEnrollment.php` with their relations
- [ ] T036 [P] Create `app/Models/AuditLog.php` with `$timestamps = false` except `created_at`, guarded against mass assignment, and **no update or delete path** — no `update()`, `save()` on an existing row, `delete()`, or `forceDelete()` reachable from application code

### Audit write mechanism (foundational — US1 depends on it)

- [ ] T037 Implement `app/Modules/Audit/Services/Auditor.php` with a `record(string $action, ?Model $entity, array $old, array $new)` method performing a plain insert on the **current** connection — no `afterCommit`, no queue, no second connection — capturing actor, IP, and user agent from the request, and stripping any `password` key from both payloads
- [ ] T038 Implement `app/Modules/Audit/Concerns/Auditable.php` as a model trait wiring `created`, `updated`, and `deleted` observers to `Auditor::record()`, with a per-model `auditedAttributes()` allowlist so that no hash or token can enter a payload
- [ ] T039 Create `app/Modules/Shared/Actions/TransactionalAction.php`, the base class that wraps `handle()` in `DB::transaction()` so audited actions inherit the constitution II guarantee rather than each remembering it

### Seeders

- [ ] T040 Create `database/seeders/OrganizationSeeder.php` inserting exactly **one** `college` record, per constitution "organization hierarchy tables exist from v1 with a single college record"
- [ ] T041 Create `database/seeders/PermissionSeeder.php` seeding the 35 named permissions listed in [data-model.md](./data-model.md) Group 1
- [ ] T042 Create `database/seeders/RoleSeeder.php` seeding `SUPER_ADMIN` (all permissions including `scope.bypass_all`, `term.edit_closed`, `section.access_override`), `BRANCH_COORDINATOR` (the `*.view` set plus `audit.view`), and `INSTRUCTOR` (`section.view`, `student.view`, `student.create`, `enrollment.create`, `enrollment.withdraw`, `import.section.run`, `import.view`) exactly as tabulated in data-model.md
- [ ] T043 [P] Create `database/seeders/SettingSeeder.php` seeding `university_id_pattern` = `^\d{9}$`, `import_max_file_mb` = 20, `import_max_rows` = 50000, `attachment_max_mb` = 10, `default_locale` = `ar`, and `session_timeout_minutes` = 60
- [ ] T044 [P] Implement `app/Modules/Settings/Settings.php` as a cached accessor facade for the `settings` table, so that no literal for any seeded value appears in application code (constitution IV)
- [ ] T045 [P] Create `database/seeders/DevAdminSeeder.php` creating the development Super Admin, refusing to run when `APP_ENV=production`
- [ ] T046 [P] Create `database/factories/` factories for `User`, `Branch`, `AcademicTerm`, `Course`, `Section`, `Student`, and `SectionEnrollment`

### Bilingual shell and shared UX components

- [ ] T047 Create `resources/css/tokens.scss` with the design tokens, and `resources/css/app-rtl.scss` and `app-ltr.scss` each importing `tokens.scss` plus the matching Bootstrap 5.3 build (`bootstrap.rtl` for Arabic), and self-host IBM Plex Sans Arabic and Inter under `public/fonts/`
- [ ] T048 Implement `app/Modules/Shared/Http/Middleware/SetLocale.php` resolving locale from `users.locale` and falling back to the `default_locale` setting, and register it on the `web` group
- [ ] T049 Create `resources/views/layouts/app.blade.php` emitting `<html lang dir>` from the active locale, loading the matching stylesheet, and rendering the navigation, the locale switcher, and the flash-message region
- [ ] T050 [P] Create the shared Blade components in `resources/views/components/`: `status-badge.blade.php` (**requires** a text label alongside the colour, per FR-077), `data-table.blade.php`, `wizard-step.blade.php`, `confirm-modal.blade.php` (restates the impact, per FR-082), `empty-state.blade.php`, and `skeleton.blade.php`
- [ ] T051 [P] Create `resources/js/app.js` registering the Alpine components for the locale switcher, confirmation modals, inline validation, and the 5-second queued-job poller
- [ ] T052 [P] Create the `lang/ar/` and `lang/en/` files for `auth`, `users`, `terms`, `branches`, `courses`, `sections`, `students`, `imports`, `audit`, `validation`, and `common`

### Architecture tests (enforce the constitution mechanically)

- [ ] T053 [P] Create `tests/Architecture/NoHardcodedBranchesTest.php` asserting that the strings `Zulfi`, `Majmaah`, and `Rumah` appear in no file under `app/`, and that no gender or campus literal is compared in application logic (constitution IV)
- [ ] T054 [P] Create `tests/Architecture/EveryRouteAuthorizedTest.php` enumerating registered `/api/v1` routes and failing on any route without authorization middleware or a policy, and on any route lacking a corresponding authorization test (constitution VIII)
- [ ] T055 [P] Create `tests/Architecture/LocalizationParityTest.php` recursively diffing the key sets of `lang/ar` and `lang/en` and failing on any key present in one and not the other
- [ ] T056 [P] Create `tests/Architecture/NoHardcodedStringsTest.php` scanning `resources/views/` for user-facing text nodes outside `__()` or `@lang`, with an allowlist for numerals, punctuation, and component slots
- [ ] T057 [P] Create `tests/Architecture/NoHardDeleteTest.php` asserting no `forceDelete` call and no `DELETE` route exists for `users`, `students`, `branches`, or `academic_terms` (constitution V, FR-049)

**Checkpoint**: Schema migrates, permissions and roles seed, the audit mechanism records, the bilingual shell renders in both directions, and the architecture tests pass on an empty application. User story work can now begin.

---

## Phase 3: User Story 1 - Staff Get Accounts And Sign In (Priority: P1) 🎯 MVP

**Goal**: Super Admin creates coordinator and instructor accounts; those people sign in, see a non-blocking first-sign-in recommendation, change their own password, and are refused when deactivated — with authorship preserved.

**Independent Test**: Create one coordinator and one instructor, sign in as each, change a password, deactivate one and confirm sign-in is refused while their name still appears on records they created. Requires no other story.

### Tests for User Story 1

- [ ] T058 [P] [US1] Write `tests/Feature/Authentication/LoginTest.php` covering successful sign-in with session regeneration and role-based landing, the generic failure message that never reveals which field was wrong (FR-009), the inactive-account refusal (FR-011), and 60-minute idle expiry (FR-016)
- [ ] T059 [P] [US1] Write `tests/Feature/Authentication/LoginThrottleTest.php` asserting five failed attempts per email + IP within one minute block the sixth for 60 seconds with a countdown (FR-010)
- [ ] T060 [P] [US1] Write `tests/Feature/Authentication/FirstLoginTest.php` asserting the recommendation appears only when `first_login_at` was null before the request, and not on subsequent sign-ins (FR-012)
- [ ] T061 [P] [US1] Write `tests/Feature/Authentication/PasswordChangeTest.php` covering the policy, the wrong-current-password refusal, the new-equals-current refusal (FR-014), and that other sessions are invalidated while the current one survives (FR-015)
- [ ] T062 [P] [US1] Write `tests/Feature/UserManagement/CreateUserTest.php` covering creation with both name languages and a role, the required `branch_ids` for a coordinator (FR-004), the duplicate-email refusal including against a soft-deleted account (FR-005), and that no password or hash appears in the response or in the audit payload (FR-007)
- [ ] T063 [P] [US1] Write `tests/Feature/UserManagement/DeactivateUserTest.php` asserting sign-in is refused afterwards, all sessions are invalidated, the row is not erased, and the person's name still renders on records they created (FR-018, FR-049)
- [ ] T064 [P] [US1] Write `tests/Feature/UserManagement/ResetPasswordTest.php` asserting all target sessions are invalidated and the password appears in neither audit payload
- [ ] T065 [P] [US1] Write `tests/Feature/Authentication/AbsentRoutesTest.php` asserting `/register`, `/password/forgot`, `/password/reset`, and any 2FA path each return 404 (FR-001)
- [ ] T066 [P] [US1] Write `tests/Authorization/UserEndpointsTest.php` with an allow case and a deny case for each of the 12 endpoints in [contracts/auth-users.md](./contracts/auth-users.md)
- [ ] T067 [P] [US1] Write `tests/Unit/PasswordPolicyTest.php` asserting the ≥ 8 characters, ≥ 1 letter, ≥ 1 digit rule (FR-006)

### Implementation for User Story 1

- [ ] T068 [P] [US1] Implement `app/Modules/Shared/Rules/PasswordPolicy.php` as the single rule object used by create, change, and reset, so the three paths cannot drift
- [ ] T069 [US1] Implement `app/Modules/Authentication/Http/Controllers/LoginController.php` with `POST /auth/login` and `POST /auth/logout` per contracts/auth-users.md: session regeneration, the generic 422, the inactive 403, `first_login_at` set during the request, and the role landing route
- [ ] T070 [US1] Register the login rate limiter keyed on `email` + IP at 5 per minute in `app/Providers/AppServiceProvider.php`, returning 429 with `retry_after`
- [ ] T071 [P] [US1] Implement `app/Modules/Authentication/Http/Controllers/MeController.php` with `GET /auth/me` returning roles, permissions, and the resolved `scope` object, computed per request so a scope change takes effect immediately (FR-027)
- [ ] T072 [US1] Implement `app/Modules/Authentication/Actions/ChangePassword.php` and `app/Modules/Authentication/Http/Controllers/PasswordController.php` with `PUT /auth/password`, invalidating other sessions via `logoutOtherDevices` and auditing `user.password_changed`
- [ ] T073 [US1] Implement `app/Modules/UserManagement/Actions/CreateUser.php` extending `TransactionalAction`, hashing with Argon2id, defaulting `locale` from the `default_locale` setting, attaching roles and coordinator branches, and auditing `user.created` with the password stripped
- [ ] T074 [US1] Implement `app/Modules/UserManagement/Http/Requests/StoreUserRequest.php` and `UpdateUserRequest.php` enforcing unique email across soft-deleted rows, `PasswordPolicy`, and `branch_ids` required when the role is `BRANCH_COORDINATOR`
- [ ] T075 [US1] Implement `app/Modules/UserManagement/Http/Controllers/UserController.php` with `GET /users`, `POST /users`, and `PUT /users/{id}`, never exposing any password field
- [ ] T076 [US1] Implement `app/Modules/UserManagement/Actions/DeactivateUser.php` and the `PATCH /users/{id}/deactivate` endpoint, invalidating the target's sessions, refusing self-deactivation with 409, and auditing `user.deactivated`
- [ ] T077 [US1] Implement `app/Modules/UserManagement/Actions/ResetPassword.php` and the `POST /users/{id}/reset-password` endpoint, invalidating **all** target sessions and auditing `user.password_reset`
- [ ] T078 [P] [US1] Implement `app/Modules/UserManagement/Policies/UserPolicy.php` mapping each action to its `user.*` permission, and register it in `app/Providers/AuthServiceProvider.php`
- [ ] T079 [P] [US1] Create the Blade screens `resources/views/auth/login.blade.php`, `resources/views/auth/change-password.blade.php`, `resources/views/users/index.blade.php`, `create.blade.php`, and `edit.blade.php`, using the shared components and the `confirm-modal` for deactivation
- [ ] T080 [P] [US1] Create `resources/views/components/first-login-banner.blade.php` as a dismissable, non-blocking banner linking to the password-change screen
- [ ] T081 [US1] Add `SESSION_LIFETIME=60` enforcement plus HttpOnly, Secure, and `SameSite=Lax` cookie settings in `config/session.php`, and confirm `/register` is absent from `routes/web.php` and `routes/api_v1.php`
- [ ] T082 [P] [US1] Add the `auth` and `users` translation keys to `lang/ar/` and `lang/en/`, including the generic sign-in failure, the throttle countdown, and the inactive-account message

**Checkpoint**: User Story 1 is fully functional and testable on its own — the MVP.

---

## Phase 4: User Story 2 - Each Role Sees Only Its Own Data (Priority: P2)

**Goal**: Every list, search, export, and directly typed address is limited to the caller's branches or sections, out-of-scope records behave as nonexistent, and scope changes apply on the next request.

**Independent Test**: With two branches and two coordinators seeded, sign in as each and confirm every access path shows only their own branch, and that the other branch's records return 404 rather than 403.

### Tests for User Story 2

- [ ] T083 [P] [US2] Create `database/seeders/ScopeFixtureSeeder.php` seeding two branches, two coordinators, two instructors with distinct sections, and students in each — the fixture quickstart.md scenario 2 depends on
- [ ] T084 [P] [US2] Write `tests/Scope/OutOfScopeReturns404Test.php` asserting **404, never 403** for a cross-branch section, student, enrollment, import job, and audit entry, reached through the API route, the Blade route, the list endpoint, and the export endpoint (FR-024, contracts/README.md)
- [ ] T085 [P] [US2] Write `tests/Scope/CoordinatorBranchScopeTest.php` asserting a coordinator's lists and their totals contain only their branches' records
- [ ] T086 [P] [US2] Write `tests/Scope/InstructorSectionScopeTest.php` asserting an instructor sees only sections where they are `instructor_id`, plus granted sections flagged `shared_access`
- [ ] T087 [P] [US2] Write `tests/Scope/ExportRespectsScopeTest.php` asserting no export can contain a record the same caller cannot retrieve individually (FR-023)
- [ ] T088 [P] [US2] Write `tests/Scope/ScopeChangeTakesEffectImmediatelyTest.php` asserting a role or branch change is in force on the target's next request with no re-authentication and no stale cached permission set (FR-027)
- [ ] T089 [P] [US2] Write `tests/Feature/Authorization/MissingPermissionReturns403Test.php` asserting a **visible** record with a missing permission yields 403 plus an `access.denied` audit entry — so that 403 and 404 are never conflated
- [ ] T090 [P] [US2] Write `tests/Feature/Authorization/NewRoleNeedsNoCodeChangeTest.php` creating a fourth role by seeding permissions only, and asserting it works with no policy or screen modification (FR-020)
- [ ] T091 [P] [US2] Write `tests/Authorization/AccessGrantEndpointTest.php` with allow and deny cases for `POST /sections/{id}/access-grants`

### Implementation for User Story 2

- [ ] T092 [US2] Implement `app/Modules/Authorization/Scopes/BranchScope.php` as an Eloquent **global** scope constraining `branch_id` directly or through the `section` relation to `coordinatorBranchIds()`, inert for `scope.bypass_all` holders and when no user is authenticated
- [ ] T093 [US2] Implement `app/Modules/Authorization/Scopes/SectionScope.php` constraining to `accessibleSectionIds()` — the union of taught sections and `section_access_grants` — with the same two inert conditions
- [ ] T094 [US2] Implement the `app/Modules/Authorization/Concerns/BelongsToBranchScope.php` and `BelongsToSectionScope.php` traits and apply them to `Section`, `Student`, `SectionEnrollment`, `ImportJob`, and `AuditLog`
- [ ] T095 [US2] Configure route-model binding in `app/Providers/RouteServiceProvider.php` to resolve **through** the scoped query so an out-of-scope id raises `ModelNotFoundException` → 404 before any policy runs, per [research.md](./research.md) R-01
- [ ] T096 [US2] Implement `app/Modules/Authorization/Http/Middleware/AuditDeniedAccess.php` recording `access.denied` on a 403 or on a 404 arising from a scope miss, capturing the attempted route and id
- [ ] T097 [P] [US2] Implement `app/Modules/Authorization/Actions/GrantSectionAccess.php` and `POST /sections/{id}/access-grants`, auditing `section.access_granted`
- [ ] T098 [P] [US2] Add the `shared_access` computed attribute to `app/Models/Section.php` and render it as a labelled badge in the section list (FR-026)
- [ ] T099 [P] [US2] Implement `PUT /users/{id}/roles` and `PUT /users/{id}/branches` in `app/Modules/UserManagement/Http/Controllers/UserRoleController.php` and `UserBranchController.php`, auditing `user.roles_changed` and `user.branches_changed` with previous and new sets
- [ ] T100 [P] [US2] Create `resources/views/errors/403.blade.php` as a full-page permission message with a link back to the caller's landing page, exposing no internal detail (FR-025, FR-080)

**Checkpoint**: Stories 1 and 2 both work independently. Cross-branch data is unreachable through every access path.

---

## Phase 5: User Story 3 - Every Sensitive Change Is Accountable (Priority: P3)

**Goal**: The audit trail is queryable, filterable, exportable, visible per entity within scope, and provably immutable and transactional.

**Independent Test**: Perform one account creation, one role change, and one refused access attempt; confirm exactly three complete entries exist, that filtering finds them, and that no path edits or removes them.

### Tests for User Story 3

- [ ] T101 [P] [US3] Write `tests/Feature/Audit/TransactionalWriteTest.php` forcing the audit insert to fail mid-action and asserting the business row is **absent** afterwards and the caller is told to retry (FR-030, constitution II)
- [ ] T102 [P] [US3] Write `tests/Feature/Audit/ImmutabilityTest.php` asserting all three layers independently: no update or delete path in code, no `updated_at` column, and — where the connection has privileges — that a direct `UPDATE` on `audit_logs` is refused for the application user (FR-031)
- [ ] T103 [P] [US3] Write `tests/Feature/Audit/ActionCoverageTest.php` performing each of the 34 action keys tabulated in [contracts/audit.md](./contracts/audit.md) once and asserting exactly one entry each with the correct key (FR-028)
- [ ] T104 [P] [US3] Write `tests/Feature/Audit/NoSecretsInPayloadTest.php` asserting no `old_values` or `new_values` payload written by any audited path contains a `password`, hash, session token, or CSRF token key
- [ ] T105 [P] [US3] Write `tests/Feature/Audit/OldAndNewValuesTest.php` asserting both previous and new values are present for every changed field on an edit
- [ ] T106 [P] [US3] Write `tests/Scope/AuditLogScopeTest.php` asserting a coordinator sees only entries resolving into their branches plus their own actions, and cannot widen `branch_id`
- [ ] T107 [P] [US3] Write `tests/Authorization/AuditEndpointsTest.php` with allow and deny cases for `GET /audit-logs`, `GET /audit-logs/export`, and `GET /{entity}/{id}/activity`
- [ ] T108 [P] [US3] Write `tests/Performance/AuditLogFilterTest.php` seeding 1,000,000 entries and asserting a person + date-range filter returns in under 2 seconds (SC-004), gated on `PERF_SUITE=1`

### Implementation for User Story 3

- [ ] T109 [US3] Implement `app/Modules/Audit/Http/Controllers/AuditLogController.php` with `GET /audit-logs` supporting the `user_id`, `action`, `entity_type`, `entity_id`, `date_from`, `date_to`, and `branch_id` filters, served by the indexes from T025
- [ ] T110 [P] [US3] Implement `app/Modules/Audit/Http/Resources/AuditLogResource.php` adding server-localized `action_label` and `entity_label` while keeping the raw `action` key stable for filtering
- [ ] T111 [P] [US3] Implement `app/Modules/Audit/Exports/AuditLogExport.php` and `GET /audit-logs/export` producing an Arabic-safe `.xlsx` whose rows match the list endpoint exactly for the same filters, with a header block carrying the applied filters, timestamp, and requesting user — auditing `audit.exported`
- [ ] T112 [US3] Implement `app/Modules/Audit/Http/Controllers/EntityActivityController.php` with `GET /{entity}/{id}/activity` for terms, branches, sections, and students, returning 404 when the entity is out of scope so it cannot be used to probe (FR-033)
- [ ] T113 [P] [US3] Apply the `Auditable` trait and an `auditedAttributes()` allowlist to `User`, `Branch`, `AcademicTerm`, `Course`, `Section`, `Student`, and `SectionEnrollment`
- [ ] T114 [P] [US3] Create `resources/views/audit/index.blade.php` with the filter bar, the paginated table, and the export control, plus `resources/views/components/activity-tab.blade.php` for the per-entity views
- [ ] T115 [P] [US3] Add the `audit` translation keys — one `action_label` per action key in contracts/audit.md — to `lang/ar/audit.php` and `lang/en/audit.php`

**Checkpoint**: The audit trail is complete, queryable, and provably immutable before the records it protects are created at scale.

---

## Phase 6: User Story 4 - The Academic Structure Is Set Up (Priority: P4)

**Goal**: Terms, branches, courses, sections, students, and enrollments are manageable, with the section key unique, one current term, instructor changes preserving authorship, and soft delete throughout.

**Independent Test**: Create a term, two branches, one course, two sections sharing a section number across branches, one student enrolled in both; withdraw from one and reassign one instructor. The duplicate number is accepted, a genuine duplicate refused, and nothing is destroyed.

### Tests for User Story 4

- [ ] T116 [P] [US4] Write `tests/Feature/Terms/TermLifecycleTest.php` covering creation in `draft`, the permitted transitions from [data-model.md](./data-model.md), and 409 on a disallowed transition such as `draft → archived`
- [ ] T117 [P] [US4] Write `tests/Feature/Terms/SingleCurrentTermTest.php` asserting exactly one row has `is_current = true` after sequential and concurrent set-current attempts (FR-036)
- [ ] T118 [P] [US4] Write `tests/Feature/Terms/ClosedStatusStorableTest.php` asserting the enum accepts `closed` and `archived` and that **no seeder or screen in this feature sets a term to `closed`**, documenting the deferral in plan.md Complexity Tracking
- [ ] T119 [P] [US4] Write `tests/Feature/Sections/SectionUniquenessTest.php` asserting the same `section_number` coexists across differing term, branch, or course, and that a genuine duplicate is refused **at the database index level** as well as by the form request (FR-041)
- [ ] T120 [P] [US4] Write `tests/Feature/Sections/ChangeInstructorTest.php` asserting access moves, `section_instructor_history` gains a closed row and a new open row, and **every existing record keeps its original author** (FR-048, SC-018)
- [ ] T121 [P] [US4] Write `tests/Feature/Students/SingleStudentRecordTest.php` asserting one `students` row per University ID across multiple enrollments, and that a student appears once in a scoped list despite several enrollments (FR-043, SC-012)
- [ ] T122 [P] [US4] Write `tests/Feature/Students/WithdrawEnrollmentTest.php` asserting the reason is mandatory, the date is recorded, the student record survives, and 409 on an already-withdrawn enrollment (FR-047)
- [ ] T123 [P] [US4] Write `tests/Feature/AcademicStructure/NoHardDeleteTest.php` asserting a branch or term holding records refuses permanent removal and offers only deactivation or archiving (FR-049)
- [ ] T124 [P] [US4] Write `tests/Feature/Sections/TermRequiredFirstTest.php` asserting section creation returns 409 with the explanatory message when no term exists (FR-037)
- [ ] T125 [P] [US4] Write `tests/Authorization/AcademicEndpointsTest.php` with allow and deny cases for all 18 endpoints in [contracts/academic.md](./contracts/academic.md)
- [ ] T126 [P] [US4] Write `tests/Unit/UniversityIdRuleTest.php` asserting the rule reads `university_id_pattern` from settings, defaults to `^\d{9}$`, and produces a message naming the expected format (FR-044)

### Implementation for User Story 4

- [ ] T127 [P] [US4] Implement `app/Modules/Shared/Rules/UniversityId.php` as the single rule used by both manual entry and the import row validator
- [ ] T128 [US4] Implement `app/Modules/AcademicTerms/Actions/SetCurrentTerm.php` extending `TransactionalAction`, clearing the previous flag and setting the new one in one transaction, and auditing `term.set_current` naming both terms
- [ ] T129 [US4] Implement `app/Modules/AcademicTerms/Actions/ChangeTermStatus.php` enforcing the state machine, requiring a reason when leaving `closed`, returning 409 on a disallowed transition, and auditing `term.status_changed`
- [ ] T130 [US4] Implement `app/Modules/AcademicTerms/Http/Controllers/TermController.php` with `GET`, `POST`, `PUT /terms`, `PATCH /terms/{id}/status`, and `PATCH /terms/{id}/set-current`, excluding archived terms from the default list
- [ ] T131 [P] [US4] Implement `app/Modules/AcademicStructure/Http/Controllers/BranchController.php` with `GET`, `POST`, and `PUT /branches`, where `is_active = false` is the only removal path, auditing `branch.created`, `branch.updated`, and `branch.deactivated`
- [ ] T132 [P] [US4] Implement `app/Modules/AcademicStructure/Http/Controllers/CourseController.php` with `GET` and `POST /courses`, refusing a duplicate `(organization_id, code)` with 422
- [ ] T133 [US4] Implement `app/Modules/Sections/Http/Requests/StoreSectionRequest.php` producing the localized message "A section with this number already exists for this course, branch, and term.", with the unique index as the authority
- [ ] T134 [US4] Implement `app/Modules/Sections/Http/Controllers/SectionController.php` with `GET`, `POST`, and `PUT /sections`, returning branch `gender` as a derived field and `shared_access` where applicable
- [ ] T135 [US4] Implement `app/Modules/Sections/Actions/ChangeSectionInstructor.php` extending `TransactionalAction`: update `instructor_id`, close the open history row's `unassigned_at`, insert a new open row, never rewrite any existing record's author, and audit `section.instructor_changed`
- [ ] T136 [P] [US4] Implement `app/Modules/Students/Http/Controllers/StudentController.php` with `GET /students` (distinct across enrollments) and `GET /students/{id}` returning enrollments plus an empty initiative-history placeholder
- [ ] T137 [US4] Implement `app/Modules/Students/Actions/EnrollStudent.php` linking an existing University ID rather than duplicating, keeping the stored name on a mismatch and returning a `warnings` array, and auditing `student.created` when new plus `enrollment.created` always
- [ ] T138 [US4] Implement `app/Modules/Students/Actions/WithdrawEnrollment.php` requiring a reason, setting `withdrawn_at`, preserving the student record, and auditing `enrollment.withdrawn`
- [ ] T139 [P] [US4] Implement the policies `TermPolicy`, `BranchPolicy`, `CoursePolicy`, `SectionPolicy`, `StudentPolicy`, and `EnrollmentPolicy` under their modules and register them in `app/Providers/AuthServiceProvider.php`
- [ ] T140 [P] [US4] Create the Blade screens under `resources/views/terms/`, `branches/`, `courses/`, `sections/`, and `students/` — index, create, edit, and show — using `confirm-modal` for instructor change, withdrawal, term status change, and branch deactivation
- [ ] T141 [P] [US4] Add the `terms`, `branches`, `courses`, `sections`, and `students` translation keys to `lang/ar/` and `lang/en/`

**Checkpoint**: Real academic data can be created and maintained by hand. Stories 1–4 all work independently.

---

## Phase 7: User Story 5 - A Whole Roster Loads From One File (Priority: P5)

**Goal**: The six-step central import validates everything before writing anything, commits in resumable 500-row chunks, links rather than duplicates, and produces a downloadable per-row error report.

**Independent Test**: Upload one file mixing valid rows with every defect category; confirm nothing is written before confirmation, every defect yields a row-level reason, and a re-upload creates zero new records.

### Tests for User Story 5

- [ ] T142 [P] [US5] Write `app/Console/Commands/MakeRosterFixture.php` (`track:make-roster-fixture --rows= --with-defects`) emitting an `.xlsx` containing valid rows plus in-file duplicates, malformed University IDs, missing required values, an unknown branch, an unknown course, an unregistered instructor, a name mismatch, and an unresolvable section
- [ ] T143 [P] [US5] Write `app/Console/Commands/CoreCounts.php` (`track:core-counts`) printing row counts for sections, students, enrollments, courses, and branches — the probe quickstart.md scenario 5 step 4 uses
- [ ] T144 [P] [US5] Write `tests/Feature/Imports/NoWritesBeforeConfirmTest.php` snapshotting core table counts across upload, mapping, validation, and preview and asserting they are **unchanged** — the contract's central guarantee (FR-060)
- [ ] T145 [P] [US5] Write `tests/Feature/Imports/UploadValidationTest.php` asserting refusal over 20 MB, over 50,000 rows, on an unsupported type, and on a `.xlsx` extension whose magic bytes disagree — all before storage (FR-055)
- [ ] T146 [P] [US5] Write `tests/Feature/Imports/MappingTest.php` asserting the suggested mapping is returned, an unmapped required target is refused, and a mapped column absent from the header row is refused (FR-056)
- [ ] T147 [P] [US5] Write `tests/Feature/Imports/ValidationCategoriesTest.php` asserting each of the eight categories in [contracts/imports.md](./contracts/imports.md) produces the right row status and localized reason key (FR-057, FR-062, FR-068)
- [ ] T148 [P] [US5] Write `tests/Feature/Imports/PreviewTest.php` asserting the create-versus-match counts, the warning and error counts, the first 100 rows each with a status, and 409 while validation is unfinished (FR-061)
- [ ] T149 [P] [US5] Write `tests/Feature/Imports/ChunkedCommitTest.php` asserting commit runs in 500-row transactional chunks and that existing records are linked rather than duplicated (FR-064)
- [ ] T150 [P] [US5] Write `tests/Feature/Imports/ResumeAfterChunkFailureTest.php` failing a chunk mid-commit, asserting `last_committed_row_id` sits at the last **successful** chunk, committed rows survive, and a resume completes the remainder (FR-065)
- [ ] T151 [P] [US5] Write `tests/Feature/Imports/DuplicateFileHashTest.php` asserting a matching `file_hash` for the same term warns via `duplicate_of`, and that continuing creates **zero** new records (FR-067, SC-008)
- [ ] T152 [P] [US5] Write `tests/Unit/DuplicateDetectionTest.php` asserting all four levels — University ID, section key, student + section, and student + assignment — are each enforced by a unique index, and that a per-row violation becomes an error reason without failing its chunk (FR-066)
- [ ] T153 [P] [US5] Write `tests/Feature/Imports/ErrorReportTest.php` asserting the `.xlsx` contains row number, column, submitted value, and reason for **every** rejected row with none missing, Arabic-safe (FR-071, SC-009)
- [ ] T154 [P] [US5] Write `tests/Feature/Imports/QueueUnavailableTest.php` asserting that with no worker the job stays `validating` or `committing` and the response says the import is waiting rather than reporting a failure
- [ ] T155 [P] [US5] Write `tests/Authorization/ImportEndpointsTest.php` with allow and deny cases for all 9 endpoints in contracts/imports.md
- [ ] T156 [P] [US5] Write `tests/Performance/ImportThroughputTest.php` asserting 10,000 rows validate and commit within 5 minutes (SC-007), gated on `PERF_SUITE=1`

### Implementation for User Story 5

- [ ] T157 [P] [US5] Implement `app/Models/ImportJob.php`, `app/Models/ImportRow.php`, and `app/Models/ImportError.php` with their relations and status enums
- [ ] T158 [US5] Implement `app/Modules/Imports/Pipeline/FileValidator.php` checking extension, MIME, magic bytes, size against `import_max_file_mb`, and row count against `import_max_rows`, all read from settings — rejecting before storage
- [ ] T159 [US5] Implement `app/Modules/Imports/Actions/UploadImport.php` storing the file on the private disk, computing the SHA-256 `file_hash`, detecting a prior matching import for the same term as `duplicate_of`, creating the job in `uploaded`, and auditing `import.uploaded`
- [ ] T160 [US5] Implement `app/Modules/Imports/Pipeline/HeaderReader.php` and `ColumnMapper.php` streaming only the header row and proposing a mapping by normalized name match
- [ ] T161 [US5] Implement `PUT /imports/{id}/mapping` in `app/Modules/Imports/Http/Controllers/ImportMappingController.php`, persisting the confirmed mapping and the `create_missing_courses` and `create_missing_instructors` options, refusing an unmapped required target
- [ ] T162 [US5] Implement `app/Modules/Imports/Pipeline/RowValidator.php` detecting all eight categories from contracts/imports.md, using `UniversityId` from T127 and a running hash set for in-file duplicates
- [ ] T163 [US5] Implement `app/Modules/Imports/Jobs/ValidateImportJob.php` streaming the file with OpenSpout and writing one `import_rows` row per source row with `raw`, `resolved`, `status`, and localized `reasons` — **writing to no core table**
- [ ] T164 [US5] Implement `POST /imports/{id}/validate` returning 202 and dispatching the job, with 409 when the job is not in `mapped` state
- [ ] T165 [US5] Implement `GET /imports/{id}/preview` reading counts and the first 100 rows from `import_rows`, with 409 while validation is unfinished
- [ ] T166 [US5] Implement `app/Modules/Imports/Pipeline/Resolver.php` resolving Branch → Course → Section → Instructor → Student → Enrollment, honouring the create-or-skip options and keeping the stored student name on a mismatch
- [ ] T167 [US5] Implement `app/Modules/Imports/Jobs/CommitImportChunkJob.php` as a self-chaining job walking `import_rows` by id in 500-row chunks, each chunk one `DB::transaction()` that advances `last_committed_row_id` **as its final statement**, catching per-row unique violations as error reasons without failing the chunk
- [ ] T168 [US5] Implement `POST /imports/{id}/commit` requiring `confirm: true`, returning 202, refusing with 409 unless the job is `validated` and the preview was fetched, and auditing `import.committed` with filename, counts, and actor
- [ ] T169 [US5] Implement `POST /imports/{id}/resume` restarting from `id > last_committed_row_id`, with 409 unless the job is `failed`, auditing `import.resumed`
- [ ] T170 [US5] Implement `GET /imports/{id}` returning status, the five counters, `last_committed_row_id`, `failure_reason`, timings, and `progress_percent` for the 5-second poller
- [ ] T171 [US5] Implement `app/Modules/Imports/Exports/ImportErrorReportExport.php` materializing `import_errors` from `import_rows` and streaming an Arabic-safe `.xlsx`, exposed as `GET /imports/{id}/errors/download` and auditing `import.error_report_downloaded`
- [ ] T172 [P] [US5] Implement `app/Modules/Imports/Policies/ImportJobPolicy.php` mapping each endpoint to `import.central.run`, `import.section.run`, or `import.view`, and requiring job ownership
- [ ] T173 [P] [US5] Store a notification row on queued-job completion in `app/Modules/Imports/Notifications/ImportFinished.php` so the later notification-centre feature surfaces it without rework — **no email is sent in this feature**
- [ ] T174 [P] [US5] Create the wizard screens `resources/views/imports/upload.blade.php`, `mapping.blade.php`, `validating.blade.php`, `preview.blade.php`, `committing.blade.php`, and `report.blade.php` using `wizard-step` and the 5-second poller
- [ ] T175 [P] [US5] Add the `imports` translation keys — including every `import.*` reason key from contracts/imports.md — to `lang/ar/imports.php` and `lang/en/imports.php`

**Checkpoint**: A real 5,000-row roster loads end to end with an error report — the PRD Phase 1 gate's second check.

---

## Phase 8: User Story 6 - Instructors Load Their Own Section Lists (Priority: P6)

**Goal**: An instructor imports a two-column student list into a section they teach, through the same validated six steps, and cannot touch anyone else's section.

**Independent Test**: As an instructor, upload a file with one new student, one existing student whose name differs, and one duplicate row; then attempt the same against another instructor's section.

### Tests for User Story 6

- [ ] T176 [P] [US6] Write `tests/Feature/Imports/SectionModeImportTest.php` asserting the same six steps apply, nothing is saved before confirmation, and only `university_id` and `student_name` are required
- [ ] T177 [P] [US6] Write `tests/Feature/Imports/LinkExistingStudentTest.php` asserting an existing student is linked rather than duplicated, and that a differing name keeps the stored value and raises a warning (FR-069)
- [ ] T178 [P] [US6] Write `tests/Feature/Imports/InFileDuplicateRejectedTest.php` asserting a repeated student in one file has its second occurrence rejected naming its row number
- [ ] T179 [P] [US6] Write `tests/Scope/InstructorCannotImportForeignSectionTest.php` asserting **404** plus an `access.denied` audit entry when an instructor targets a section they neither teach nor hold a grant for (FR-054)
- [ ] T180 [P] [US6] Write `tests/Feature/Sections/InstructorWithNoSectionsTest.php` asserting the empty state naming whom to contact, not a blank page

### Implementation for User Story 6

- [ ] T181 [US6] Implement `POST /sections/{id}/imports` in `app/Modules/Imports/Http/Controllers/SectionImportController.php`, resolving the section through the scoped query so a foreign section yields 404, and creating the job with `mode: section`
- [ ] T182 [US6] Extend `app/Modules/Imports/Pipeline/RowValidator.php` with the section-mode rule set — `university_id` and `student_name` only, with the section fixed by the job rather than read from the file
- [ ] T183 [US6] Extend `app/Modules/Imports/Pipeline/Resolver.php` for section mode: attach students to the one job section only, never creating or resolving a section from file values
- [ ] T184 [P] [US6] Create `resources/views/sections/import.blade.php` reusing the wizard components with the two-column template download
- [ ] T185 [P] [US6] Add the instructor-facing `imports` and `sections` empty-state translation keys to `lang/ar/` and `lang/en/`

**Checkpoint**: Instructors are unblocked from the central office for routine roster corrections. Stories 1–6 all work independently.

---

## Phase 9: User Story 7 - Everyone Works In Their Own Language (Priority: P7)

**Goal**: Every screen delivered by stories 1–6 is complete, correctly mirrored, accessible, and behaviourally identical in Arabic and English, with the shared UX states applied throughout.

**Independent Test**: Switch language on each delivered screen and confirm full translation, correct mirroring, and identical behaviour; then confirm every status carries a label and every form validates, loads, errors, and confirms as specified.

**Note**: This story is cross-cutting — its components ship alongside stories 1–6, and this phase is the sweep that verifies and closes the gaps.

### Tests for User Story 7

- [ ] T186 [P] [US7] Write `tests/Feature/Localization/EveryScreenRendersBothDirectionsTest.php` asserting every registered `web` route renders in both `ar` and `en` with the correct `lang` and `dir` attributes and the matching stylesheet
- [ ] T187 [P] [US7] Write `tests/Feature/Localization/NoUntranslatedOutputTest.php` asserting no rendered screen contains a raw translation key or a fallback-language string while the other locale is active
- [ ] T188 [P] [US7] Write `tests/Feature/Ux/StatusBadgeRequiresLabelTest.php` asserting `status-badge` cannot render colour without a text label (FR-077)
- [ ] T189 [P] [US7] Write `tests/Feature/Ux/ConfirmationRequiredTest.php` asserting student withdrawal, account deactivation, instructor change, and term status change each require a confirmation restating the impact (FR-082)
- [ ] T190 [P] [US7] Write `tests/Feature/Ux/ValidationStatesTest.php` asserting server-side rules repeat every client-side rule and that errors render beside their field in the active language (FR-078)
- [ ] T191 [P] [US7] Write `tests/Performance/PageLoadTest.php` asserting first contentful render under 2 seconds at p95 (SC-015), gated on `PERF_SUITE=1`

### Implementation for User Story 7

- [ ] T192 [US7] Sweep every Blade view under `resources/views/` for hard-coded user-facing text and move each string into `lang/ar/` and `lang/en/` until `NoHardcodedStringsTest` and `LocalizationParityTest` both pass
- [ ] T193 [US7] Audit and correct RTL mirroring across navigation, tables, forms, the import wizard, and the audit filter bar in `resources/css/app-rtl.scss` and the affected views
- [ ] T194 [P] [US7] Implement the number and date formatting helpers in `app/Modules/Shared/Support/Format.php` emitting Western Arabic digits 0-9 and `DD/MM/YYYY` in both locales, and apply them to every rendered number and date (FR-084)
- [ ] T195 [P] [US7] Add the responsive rules to `resources/css/tokens.scss` verified at 1280 px and 1920 px, with tabular views becoming stacked cards below 768 px (FR-085)
- [ ] T196 [US7] Complete WCAG 2.1 AA and full keyboard operation across every screen: focus order, visible focus rings, ARIA labels on icon-only controls, and skip links in `resources/views/layouts/app.blade.php` (FR-085, SC-014)
- [ ] T197 [P] [US7] Apply the loading, error, success, and empty states uniformly — spinner-and-disable on every action control, skeletons on lists, retry on recoverable failures, a 4-second success toast, and `empty-state` wherever a list can be empty (FR-079, FR-080, FR-081)
- [ ] T198 [P] [US7] Implement the locale preference control in `resources/views/profile/preferences.blade.php` persisting `users.locale`

**Checkpoint**: All seven stories are independently functional in both languages.

---

## Phase 10: Typed EAV Capacity Spike (Blocks Acceptance)

**Purpose**: Prove the Phase 2 value-storage design before Phase 2 depends on it (FR-087, FR-088, PRD risk R-03)

**⚠️ These tables carry no controller, route, policy, or view in this feature.** Containment is asserted by T204.

- [ ] T199 [P] Create migrations for `initiatives`, `initiative_schema_versions` (**unique `(initiative_id, version_number)`**), `initiative_fields` (**unique `(schema_version_id, field_key)`**), and `initiative_sections` (**unique `(initiative_id, section_id)`**) per [data-model.md](./data-model.md) Group 6
- [ ] T200 [P] Create migrations for `initiative_targets` (**unique `(assignment_id, student_id)`**), `student_initiative_records` (**unique `(assignment_id, student_id)`**), and `student_initiative_values` (**unique `(record_id, field_id)`**) with the typed columns `value_text`, `value_number`, `value_date`, `value_bool`, `value_json`, and `attachment_id`
- [ ] T201 Add the two indexes under measurement — `student_initiative_values(field_id, value_bool)` and `student_initiative_records(assignment_id, is_complete)` — as a separate migration that can be toggled, since whether they ship is decided by the spike result, not in advance
- [ ] T202 Implement `database/seeders/EavSpikeSeeder.php` generating, with chunked raw multi-row inserts rather than Eloquent, 1 term, 6 branches, 3,000 sections, 30,000 students, 30,000 enrollments, 10 initiatives × 5 schema versions, 50 field definitions, 30,000 records, and **1,500,000** values distributed ≈45% `value_text`, ≈25% `value_number`, ≈15% `value_bool`, ≈10% `value_date`, ≈5% `value_json`
- [ ] T203 Implement `tests/Performance/EavSpikeTest.php` measuring each of the five queries in [research.md](./research.md) R-06 as p95 over 10 cold runs against its threshold: assignment grid of 200 records × 15 fields pivoted **< 2 s**; per-section completion counts across ≈3,000 sections **< 3 s**; initiative-wide student-weighted completion at 30,000 targets **< 3 s**; full recalculation sweep over 30,000 records **< 2 min**; single value upsert **< 500 ms**
- [ ] T204 [P] Write `tests/Architecture/EavSpikeContainmentTest.php` asserting no route, controller, policy, or Blade view in this feature references the Group 6 tables
- [ ] T205 Write `specs/001-foundation-access-roster/spike-eav-results.md` recording the schema measured, the index set, row counts, `EXPLAIN` output per query, measured p95s, and a PASS/FAIL per threshold
- [ ] T206 On any FAIL, record the finding and the proposed remedy in `spike-eav-results.md` before Phase 2 planning begins, selecting from the ranked candidates in research.md R-06 — and state the reason if a new remedy is proposed instead (FR-088)

**Checkpoint**: The Phase 2 storage design is proven or its remedy is decided and recorded. Phase 2 planning is unblocked.

---

## Phase 11: Polish & Cross-Cutting Concerns

- [ ] T207 [P] Implement structured JSON application logging with request id, user id, and route in `app/Modules/Shared/Logging/` and `config/logging.php` (PRD 8.8)
- [ ] T208 [P] Implement the `/health` endpoint in `app/Modules/Shared/Http/Controllers/HealthController.php` checking database, Redis, queue workers, and storage
- [ ] T209 [P] Implement `app/Modules/Shared/Http/Middleware/ContentSecurityPolicy.php` with a strict policy and register it on the `web` group
- [ ] T210 [P] Verify every state-changing route carries CSRF protection, and add `tests/Feature/Security/CsrfCoverageTest.php`
- [ ] T211 Run the OWASP Top 10 review against this feature's surface and record the findings in `docs/security-review-001.md`
- [ ] T212 [P] Write the deployment runbook in `docs/runbook-deploy.md` covering the Supervisor worker configuration, the audit-table grant verification for staging and production, backup and restore, and the 5-year audit retention policy
- [ ] T213 [P] Write `docs/architecture-001.md` recording the decisions from research.md so they survive the key-person risk in PRD R-05
- [ ] T214 Execute the full gate from [quickstart.md](./quickstart.md): `pint --test`, `phpstan analyse`, `php artisan test`, `PERF_SUITE=1 php artisan test --testsuite=Performance`, and `--testsuite=Architecture`
- [ ] T215 Walk all eight quickstart.md validation scenarios manually and record the outcome against the four PRD Phase 1 gate checks and the seven constitution Definition-of-Done items

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Setup — **BLOCKS all user stories**
- **User Stories (Phases 3–9)**: All depend on Phase 2. Beyond that they follow the PRD build order rather than running freely in parallel — see below
- **EAV Spike (Phase 10)**: Depends only on Phase 2 migrations; can run in parallel with Phases 3–9 and **must complete before this feature is accepted**
- **Polish (Phase 11)**: Depends on Phases 3–9

### User Story Dependencies

These stories are **not** fully independent of one another, and pretending otherwise would
misrepresent the work. The PRD build order is a data-dependency chain:

- **US1 (P1)** — Accounts and sign-in. Depends only on Phase 2. **The MVP.**
- **US2 (P2)** — Scoping. Depends on Phase 2; needs US1's accounts to have anyone to scope, and its own `ScopeFixtureSeeder` for branches and sections
- **US3 (P3)** — Audit viewing. Depends on Phase 2's `Auditor` and on US2 for the scoped audit view
- **US4 (P4)** — Academic structure. Depends on Phase 2 and US2's scopes (sections and students are scoped models)
- **US5 (P5)** — Central import. Depends on US4 — it resolves into terms, branches, courses, sections, students, and enrollments
- **US6 (P6)** — Instructor import. Depends on US5's pipeline and US2's section scope
- **US7 (P7)** — Bilingual sweep. Depends on US1–US6 existing, since it verifies their screens

### Within Each User Story

- Tests are written first and must **fail** before implementation
- Rules and models before actions; actions before controllers; controllers before views
- Translation keys land with the screen that uses them, not afterwards

### Parallel Opportunities

- Setup: T004, T005, T006, T007, T011, T012 in parallel
- Phase 2 migrations: T016–T018, T020–T023, T027, T029, T030 in parallel (T014, T019, T024, T025 have ordering dependencies)
- Phase 2 models: T031, T033, T034, T035, T036 in parallel after T032
- Phase 2 architecture tests: T053–T057 all in parallel
- Every test task marked [P] within a story runs in parallel
- **Phase 10 runs in parallel with the whole of Phases 3–9** — it touches only its own tables, seeder, and tests, so it can be given to a second developer on day one and is the single best use of parallel capacity in this feature

---

## Parallel Example: User Story 1

```bash
# All US1 tests together (they must fail first):
Task: "tests/Feature/Authentication/LoginTest.php"
Task: "tests/Feature/Authentication/LoginThrottleTest.php"
Task: "tests/Feature/Authentication/FirstLoginTest.php"
Task: "tests/Feature/Authentication/PasswordChangeTest.php"
Task: "tests/Feature/UserManagement/CreateUserTest.php"
Task: "tests/Feature/UserManagement/DeactivateUserTest.php"
Task: "tests/Feature/UserManagement/ResetPasswordTest.php"
Task: "tests/Feature/Authentication/AbsentRoutesTest.php"
Task: "tests/Authorization/UserEndpointsTest.php"
Task: "tests/Unit/PasswordPolicyTest.php"

# Then the independent implementation pieces:
Task: "app/Modules/Shared/Rules/PasswordPolicy.php"
Task: "app/Modules/Authentication/Http/Controllers/MeController.php"
Task: "app/Modules/UserManagement/Policies/UserPolicy.php"
Task: "resources/views/components/first-login-banner.blade.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 only)

1. Phase 1: Setup
2. Phase 2: Foundational — **critical, blocks everything**
3. Phase 3: User Story 1
4. **STOP and VALIDATE**: quickstart.md scenario 1 end to end
5. Demo: the Super Admin can create accounts and staff can sign in

### Incremental Delivery

| Increment | Adds | Demonstrable outcome |
|---|---|---|
| Setup + Foundational | Schema, permissions, audit mechanism, bilingual shell | Migrations and seeders run; architecture tests green |
| + US1 | Accounts and sign-in | **MVP** — staff have access |
| + US2 | Scoping | Real data can be loaded safely |
| + US3 | Audit viewing | Accountability is demonstrable |
| + US4 | Academic structure | Terms, sections, and students exist by hand |
| + US5 | Central import | PRD Phase 1 gate reachable |
| + US6 | Instructor import | Instructors self-serve |
| + US7 | Bilingual sweep | Arabic-first users fully served |
| + Phase 10 | EAV spike result | Phase 2 planning unblocked |

### Parallel Team Strategy

With the PRD's team of a backend developer, a frontend developer, and a QA engineer:

1. Everyone on Setup + Foundational together — it blocks all else
2. Then: **backend** takes the US1 → US2 → US3 → US4 → US5 → US6 chain in order, because each
   link genuinely depends on the last; **frontend** trails one story behind on Blade screens,
   components, and the US7 sweep; **QA** writes each story's test tasks ahead of its
   implementation, which the tests-first rule requires anyway
3. Phase 10 (the EAV spike) goes to whoever has capacity from day one — it is the only truly
   independent block of work in this feature, and its answer gates Phase 2 planning

---

## Notes

- **215 tasks**: 12 Setup, 45 Foundational, 25 US1, 18 US2, 15 US3, 26 US4, 34 US5, 10 US6, 13 US7, 8 EAV spike, 9 Polish
- [P] tasks touch different files with no dependency on incomplete work
- Every task states its exact file path
- Tests must fail before implementation — verify, do not assume
- Commit after each task or logical group; feature branches follow the `NNN-feature-name` prefix
- **403 where 404 is specified is a defect**, not a preference — it discloses that an out-of-scope record exists (constitution I)
- No task in this feature may set a term to `closed`, emit a 423, or expose the Group 6 EAV tables to a user — those belong to later features
