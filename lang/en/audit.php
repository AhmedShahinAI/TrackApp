<?php

declare(strict_types=1);

/* The English half of the audit action labels. Key for key with lang/ar/audit.php. */

return [
    'actions' => [
        'unknown' => 'Unknown action',

        'auth.login' => 'Signed in',
        'auth.logout' => 'Signed out',
        'auth.login_failed' => 'Failed sign-in',
        'auth.login_blocked' => 'Sign-in blocked',

        'access.denied' => 'Access denied',

        'user.created' => 'Account created',
        'user.updated' => 'Account updated',
        'user.deactivated' => 'Account deactivated',
        'user.password_changed' => 'Password changed',
        'user.password_reset' => 'Password reset',
        'user.roles_changed' => 'Roles changed',
        'user.branches_changed' => 'Branches changed',

        'branch.created' => 'Branch added',
        'branch.updated' => 'Branch updated',
        'branch.deleted' => 'Branch deactivated',

        'term.set_current' => 'Current term set',
        'section.instructor_changed' => 'Section instructor changed',
        'section.access_granted' => 'Section access granted',
        'enrollment.withdrawn' => 'Withdrawn from section',
        'import.committed' => 'Import committed',
    ],
];
