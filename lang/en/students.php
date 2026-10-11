<?php

declare(strict_types=1);

return [
    'title' => 'Students',
    'subtitle' => 'Student records and their enrollments',
    'fields' => [
        'university_id' => 'University ID',
        'name' => 'Name',
        'name_ar' => 'Name in Arabic',
        'name_en' => 'Name in English',
        'enrollments' => 'Enrollments',
        'section' => 'Section',
        'course' => 'Course',
        'branch' => 'Branch',
        'status' => 'Status',
        // FR-110, FR-111 — the registrar's own values, kept apart from TRACK's status above.
        'academic_status' => 'Academic status',
        'roster_sequence' => 'Seq.',
        'enrolled_at' => 'Enrolled',
        'withdrawn_at' => 'Withdrawn',
        'withdraw_reason' => 'Reason',
    ],
    'actions' => [
        'enroll' => 'Enroll a student',
        'withdraw' => 'Withdraw from section',
    ],
    'errors' => [
        'already_enrolled' => 'This student is already enrolled in this section.',
        'already_withdrawn' => 'This enrollment has already been withdrawn.',
        'reason_required' => 'A reason for the withdrawal is required.',
    ],
    'warnings' => [
        'name_mismatch' => 'The stored name for this university ID is ":stored", which differs from ":given". The stored name was kept.',
        'restored' => 'This student had been deleted and was restored.',
    ],
    'confirm' => [
        'withdraw_title' => 'Withdraw the student from this section',
        'withdraw_body' => 'The student record and their other enrollments are untouched. The reason is kept in the audit log.',
    ],
    'empty' => [
        'message' => 'No students within your scope.',
        'enrollments' => 'No enrollments for this student are visible to you.',
    ],
    'tabs' => [
        'enrollments' => 'Enrollments',
        'activity' => 'Activity',
    ],
    'hints' => [
        'university_id' => 'An id that already exists links the same student; a second record is never created.',
        'withdraw_reason' => 'The reason is required and is written to the audit log under your name.',
    ],
];
