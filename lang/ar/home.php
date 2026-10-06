<?php

declare(strict_types=1);

/* T134 — the role landing page (FR-017). Arabic verbatim from the design system. */

return [
    'title' => 'الرئيسية',
    'welcome' => 'مرحبًا، :name',

    /*
     * Six KPI tiles. Each role sees only its own in-scope counts — a coordinator's "الشعب" is the
     * sections in their branches, not the college's. The labels are identical across roles because
     * the number means the same thing; only its scope differs.
     */
    'stats' => [
        'branches' => 'الفروع',
        'sections' => 'الشعب',
        'students' => 'الطلبة',
        'users' => 'المستخدمون',
        'enrollments' => 'التسجيلات',
        'imports' => 'عمليات الاستيراد',
    ],

    'recent' => [
        'title' => 'آخر النشاطات',
        'empty' => 'لا نشاط بعد.',
        'when' => 'الوقت',
        'what' => 'الإجراء',
        'who' => 'المستخدم',
    ],

    'quick' => [
        'title' => 'إجراءات سريعة',
        'new_user' => 'مستخدم جديد',
        'new_section' => 'شعبة جديدة',
        'import' => 'استيراد قائمة',
        'profile' => 'الملف الشخصي',
    ],

    'profile' => [
        'title' => 'الملف الشخصي',
        'details' => 'بيانات الحساب',
    ],
];
