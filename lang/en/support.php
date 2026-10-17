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
        'status' => 'No request matches this reference number and University ID. Check both and try again.',
        'challenge_unavailable' => 'The service is temporarily unavailable. Please try again shortly.',
        // One message for an unsolved or replayed challenge, a filled honeypot, a form sent too fast.
        'challenge' => 'The security check did not complete. Reload the page and try again.',
    ],

    // T398 — the form (US9).
    'form' => [
        'title' => 'Request support',
        'lead' => 'Describe the problem you are having and the support team will follow it up. You will receive a reference number to track your request.',
        'university_id' => 'University ID',
        'university_id_hint' => 'Your University ID as it appears on your record.',
        'category' => 'Type of problem',
        'category_placeholder' => 'Choose the type of problem',
        'description' => 'Description',
        'description_count' => ':count of :max characters',
        'contact' => 'Contact (optional)',
        'contact_hint' => 'An email or phone number you type yourself, if you would like us to contact you.',
        'honeypot' => 'Leave this field empty',
        'submit' => 'Send request',
        'challenge_working' => 'Checking that the sender is a person, not a program…',
        'challenge_ready' => 'Check complete. You can send your request.',
        'challenge_failed' => 'The check could not be completed. Reload the page and try again.',
        'needs_javascript' => 'This form needs JavaScript turned on to complete its security check.',
    ],

    // T396 — the confirmation.
    'submitted' => [
        'title' => 'Your request has been received',
        'reference_label' => 'Reference number',
        'keep_it' => 'Keep this number. You will need it, with your University ID, to check on your request, and it will not be shown again.',
        'check_status' => 'Check its status',
        'new_request' => 'Send another request',
    ],

    // T397 — the refusal page.
    'refused' => [
        'title' => 'Your request could not be sent',
        'try_again' => 'Back to the form',
    ],

    // T422 — the staff side (US10).
    'staff' => [
        'title' => 'Support requests',
        'subtitle' => 'Requests from students, longest waiting first.',
        'detail_title' => 'Support request :reference',
        'empty' => 'No requests match this search.',
        'fields' => [
            'reference' => 'Reference',
            'student' => 'Student',
            'university_id' => 'University ID',
            'branch' => 'Branch',
            'category' => 'Type',
            'status' => 'Status',
            'assignee' => 'Assigned to',
            'waiting' => 'Waiting',
            'submitted_at' => 'Submitted',
            'description' => "The student's description",
            'contact' => 'Contact',
            'term' => 'Term',
            'ip' => 'Submitted from',
            'rejection_reason' => 'Reason for rejection',
        ],
        'filters' => [
            'status' => 'Status',
            'category' => 'Type',
            'branch' => 'Branch',
            'assignee' => 'Assigned to',
            'date_from' => 'From',
            'date_to' => 'To',
            'q' => 'Search',
            'q_placeholder' => 'Reference, University ID or name',
            'any' => 'All',
        ],
        // Phase 24 (T447) — joining a list of names, and a waiting time in words, in this language.
        'list_separator' => ', ',
        'duration' => [
            'days' => '{1} :count day|[2,*] :count days',
            'hours' => '{1} :count hour|[2,*] :count hours',
            'minutes' => '{1} :count minute|[2,*] :count minutes',
            'join' => ', ',
            'under_a_minute' => 'under a minute',
        ],
        'unassigned' => 'Unassigned',
        'student_removed' => 'Student details removed',
        'no_contact' => 'The student gave no contact.',
        'unverified' => 'Unverified',
        'unverified_hint' => 'Typed by the student and not checked.',
        'thread' => 'Responses',
        'no_responses' => 'No response has been sent yet.',
        'history' => 'Status history',
        'showing_latest' => 'Showing the latest :shown of :total.',
        'history_by_student' => 'The student',
        'respond' => [
            'label' => 'Reply to the student',
            'hint' => 'The student sees this when they check their request. It cannot be edited once sent.',
            'submit' => 'Send reply',
            'closed' => 'This request is closed and accepts no replies.',
        ],
        'transition' => [
            'label' => 'Change status',
            'to' => 'New status',
            'submit' => 'Change status',
            'none' => 'There is no status this request can move to from here.',
        ],
        'reject' => [
            'title' => 'Reject this request',
            'impact' => 'The request ends unresolved, and the student sees the reason exactly as you write it here. A rejection cannot be undone.',
            'reason' => 'Reason for rejection',
            'reason_hint' => 'Written for the student, not as an internal note.',
            'confirm' => 'Reject request',
            'open' => 'Reject…',
        ],
        'assign' => [
            'label' => 'Assign the request',
            'submit' => 'Save assignment',
        ],
        'errors' => [
            'transition' => 'This request cannot move from “:from” to “:to”.',
            'close_forbidden' => 'Closing a request needs the close permission.',
            'resolve_needs_response' => 'A request cannot be resolved before the student has had a reply.',
            'reason_required' => 'Write the reason for rejection; the student will see it.',
            'closed' => 'This request is closed and accepts no replies.',
            'assignee_cannot_view' => 'This user cannot open the request, so assigning it to them would leave it unattended.',
        ],
    ],

    // T432 — the status page (US11).
    'status' => [
        'title' => 'Check a support request',
        'lead' => 'Enter the reference number you were given when you sent your request, and your University ID.',
        'reference' => 'Reference number',
        'reference_hint' => 'Like K7Q2-M9XV-T3X — lower case and no hyphens are fine.',
        'submit' => 'Show the request',
        'new_request' => 'Send a new request',
        'result_title' => 'Your request',
        'state' => 'Status',
        'submitted_at' => 'Submitted',
        'rejection_reason' => 'Reason for rejection:',
        'responses' => 'Replies from the support team',
        'still_handled' => 'No reply yet. Your request is still being handled, and the reply will appear here when it is sent.',
        'another' => 'Check another request',
    ],

    // FR-130: a limit was reached and when to retry — never which limit.
    'limit_title' => 'Too many attempts',
    'limit_reached' => '{1} A limit was reached. Try again in a minute.|[2,*] A limit was reached. Try again in :minutes minutes.',
];
