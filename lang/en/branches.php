<?php

declare(strict_types=1);

return [
    'title' => 'Branches',
    'subtitle' => 'The college campuses',

    'fields' => [
        'campus_name' => 'Branch name',
        'campus_name_ar' => 'Name in Arabic',
        'campus_name_en' => 'Name in English',
        'gender' => 'Gender',
        'is_active' => 'Active',
        'sections' => 'Sections',
        'students' => 'Students',
    ],

    'gender' => [
        'male' => 'Male',
        'female' => 'Female',
    ],

    'actions' => ['create' => 'New branch'],

    /*
     * Shown always, not only on an attempt: someone looking for a delete button needs to know it is
     * deliberately absent before they go hunting for it (FR-049).
     */
    'notice' => 'A branch that holds sections is deactivated, never deleted — the student records attached to it stay readable.',

    'empty' => ['message' => 'No branches yet.'],
];
