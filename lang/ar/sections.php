<?php

declare(strict_types=1);

/*
 * نصوص شاشات الشُّعَب — ما تحتاجه US2 فقط.
 *
 * الأسماء مأخوذة من مرجع SectionsScreens.jsx. بقية النصوص (الإنشاء، التعديل، إسناد المدرّس،
 * الاعتماد) تأتي مع US4 والتعديل الرابع.
 */

return [
    'title' => 'الشعب الدراسية',
    'subtitle' => 'الشعب ضمن نطاقك',
    'unassigned' => 'غير مُعيَّن',

    'fields' => [
        'number' => 'رقم الشعبة',
        'course' => 'المقرر',
        'branch' => 'الفرع',
        'gender' => 'الجنس',
        'term' => 'الفصل',
        'instructor' => 'المدرّس',
        'enrollments' => 'المسجّلون',
        'access' => 'الوصول',
    ],

    // FR-050 — read through the branch; a section has no gender column of its own.
    'gender' => [
        'male' => 'طلاب',
        'female' => 'طالبات',
    ],

    'tabs' => [
        'details' => 'التفاصيل',
        'activity' => 'السجل',
    ],

    'detail' => [
        'title' => 'الشعبة :number',
    ],

    'empty' => [
        'message' => 'لا توجد شعب ضمن نطاقك حالياً.',
    ],
];
