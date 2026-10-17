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
        'instructor_invalid' => 'Choose an active instructor of the college.',
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

    // T500 — section approval (Amendment 4). Every label names the section: an assignment's review has
    // its own words, and a bare "approved" could mean either (FR-185).
    'approval' => [
        'title' => 'Section approvals',
        'subtitle_all' => 'Section requests from every branch. Approve, return for edits, or reject.',
        'subtitle_scoped' => "Section requests from your branches' instructors. Approve, return for edits, or reject.",
        'empty' => 'No section is waiting for your decision.',
        'status' => [
            'pending' => 'Pending section approval',
            'returned' => 'Returned for edits',
            'rejected' => 'Section rejected',
            'active' => 'Section approved',
        ],
        'fields' => [
            'creator' => 'Requested by',
            'role' => 'Role',
            'waiting_since' => 'Waiting since',
            'enrollments' => 'Students',
            'reason' => 'Reason',
            'state' => 'Approval state',
        ],
        'actions' => [
            'decide' => 'Decide',
            'approve' => 'Approve section',
            'return' => 'Return for edits',
            'reject' => 'Reject request',
            'resubmit' => 'Resubmit section',
        ],
        'dialog' => [
            'title' => 'Section approval decision',
            'reason_hint' => 'Required to return or reject, at least 10 characters. The requester sees it.',
            'reject_title' => 'Reject this section request',
            'reject_body' => 'Rejection is final: the section cannot come back for approval, and its number becomes free for a new section.',
        ],
        'returned_notice' => 'This section was returned for edits: :reason',
        'rejected_notice' => 'This section request was rejected: :reason',
        'errors' => [
            'own_section' => 'You cannot decide a section you created yourself.',
            'peer_section' => 'This section is the Super Admin\'s to decide: a coordinator created it.',
            'not_creator' => 'Only the person who created the section can resubmit it.',
            'resubmit_only_returned' => 'Only a section returned for edits can be resubmitted. A rejected section is final.',
            'transition' => 'A section cannot go from “:from” to “:to”.',
            'reason_required' => 'A reason is required to return or reject.',
            'reason_short' => 'The reason is too short: at least :min characters.',
            'roster_not_permitted' => 'You cannot upload this section\'s roster. Upload it from the section page once it is assigned.',
        ],
    ],
];
