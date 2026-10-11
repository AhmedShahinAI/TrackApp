<?php

declare(strict_types=1);

/* T134 — the English half of the accounts screen. Key for key with lang/ar/users.php. */

return [
    'title' => 'Users',
    'subtitle' => 'Create and manage coordinator and instructor accounts',

    'create' => 'New user',
    'edit' => 'Edit user',
    'created' => 'Account created',
    'saved' => 'Changes saved',

    'fields' => [
        'name' => 'Name',
        'name_ar' => 'Arabic name',
        'name_en' => 'English name',
        'email' => 'Institutional email',
        'password' => 'Initial password',
        'role' => 'Role',
        'branches' => 'Branches',
        'status' => 'Status',
        'last_login' => 'Last sign-in',
        'sections' => 'Sections',
    ],

    'roles' => [
        'SUPER_ADMIN' => 'Super Admin',
        'BRANCH_COORDINATOR' => 'Branch coordinator',
        'INSTRUCTOR' => 'Instructor',
    ],

    'never_signed_in' => 'Never signed in',

    'filters' => [
        'role' => 'Role',
        'branch' => 'Branch',
        'status' => 'Status',
        'all' => 'All',
        'search' => 'Search by name or email',
        'sort' => 'Sort by',
    ],
    'sorts' => [
        'name' => 'Name',
        'last_login' => 'Last sign-in — not signed in longest first',
        'last_login_desc' => 'Last sign-in — most recent first',
    ],

    // FR-082.
    'deactivate' => [
        'action' => 'Deactivate account',
        'title' => 'Deactivate account',
        'message' => 'All of this user\'s sessions will end and they will no longer be able to sign in.',
        'preserved' => 'Their name stays on everything they created; no record is deleted.',
        'confirm' => 'Deactivate',
        'done' => 'Account deactivated',
        'self' => 'You cannot deactivate your own account.',
    ],

    'reset_password' => [
        'action' => 'Reset password',
        'title' => 'Reset password',
        'message' => 'All of this user\'s sessions will end. Tell them the new password yourself — no email is sent.',
        'confirm' => 'Reset',
        'done' => 'Password reset',
    ],

    'empty' => [
        'title' => 'No matching accounts',
        'message' => 'Adjust the filters, or create a new account.',
    ],

    'errors' => [
        'email_taken' => 'That email already belongs to another account, even a deactivated one.',
        'branches_required' => 'A branch coordinator needs at least one branch.',
    ],
];
