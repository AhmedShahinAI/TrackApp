<?php

declare(strict_types=1);

return [
    'title' => 'Academic terms',
    'subtitle' => 'Create terms and manage their status',
    'fields' => [
        'name' => 'Term name',
        'name_ar' => 'Name in Arabic',
        'name_en' => 'Name in English',
        'academic_year' => 'Academic year',
        'semester' => 'Semester',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'status' => 'Status',
        'sections' => 'Sections',
        'current' => 'Current term',
    ],
    'semesters' => [
        'first' => 'First',
        'second' => 'Second',
        'summer' => 'Summer',
    ],
    'actions' => [
        'create' => 'New term',
        'change_status' => 'Change status',
        'set_current' => 'Set as current',
    ],
    'errors' => [
        'transition_not_permitted' => 'A term cannot move from ":from" to ":to".',
        'reason_required' => 'Changing the status of a closed term requires a reason.',
    ],
    'confirm' => [
        'set_current_title' => 'Set the current term',
        'set_current_body' => 'The previous current term will be cleared, and this one becomes the term every screen uses.',
        'transition' => 'Move the term ":term" from ":from" to ":to".',
    ],
    'empty' => [
        'message' => 'No academic terms yet. Start by creating one.',
    ],
    'hints' => [
        'reason' => 'The reason is written to the audit log and cannot be edited afterwards.',
    ],
];
