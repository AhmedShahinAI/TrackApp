<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T153 — the sidebar, per role
|--------------------------------------------------------------------------
|
| Transcribed from the approved reference, design-system/ui_kits/web-app/Shell.jsx line 40, with
| two deliberate departures recorded below. Configuration rather than code (constitution IV): the
| order of the sidebar is a product decision, and a product decision that requires a deployment to
| change is a product decision nobody makes.
|
| ## The departure from the reference
|
| **`programs`** was held back by T153 pending conflict C-04. C-04 was accepted (2026-09-27) and the
| screen built in Phase 14, so T321 restored it between `branches` and `courses`, as the reference
| has it.
|
| **`dashboard` and `initiatives` are stubbed.** Both are in the reference and neither has a screen
| in this feature. They render, and they lead to a "coming in a later phase" placeholder rather than
| to a 404. The alternative — hiding them until their phase lands — would mean the sidebar a user
| sees today is not the sidebar in the approved design, and every visual baseline would have to be
| recaptured when they arrive.
|
| ## How a role not listed here is treated
|
| It falls back to every entry whose permission the caller actually holds. FR-020 requires a fourth
| role to be a seeded row and nothing else; a navigation map keyed only by role name would make it a
| code change, and NewRoleNeedsNoCodeChangeTest would fail. The per-role lists below are an ordering
| and a curation of what a known role sees — never the authorization, which is always the permission
| on the entry and the `can:` middleware on the route behind it.
|
*/

return [

    /*
     * Every entry the sidebar knows, keyed by the id the reference uses. `permission` is what
     * actually decides visibility; `stub` marks a destination that does not exist yet.
     */
    'items' => [
        'home' => ['icon' => 'house', 'route' => 'home', 'permission' => 'dashboard.view'],
        'dashboard' => ['icon' => 'layout-dashboard', 'route' => null, 'permission' => 'dashboard.view', 'stub' => true],
        'initiatives' => ['icon' => 'lightbulb', 'route' => null, 'permission' => 'dashboard.view', 'stub' => true],
        'users' => ['icon' => 'user-cog', 'route' => 'users.index', 'permission' => 'user.view'],
        // US4 built all four screens, so none of these are stubs any more.
        'terms' => ['icon' => 'calendar-range', 'route' => 'terms.index', 'permission' => 'term.view'],
        'branches' => ['icon' => 'building-2', 'route' => 'branches.index', 'permission' => 'branch.view'],
        // Phase 14 (T321) — FR-095: only a Super Admin's list carries it. `library` (T465): v2's
        // Shell.jsx corrected it, and `graduation-cap` became الطلبة's.
        'programs' => ['icon' => 'library', 'route' => 'programs.index', 'permission' => 'program.view'],
        'courses' => ['icon' => 'book-open', 'route' => 'courses.index', 'permission' => 'course.view'],
        // `graduation-cap` (T465), v2's `studentsadmin`. v1 gave that glyph to `programs`, which is why
        // this was `user` until v2 reassigned them; no two entries share an icon either way.
        'students' => ['icon' => 'graduation-cap', 'route' => 'students.index', 'permission' => 'student.view'],
        'sections' => ['icon' => 'users', 'route' => 'sections.index', 'permission' => 'section.view'],
        'mysections' => ['icon' => 'users', 'route' => 'sections.index', 'permission' => 'section.view'],
        // Amendment 4 (T499) — the section approval queue, the entry T466 seeded and left hidden.
        'approvals' => ['icon' => 'clipboard-check', 'route' => 'sections.approvals', 'permission' => 'section.approve'],
        // US5 built the wizard, so this is no longer a stub.
        'imports' => ['icon' => 'file-up', 'route' => 'imports.index', 'permission' => 'import.view'],
        // US3 built the screen, so this is no longer a stub.
        'audit' => ['icon' => 'shield-check', 'route' => 'audit.index', 'permission' => 'audit.view'],
        // Amendment 2 (T421) — the support queue.
        'support' => ['icon' => 'life-buoy', 'route' => 'support-requests.index', 'permission' => 'support.view'],
    ],

    /*
     * The order each known role sees.
     *
     * `programs` for SUPER_ADMIN only, per the approved sidebar. A coordinator holds `program.view`
     * so the section screens can name and filter by programme, not to manage the list.
     *
     * The sidebar is a curation, never the authorization. A coordinator who types /programs gets
     * the list read-only, which is deliberate: T315 says a coordinator may read programmes, and the
     * list is institution-wide reference data (plan.md Amendment 1), with section counts limited to
     * their own branches. Managing is FR-095's, enforced server-side by `program.create` and
     * `program.edit`, which only SUPER_ADMIN holds. ProgramEndpointsTest pins both halves.
     */
    'roles' => [
        // `support` (T421) for the two roles holding support.view; never for INSTRUCTOR (FR-142).
        // `approvals` (T499) after `sections`, as v2's Shell.jsx has it; never for INSTRUCTOR (FR-177).
        'SUPER_ADMIN' => ['home', 'dashboard', 'initiatives', 'users', 'terms', 'branches', 'programs', 'courses', 'sections', 'approvals', 'students', 'imports', 'support', 'audit'],
        'BRANCH_COORDINATOR' => ['home', 'initiatives', 'sections', 'approvals', 'students', 'imports', 'support', 'audit'],
        /*
         * No `students` for an instructor, although the permission would allow it and the screen
         * would be correctly scoped. An instructor reaches a student through the section they
         * teach, which is the only context in which the record means anything to them; a flat list
         * of their students answers a question nobody in that role asks.
         */
        'INSTRUCTOR' => ['home', 'mysections', 'imports'],
    ],
];
