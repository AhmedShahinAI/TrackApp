<?php

declare(strict_types=1);

/* T134 — the accounts screen. Arabic taken verbatim from the design system's i18n.js. */

return [
    'title' => 'المستخدمون',
    'subtitle' => 'إنشاء حسابات المنسقين والمدرسين وإدارتها',

    'create' => 'مستخدم جديد',
    'edit' => 'تعديل المستخدم',
    'created' => 'تم إنشاء الحساب',
    'saved' => 'تم حفظ التعديلات',

    'fields' => [
        'name' => 'الاسم',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'email' => 'البريد الإلكتروني المؤسسي',
        'password' => 'كلمة المرور الأولية',
        'role' => 'الدور',
        'branches' => 'الفروع',
        'status' => 'الحالة',
        'last_login' => 'آخر دخول',
        'sections' => 'الشعب',
    ],

    'roles' => [
        'SUPER_ADMIN' => 'سوبر أدمن',
        'BRANCH_COORDINATOR' => 'منسق فرع',
        'INSTRUCTOR' => 'مدرس',
    ],

    'never_signed_in' => 'لم يسجّل الدخول',

    'filters' => [
        'role' => 'الدور',
        'branch' => 'الفرع',
        'status' => 'الحالة',
        'all' => 'الكل',
        'search' => 'بحث بالاسم أو البريد',
        'sort' => 'الترتيب حسب',
    ],
    'sorts' => [
        'name' => 'الاسم',
        'last_login' => 'آخر دخول — الأقدم دخولًا أولًا',
        'last_login_desc' => 'آخر دخول — الأحدث أولًا',
    ],

    /*
     * FR-082. A high-impact action restates its consequence before it happens. Both of these name
     * the session effect, because that is the part a Super Admin does not expect — and the
     * preserved-history note is there because the opposite is what people fear.
     */
    'deactivate' => [
        'action' => 'تعطيل الحساب',
        'title' => 'تعطيل الحساب',
        'message' => 'سيتم إنهاء جميع جلسات المستخدم ومنعه من تسجيل الدخول.',
        'preserved' => 'يبقى اسمه على كل ما أنشأه؛ لا يُحذف أي سجل.',
        'confirm' => 'تعطيل',
        'done' => 'تم تعطيل الحساب',
        'self' => 'لا يمكنك تعطيل حسابك الخاص.',
    ],

    'reset_password' => [
        'action' => 'إعادة تعيين كلمة المرور',
        'title' => 'إعادة تعيين كلمة المرور',
        'message' => 'سيتم إنهاء جميع جلسات المستخدم. أبلغه كلمة المرور الجديدة بنفسك — لا يُرسل بريد.',
        'confirm' => 'إعادة التعيين',
        'done' => 'تم إعادة تعيين كلمة المرور',
    ],

    'empty' => [
        'title' => 'لا توجد حسابات مطابقة',
        'message' => 'عدّل عوامل التصفية أو أنشئ حسابًا جديدًا.',
    ],

    'errors' => [
        'email_taken' => 'هذا البريد مستخدم بحساب آخر، حتى لو كان معطّلًا.',
        'branches_required' => 'منسق الفرع يحتاج فرعًا واحدًا على الأقل.',
    ],
];
