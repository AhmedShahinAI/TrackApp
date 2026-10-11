<?php

declare(strict_types=1);

return [
    'title' => 'البرامج الأكاديمية',
    'subtitle' => 'كل شعبة في الكلية مرتبطة ببرنامج أكاديمي واحد.',
    'fields' => [
        'code' => 'الرمز',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'unit' => 'الوحدة التخصصية',
        'courses' => 'المقررات',
        'sections' => 'الشعب',
        'is_active' => 'فعّال',
    ],
    'filters' => [
        'search' => 'الرمز أو الاسم',
    ],
    'units' => [
        'admin' => 'البرامج الإدارية',
        'tech' => 'البرامج التقنية',
    ],
    'actions' => [
        'create' => 'برنامج جديد',
        'edit' => 'تعديل البرنامج',
        'import_courses' => 'استيراد المقررات من Excel',
    ],
    // FR-092, said where someone would otherwise look for a delete button.
    'notice' => 'لا يمكن حذف برنامج مرتبط بشعب؛ يمكن إيقافه بدلاً من ذلك.',
    'empty' => [
        'message' => 'لا توجد برامج أكاديمية بعد.',
        'filtered' => 'لا يوجد برنامج يطابق هذا البحث.',
    ],
    'hints' => [
        'code' => 'بالأحرف اللاتينية، ويُحفظ بالأحرف الكبيرة.',
    ],
    'errors' => [
        'code_taken' => 'الرمز :code مستخدم لبرنامج آخر في الكلية.',
        'code_locked' => 'لا يمكن تغيير رمز هذا البرنامج لأنه يضم شعباً؛ فالتقارير وملفات عمادة القبول تشير إليه بهذا الرمز.',
        // FR-092 - the platform says so when removal is attempted.
        'has_sections' => 'لا يمكن حذف هذا البرنامج لأنه يضم شعباً. يمكنك تعطيله بدلاً من ذلك.',
    ],
];
