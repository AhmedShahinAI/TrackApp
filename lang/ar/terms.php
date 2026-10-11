<?php

declare(strict_types=1);

return [
    'title' => 'الفصول الدراسية',
    'subtitle' => 'إنشاء الفصول وإدارة حالاتها',
    'fields' => [
        'name' => 'اسم الفصل',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'academic_year' => 'العام الجامعي',
        'semester' => 'الفصل',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'status' => 'الحالة',
        'sections' => 'الشعب',
        'current' => 'الفصل الحالي',
    ],
    'semesters' => [
        'first' => 'الأول',
        'second' => 'الثاني',
        'summer' => 'الصيفي',
    ],
    'actions' => [
        'create' => 'فصل جديد',
        'change_status' => 'تغيير الحالة',
        'set_current' => 'تعيين كفصل حالي',
    ],
    'errors' => [
        'transition_not_permitted' => 'لا يمكن نقل الفصل من حالة «:from» إلى «:to».',
        'reason_required' => 'تغيير حالة فصل مغلق يتطلّب ذكر السبب.',
    ],
    'confirm' => [
        'set_current_title' => 'تعيين الفصل الحالي',
        'set_current_body' => 'سيُلغى تعيين الفصل الحالي السابق، ويصبح هذا الفصل هو المعتمد في كل الشاشات.',
        'transition' => 'نقل الفصل «:term» من حالة «:from» إلى «:to».',
    ],
    'empty' => [
        'message' => 'لا توجد فصول دراسية بعد. ابدأ بإنشاء فصل.',
    ],
    'hints' => [
        'reason' => 'يُحفظ السبب في سجل التدقيق ولا يمكن تعديله بعد ذلك.',
    ],
];
