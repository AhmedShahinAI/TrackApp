<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| رسائل التحقّق
|--------------------------------------------------------------------------
|
| كانت هذه الصفحة تحتوي القواعد الخاصة بالمنصّة وحدها، وتركت رسائل Laravel الافتراضية
| بالإنجليزية — فكانت صفحة الدخول العربية تقول "The email field is required" (T263، FR-078).
| صارت تحتوي رسالة كل قاعدة تستعملها المنصّة، وأسماء الحقول كما يقرؤها المستخدم.
|
*/

return [
    'accepted' => 'يجب تأكيد :attribute.',
    'after' => 'يجب أن يكون :attribute بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute في :date أو بعده.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'before' => 'يجب أن يكون :attribute قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute في :date أو قبله.',
    'boolean' => 'يجب أن تكون قيمة :attribute نعم أو لا.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور الحالية غير صحيحة.',
    'date' => ':attribute ليس تاريخاً صحيحاً.',
    'different' => 'يجب أن يختلف :attribute عن :other.',
    'digits' => 'يجب أن يتكوّن :attribute من :digits أرقام.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صحيحاً.',
    'exists' => 'القيمة المختارة في :attribute غير موجودة.',
    'file' => 'يجب أن يكون :attribute ملفاً.',
    'in' => 'القيمة المختارة في :attribute غير مسموح بها.',
    // InSetting (T304): قيمة تُطابَق مع قائمة يديرها مدير النظام في الإعدادات.
    'in_setting' => 'القيمة المختارة في :attribute ليست من الخيارات المعتمدة.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'max' => [
        'array' => 'يجب ألّا يحتوي :attribute على أكثر من :max عنصراً.',
        'file' => 'يجب ألّا يزيد حجم :attribute على :max كيلوبايت.',
        'numeric' => 'يجب ألّا يزيد :attribute على :max.',
        'string' => 'يجب ألّا يزيد :attribute على :max حرفاً.',
    ],
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عنصر على الأقل.',
        'file' => 'يجب ألّا يقل حجم :attribute عن :min كيلوبايت.',
        'numeric' => 'يجب ألّا يقل :attribute عن :min.',
        'string' => 'يجب ألّا يقل :attribute عن :min أحرف.',
    ],
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'present' => 'يجب إرسال :attribute.',
    'prohibited' => ':attribute غير مسموح به هنا.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => ':attribute مطلوب.',
    'required_if' => ':attribute مطلوب عندما يكون :other :value.',
    'required_with' => ':attribute مطلوب عند إدخال :values.',
    'same' => 'يجب أن يطابق :attribute :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عنصراً.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute مساوياً لـ :size.',
        'string' => 'يجب أن يتكوّن :attribute من :size حرفاً.',
    ],
    'string' => 'يجب أن يكون :attribute نصاً.',
    'unique' => 'هذه القيمة في :attribute مستخدمة من قبل.',
    'url' => 'صيغة :attribute غير صحيحة.',

    /*
     * T183 — الرسالة تذكر الصيغة المتوقّعة، لأن «صيغة غير صحيحة» لا تقول شيئاً لمسجّل ينظر إلى
     * خلية في جدول. والصيغة مشتقّة من النمط المُهيَّأ نفسه، فتبقى صادقة إذا تغيّر الإعداد.
     */
    'university_id' => 'الرقم الجامعي غير صحيح. الصيغة المتوقّعة: :format.',
    'university_id_digits' => ':count أرقام',

    /*
     * أسماء الحقول كما يقرؤها المستخدم. بدونها تظهر الرسالة باسم الحقل البرمجي:
     * «name_ar مطلوب».
     */
    'attributes' => [
        'academic_year' => 'العام الدراسي',
        'activity' => 'النشاط',
        'branch_id' => 'الفرع',
        'branch_ids' => 'الفروع',
        'campus_name_ar' => 'اسم المقر بالعربية',
        'campus_name_en' => 'اسم المقر بالإنجليزية',
        'code' => 'الرمز',
        'confirm' => 'التأكيد',
        'course_id' => 'المقرر',
        'current_password' => 'كلمة المرور الحالية',
        'degree' => 'الدرجة',
        'email' => 'البريد الإلكتروني',
        'end_date' => 'تاريخ النهاية',
        'file' => 'الملف',
        'gender' => 'النوع',
        'instructor_id' => 'المدرّس',
        'locale' => 'اللغة',
        'mapping' => 'مطابقة الأعمدة',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'options' => 'الخيارات',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'program_id' => 'البرنامج',
        'reason' => 'السبب',
        'role' => 'الدور',
        'roles' => 'الأدوار',
        'section_number' => 'رقم الشعبة',
        'semester' => 'الفصل',
        'start_date' => 'تاريخ البداية',
        'status' => 'الحالة',
        'term_id' => 'الفصل الدراسي',
        'university_id' => 'الرقم الجامعي',
        'user_id' => 'المستخدم',
    ],
];
