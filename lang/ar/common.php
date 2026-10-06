<?php

declare(strict_types=1);

/*
 * T099 — shared interface wording.
 *
 * Content rules from the design system: labels are nouns, buttons are verbs, and an error says what
 * to do rather than what went wrong. Nothing here is a sentence about the system ("an error
 * occurred"); every message is addressed to the person reading it.
 */

return [
    'actions' => [
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'create' => 'إضافة',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'close' => 'إغلاق',
        'confirm' => 'تأكيد',
        'search' => 'بحث',
        'filter' => 'تصفية',
        'export' => 'تصدير',
        'import' => 'استيراد',
        'download' => 'تنزيل',
        'upload' => 'رفع',
        'retry' => 'إعادة المحاولة',
        'back' => 'رجوع',
        'next' => 'التالي',
        'previous' => 'السابق',
        'logout' => 'تسجيل الخروج',
        'dismiss' => 'إخفاء',
        'more' => 'المزيد',
    ],

    'labels' => [
        'required' => 'مطلوب',
        'optional' => 'اختياري',
        'loading' => 'جارٍ التحميل',
        'notAssigned' => 'غير مُعيَّن',
        'none' => 'لا شيء',
        'all' => 'الكل',
        'of' => 'من',
        'results' => 'نتيجة',
        'page' => 'صفحة',
        'lastLogin' => 'آخر دخول',
        'never' => 'لم يسجّل دخولًا',
        'language' => 'اللغة',
        'profile' => 'الملف الشخصي',
        'notifications' => 'التنبيهات',
        'help' => 'المساعدة',
        'menu' => 'القائمة',
        'skipToContent' => 'تجاوز إلى المحتوى',
    ],

    'empty' => [
        'title' => 'لا توجد بيانات',
        'message' => 'لم يُضَف أي سجل بعد.',
        'noResults' => 'لا نتائج مطابقة',
        'noResultsMessage' => 'جرّب تعديل كلمات البحث أو إزالة بعض عوامل التصفية.',
    ],

    'errors' => [
        'forbidden' => 'لا تملك صلاحية الوصول',
        'forbiddenMessage' => 'هذه الصفحة تحتاج صلاحية ليست ممنوحة لحسابك. راجع مسؤول النظام إن كنت تحتاجها.',
        'notFound' => 'الصفحة غير موجودة',
        'notFoundMessage' => 'الرابط غير صحيح أو أن السجل لم يعد متاحًا.',
        'generic' => 'تعذّر إكمال العملية',
        'genericMessage' => 'أعد المحاولة. إن تكرّر الأمر فأبلغ الدعم الفني.',
    ],

    'queue' => [
        'waiting' => 'العملية في الانتظار',
        'waitingMessage' => 'المعالجة في الخلفية غير متاحة حاليًا. العملية محفوظة وسيتم إبلاغك عند انتهائها.',
    ],

    'validation' => [
        'inSetting' => 'القيمة المختارة غير مسموح بها.',
    ],
];
