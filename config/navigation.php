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
| ## The two departures from the reference
|
| **`programs` is omitted from SUPER_ADMIN.** The reference lists it between `branches` and
| `courses`. T153 holds it back pending conflict C-04, so the entry is absent rather than stubbed —
| a stub would promise a screen whose shape is still being argued about.
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
        'terms' => ['icon' => 'calendar-range', 'route' => null, 'permission' => 'term.view', 'stub' => true],
        'branches' => ['icon' => 'building-2', 'route' => null, 'permission' => 'branch.view', 'stub' => true],
        'courses' => ['icon' => 'book-open', 'route' => null, 'permission' => 'course.view', 'stub' => true],
        'sections' => ['icon' => 'users', 'route' => 'sections.index', 'permission' => 'section.view'],
        'mysections' => ['icon' => 'users', 'route' => 'sections.index', 'permission' => 'section.view'],
        'imports' => ['icon' => 'file-up', 'route' => null, 'permission' => 'import.view', 'stub' => true],
        // US3 built the screen, so this is no longer a stub.
        'audit' => ['icon' => 'shield-check', 'route' => 'audit.index', 'permission' => 'audit.view'],
    ],

    /*
     * The order each known role sees. `programs` is absent from SUPER_ADMIN on purpose — see above.
     */
    'roles' => [
        'SUPER_ADMIN' => ['home', 'dashboard', 'initiatives', 'users', 'terms', 'branches', 'courses', 'sections', 'imports', 'audit'],
        'BRANCH_COORDINATOR' => ['home', 'initiatives', 'sections', 'imports', 'audit'],
        'INSTRUCTOR' => ['home', 'mysections', 'imports'],
    ],
];
