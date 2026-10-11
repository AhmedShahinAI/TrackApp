<?php

declare(strict_types=1);

/*
 * T356 + T376 — the student support channel (Amendment 2). Read as __('support.…'); key for key with
 * lang/ar/support.php (LocalizationParityTest).
 *
 * The category labels are NOT here. They live with the categories in the `support_categories`
 * setting, which the Super Admin extends (constitution IV); a translation key per category would be
 * a second list that the sixth category added through settings would never reach.
 */

return [
    'layout' => [
        'title' => 'Student support',
        'footer' => 'This form needs no account and no sign-in.',
    ],

    // The five states (data-model.md "State machine"); the badge and the status page read these.
    'statuses' => [
        'new' => 'New',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        'rejected' => 'Rejected',
    ],

    // The two generic failures (contracts/support-public.md). Each is ONE message for several
    // causes, and must stay byte-identical across them (FR-119, FR-135) — never split by reason.
    'failures' => [
        'submission' => 'Your request could not be sent. Check your University ID and try again.',
        'status' => 'No request was found with this reference number.',
        'challenge_unavailable' => 'The service is temporarily unavailable. Please try again shortly.',
    ],

    // FR-130: a limit was reached and when to retry — never which limit.
    'limit_title' => 'Too many attempts',
    'limit_reached' => '{1} A limit was reached. Try again in a minute.|[2,*] A limit was reached. Try again in :minutes minutes.',
];
