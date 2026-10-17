<?php

declare(strict_types=1);

/* T099 — the English navigation nouns. Key for key with lang/ar/nav.php. */

return [
    'home' => 'Home',
    'dashboard' => 'Dashboard',
    'initiatives' => 'Initiatives',
    'branches' => 'Branches',
    'programs' => 'Programs',
    'courses' => 'Courses',
    'sections' => 'Sections',

    // The instructor's own sections. Same destination as `sections`, different name, because
    // "my" is the whole of what distinguishes an instructor's view (reference Shell.jsx NAV).
    'mysections' => 'My sections',
    'users' => 'Users',
    'terms' => 'Academic terms',
    'imports' => 'Imports',
    'audit' => 'Audit log',
    'support' => 'Support requests',

    // T466 — v2's new navigation keys (i18n.js). See lang/ar/nav.php for why two have no entry yet.
    'approvals' => 'Section approvals',
    'myinitiatives' => 'My initiatives',
    'studentsadmin' => 'Students',
    'reports' => 'Reports',
    'settings' => 'Settings',
    'students' => 'Students',
    'instructors' => 'Instructors',
    'groups' => [
        'overview' => 'Overview',
        'academic' => 'Academic structure',
        'people' => 'People',
        'operations' => 'Operations',
    ],
];
