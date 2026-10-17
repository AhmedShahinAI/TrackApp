<?php

declare(strict_types=1);

return [
    'title' => 'الطلبة',
    'subtitle' => 'سجلّات الطلبة وتسجيلاتهم',

    // T468 — v2's StudentsAdminScreen, verbatim, without its sentence about student accounts (C-19).
    'subtitle_all' => 'جميع الطلبة.',
    'subtitle_scoped' => 'الطلبة المسجلون في الشعب ضمن نطاق صلاحيتك للفصل الحالي.',
    'count' => ':count طالب',
    'list_separator' => '، ',
    'filters' => [
        'q' => 'بحث',
        // v2's placeholder also names the email and the phone, which students do not have (C-23).
        'q_placeholder' => 'الاسم أو الرقم الجامعي',
        'branch' => 'الفرع',
        'section' => 'الشعبة',
        'program' => 'البرنامج',
        'all_branches' => 'كل الفروع',
        'all_sections' => 'كل الشعب',
        'all_programs' => 'كل البرامج',
        'sections_truncated' => 'تُعرض أول :count شعبة فقط. اختر الفرع لتضييق القائمة.',
    ],
    'fields' => [
        'university_id' => 'الرقم الجامعي',
        'name' => 'الاسم',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'enrollments' => 'التسجيلات',
        'section' => 'الشعبة',
        'course' => 'المقرر',
        'branch' => 'الفرع',
        'status' => 'الحالة',
        // FR-110, FR-111 — the registrar's own values, kept apart from TRACK's status above.
        'academic_status' => 'الحالة الأكاديمية',
        'roster_sequence' => 'تسلسل',
        'enrolled_at' => 'تاريخ التسجيل',
        'withdrawn_at' => 'تاريخ الانسحاب',
        'withdraw_reason' => 'سبب الانسحاب',
        // T468, from v2's column labels.
        'program' => 'البرنامج',
        'degree' => 'الدرجة',
        'activity' => 'النشاط',
        'sections' => 'الشعب',
        'open_requests' => 'طلبات مفتوحة',
    ],
    'actions' => [
        'enroll' => 'تسجيل طالب',
        'withdraw' => 'سحب من الشعبة',
    ],
    'errors' => [
        'already_enrolled' => 'هذا الطالب مُسجَّل في هذه الشعبة بالفعل.',
        'already_withdrawn' => 'هذا التسجيل مسحوب بالفعل.',
        'reason_required' => 'سبب الانسحاب مطلوب.',
    ],
    'warnings' => [
        'name_mismatch' => 'الاسم المحفوظ لهذا الرقم الجامعي «:stored» ويختلف عمّا أُدخل «:given». حُفظ الاسم الأصلي.',
        'restored' => 'كان هذا الطالب محذوفاً وأُعيد.',
    ],
    'confirm' => [
        'withdraw_title' => 'سحب الطالب من الشعبة',
        'withdraw_body' => 'سيبقى سجلّ الطالب وتسجيلاته الأخرى كما هي. السبب يُحفظ في سجل التدقيق.',
    ],
    'empty' => [
        'message' => 'لا يوجد طلبة ضمن نطاقك.',
        'enrollments' => 'لا توجد تسجيلات ظاهرة لك لهذا الطالب.',
    ],
    'tabs' => [
        'enrollments' => 'التسجيلات',
        'activity' => 'السجل',
    ],
    'hints' => [
        'university_id' => 'إن كان الرقم مسجّلاً مسبقاً فسيُربط الطالب نفسه ولن يُنشأ سجلّ ثانٍ.',
        'withdraw_reason' => 'السبب مطلوب، ويُحفظ في سجل التدقيق باسمك.',
    ],
];
