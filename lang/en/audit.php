<?php

declare(strict_types=1);

/* The English half of the audit action labels. Key for key with lang/ar/audit.php. */

return [
    /*
    |--------------------------------------------------------------------------
    | T170 — the thirty-eight actions, exactly as contracts/audit.md lists them
    |--------------------------------------------------------------------------
    |
    | The list is closed. ActionCoverageTest treats it as an allowlist: it fails if the system emits
    | a key that is not here, and if a key arrives without a label. The specification's keys govern
    | and the design kit's labels are adopted for them — which is precisely what C-11 decided, on
    | the grounds that a key is a functional identifier the filters and five years of retained
    | records depend on, while a label is only display.
    |
    */
    'actions' => [
        'unknown' => 'Unknown action',

        'auth.login' => 'Signed in',
        'auth.logout' => 'Signed out',
        'auth.login_failed' => 'Failed sign-in',
        'auth.login_blocked' => 'Blocked after repeated attempts',
        'user.created' => 'Account created',
        'user.updated' => 'Account edited',
        'user.deactivated' => 'Account deactivated',
        'user.password_changed' => 'Password changed',
        'user.password_reset' => 'Password reset',
        'user.roles_changed' => 'Role or branches changed',
        'user.branches_changed' => 'Branches changed',
        'access.denied' => 'Access denied',
        'section.access_granted' => 'Shared access granted',
        'term.created' => 'Term created',
        'term.updated' => 'Term edited',
        'term.status_changed' => 'Term state changed',
        'term.set_current' => 'Current term set',
        'branch.created' => 'Branch created',
        'branch.updated' => 'Branch edited',
        'branch.deactivated' => 'Branch deactivated',
        'course.created' => 'Course created',
        'course.updated' => 'Course edited',
        'program.created' => 'Program created',
        'program.updated' => 'Program edited',
        'program.deactivated' => 'Program deactivated',
        'program.courses_changed' => 'Program courses changed',
        'section.created' => 'Section created',
        'section.updated' => 'Section edited',
        'section.instructor_changed' => 'Instructor changed',
        'student.created' => 'Student created',
        'student.updated' => 'Student edited',
        'enrollment.created' => 'Student enrolled',
        'enrollment.withdrawn' => 'Student withdrawn',
        'import.uploaded' => 'Import uploaded',
        'import.committed' => 'Import completed',
        'import.resumed' => 'Import resumed',
        'import.error_report_downloaded' => 'Error report downloaded',
        'audit.exported' => 'Audit log exported',
    ],

    /*
     * Field names for the export and its header block (T165).
     */
    'fields' => [
        'created_at' => 'When',
        'user' => 'User',
        'user_id' => 'User',
        'action' => 'Action',
        'entity_type' => 'Entity type',
        'entity_id' => 'Entity id',
        'ip_address' => 'IP address',
        'date_from' => 'From',
        'date_to' => 'To',
        'branch_id' => 'Branch',
    ],

    'export' => [
        'title' => 'Audit log — export',
        'generated_at' => 'Generated at',
        'requested_by' => 'Requested by',
        'filters' => 'Filters applied',
        'no_filters' => 'No filters — the whole log within the scope of whoever asked',
    ],

    'subtitle' => 'Every sensitive change, who made it and when',
    'empty' => 'No entries match these filters.',
    'details' => 'Details',
    'before' => 'Before',
    'after' => 'After',

    /*
     * FR-031 — always shown, never dismissible. A guarantee nobody is told about is a guarantee
     * nobody acts on: someone looking at an entry that is wrong needs to know the answer is a
     * correcting action, not an edit, before they go hunting for an edit button that cannot exist.
     */
    'immutable_notice' => 'Entries in this log cannot be edited or deleted. Correcting anything means a new action, which is itself recorded.',

    'entities' => [
        'User' => 'User',
        'Branch' => 'Branch',
        'AcademicTerm' => 'Term',
        'Course' => 'Course',
        'Program' => 'Program',
        'Section' => 'Section',
        'Student' => 'Student',
        'SectionEnrollment' => 'Enrollment',
        'ImportJob' => 'Import',
    ],

    'activity' => [
        'caption' => 'This record’s history',
        'empty_title' => 'No activity visible to you',
        /*
         * "visible to you", not "no activity". Every record carries at least its creation entry, so
         * an empty panel usually means the entries are outside the reader's scope rather than that
         * nothing happened.
         */
        'empty_message' => 'There are no entries on this record within your scope.',
        'see_all' => 'See the full log',
    ],
];
