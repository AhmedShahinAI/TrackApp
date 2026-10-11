<?php

declare(strict_types=1);

return [
    'title' => 'Academic programs',
    'subtitle' => 'Every section in the college belongs to one academic program.',
    'fields' => [
        'code' => 'Code',
        'name_ar' => 'Name in Arabic',
        'name_en' => 'Name in English',
        'unit' => 'Specialty unit',
        'courses' => 'Courses',
        'sections' => 'Sections',
        'is_active' => 'Active',
    ],
    'filters' => [
        'search' => 'Code or name',
    ],
    'units' => [
        'admin' => 'Administrative',
        'tech' => 'Technical',
    ],
    'actions' => [
        'create' => 'New program',
        'edit' => 'Edit program',
        'import_courses' => 'Import courses from Excel',
    ],
    // FR-092, said where someone would otherwise look for a delete button.
    'notice' => 'Programs linked to sections cannot be deleted; deactivate instead.',
    'empty' => [
        'message' => 'No academic programs yet.',
        'filtered' => 'No program matches this search.',
    ],
    'hints' => [
        'code' => 'Latin letters, stored in capitals.',
    ],
    'errors' => [
        'code_taken' => 'The code :code is already used by another program in the college.',
        'code_locked' => 'This program holds sections, so its code cannot be changed. Reports and registrar files name it by this code.',
        // FR-092 - the platform says so when removal is attempted.
        'has_sections' => 'This programme holds sections, so it cannot be removed. Deactivate it instead.',
    ],
];
