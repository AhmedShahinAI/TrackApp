<?php

declare(strict_types=1);

return [
    'title' => 'Courses',
    'subtitle' => 'The college catalogue',
    'fields' => [
        'code' => 'Course code',
        'name_ar' => 'Name in Arabic',
        'name_en' => 'Name in English',
        'sections' => 'Sections',
    ],
    'actions' => [
        'create' => 'New course',
    ],
    'errors' => [
        'code_taken' => 'A course with this code already exists in the college.',
    ],
    'empty' => [
        'message' => 'No courses yet.',
    ],
    'hints' => [
        'code' => 'The course code as it appears in the study plan, in Latin characters.',
    ],
];
