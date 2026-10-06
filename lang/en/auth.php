<?php

declare(strict_types=1);

/* T134 — the English half. Key for key with lang/ar/auth.php; LocalizationParityTest enforces it. */

return [
    'title' => 'Sign in',
    'subtitle' => 'Enter your institutional email and password',
    'email' => 'Institutional email',
    'password' => 'Password',
    'sign_in' => 'Sign in',
    'sign_out' => 'Sign out',

    // FR-009. One message for both failures.
    'failed' => 'The email or password is incorrect.',

    // FR-010.
    'locked' => 'Too many attempts. Try again in :seconds seconds.',

    // FR-011.
    'deactivated' => 'This account has been deactivated. Please contact the administrator.',

    // FR-001.
    'no_registration' => 'There is no self-registration — the administrator creates all accounts.',

    'tagline_ar' => 'معًا .. نحو أثر أكاديمي أكبر',
    'tagline_en' => 'Track Today, a Brighter Tomorrow',

    // FR-012.
    'first_login' => [
        'title' => 'We recommend changing your initial password',
        'message' => 'You are using the password set by the administrator. Change it now or later from your profile.',
        'action' => 'Change password',
    ],

    'password_change' => [
        'title' => 'Change password',
        'current' => 'Current password',
        'new' => 'New password',
        'confirm' => 'Confirm password',
        'rule' => 'At least 8 characters, including a letter and a digit.',
        'changed' => 'Password changed',
        'changed_message' => 'Your other sessions have been signed out.',
    ],

    'errors' => [
        'policy' => 'Must be at least 8 characters with a letter and a digit.',
        'same_as_current' => 'The new password matches your current one.',
        'mismatch' => 'Confirmation does not match the new password.',
        'wrong_current' => 'Current password is incorrect.',
    ],
];
