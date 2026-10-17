<?php

declare(strict_types=1);

/*
 * T099 — navigation nouns, from the design system's content rules.
 *
 * Nouns, never verbs: the sidebar names places, not actions. "الشعب الدراسية" rather than
 * "إدارة الشعب". The icon for each is fixed in config/icons.php §7 name map.
 */

return [
    'home' => 'الرئيسية',
    'dashboard' => 'لوحة التحكم',
    'initiatives' => 'المبادرات',
    'branches' => 'الفروع',
    'programs' => 'البرامج',
    'courses' => 'المقررات',
    'sections' => 'الشعب الدراسية',

    // The instructor's own sections. Same destination as `sections`, different name, because
    // "my" is the whole of what distinguishes an instructor's view (reference Shell.jsx NAV).
    'mysections' => 'شعبي',
    'users' => 'المستخدمون',
    'terms' => 'الفصول الدراسية',
    'imports' => 'الاستيراد',
    'audit' => 'سجل التدقيق',
    // T466 — verbatim from v2's i18n.js (`nav_support`), which adds «الفني».
    'support' => 'طلبات الدعم الفني',

    // T466 — the rest of v2's new navigation keys, verbatim from its i18n.js. `approvals` and
    // `myinitiatives` are seeded ahead of their entries, which stay absent from config/navigation.php
    // until C-22 (approvals, Phase 27 T499) and C-19 (myinitiatives, retired with the student portal)
    // are built. `studentsadmin` is v2's id for the entry this platform calls `students`; it is kept
    // under v2's name so the reference and the build share a vocabulary.
    'approvals' => 'اعتماد الشعب',
    'myinitiatives' => 'مبادراتي',
    'studentsadmin' => 'الطلبة',
    'reports' => 'التقارير',
    'settings' => 'الإعدادات',
    'students' => 'الطلبة',
    'instructors' => 'المدرسون',
    'groups' => [
        'overview' => 'نظرة عامة',
        'academic' => 'البنية الأكاديمية',
        'people' => 'المستخدمون والطلبة',
        'operations' => 'العمليات',
    ],
];
