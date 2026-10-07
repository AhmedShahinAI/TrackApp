<?php

declare(strict_types=1);

return [
    'title' => 'Sections',
    'subtitle' => 'Sections within your scope',
    'unassigned' => 'Unassigned',

    'fields' => [
        'number' => 'Section number',
        'course' => 'Course',
        'branch' => 'Branch',
        'gender' => 'Gender',
        'term' => 'Term',
        'instructor' => 'Instructor',
        'enrollments' => 'Enrolled',
        'access' => 'Access',
    ],

    // FR-050 — read through the branch; a section has no gender column of its own.
    'gender' => [
        'male' => 'Male',
        'female' => 'Female',
    ],

    'tabs' => [
        'details' => 'Details',
        'activity' => 'History',
    ],

    'detail' => [
        'title' => 'Section :number',
    ],

    'empty' => [
        'message' => 'There are no sections within your scope yet.',
    ],
];
