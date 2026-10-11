<?php

declare(strict_types=1);

return [
    'title' => 'Sections',
    'subtitle' => 'Sections within your scope',
    'unassigned' => 'Unassigned',
    'placeholders' => [
        'program' => 'Select program…',
    ],
    'fields' => [
        'number' => 'Section number',
        'course' => 'Course',
        'branch' => 'Branch',
        'gender' => 'Gender',
        'term' => 'Term',
        'instructor' => 'Instructor',
        'enrollments' => 'Enrolled',
        'access' => 'Access',
        'program' => 'Academic programme',
        'degree' => 'Degree',
        'activity' => 'Activity',
    ],
    'gender' => [
        'male' => 'Male',
        'female' => 'Female',
    ],
    'tabs' => [
        'details' => 'Details',
        'activity' => 'History',
        'roster' => 'Roster',
        'history' => 'Instructor history',
    ],
    'detail' => [
        'title' => 'Section :number',
    ],
    'empty' => [
        'message' => 'There are no sections within your scope yet.',
        'instructor' => 'No sections have been assigned to you yet. Contact your branch coordinator or the system administrator to have your sections assigned.',
        'roster' => 'No students are enrolled in this section yet.',
        'history' => 'This section has never been reassigned.',
    ],
    'errors' => [
        'duplicate_key' => 'A section with this number already exists for this course, branch, and term.',
        'term_required_first' => 'A section cannot be created before an academic term exists. Start by adding a term.',
        'program_required' => 'An academic programme is required, and it must be an active programme of your organization.',
        'instructor_via_own_endpoint' => 'The instructor is not changed from here — use the "Change instructor" action, which is the only path that records the handover in the instructor history.',
        'branch_not_allowed' => 'You may not create a section in this branch.',
    ],
    'actions' => [
        'create' => 'New section',
        'change_instructor' => 'Change instructor',
        'import_list' => 'Import list',
    ],
    'history' => [
        'current' => 'Present',
        'from' => 'From',
        'to' => 'To',
        'changed_by' => 'Changed by',
    ],
    'confirm' => [
        'change_instructor_body' => 'Access moves to the new instructor from now on. Existing records — enrollments, withdrawals and their reasons — keep the name of whoever performed them and are not reattributed.',
        'current_instructor' => 'Current instructor: :name',
    ],
];
