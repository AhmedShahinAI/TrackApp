<?php

declare(strict_types=1);

/*
 * Audit action labels.
 *
 * Beyond T134's list (auth, users, profile, home) but required by it: the home screen's
 * recent-activity table renders `action_label`, which contracts/audit.md says is "localized
 * server-side" while the raw `action` key stays stable for filtering. Without this file the table
 * would print `auth.login` to a user.
 *
 * Only the keys Phase 4 can emit are filled in. The remaining 30-odd from contracts/audit.md arrive
 * with the phases that emit them; `actions.unknown` is the fallback so a new key degrades to a
 * readable sentence rather than to its own name.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | T170 — الأفعال الثمانية والثلاثون، حرفياً كما في contracts/audit.md
    |--------------------------------------------------------------------------
    |
    | القائمة مغلقة: ActionCoverageTest يعاملها قائمةَ سماح، فيفشل إذا أصدر النظام مفتاحاً خارجها
    | أو إذا وصل مفتاح بلا تسمية. مفاتيح المواصفة هي الحاكمة، والتسميات العربية مأخوذة من
    | مجموعة التصميم (`act.*`) — هذا بالضبط ما قرّره C-11: المفاتيح معرّفات وظيفية تعتمد عليها
    | المرشّحات والسجلات المحفوظة خمس سنوات، والتسميات عرضٌ فقط.
    |
    | وستّة منها تحمل اسماً في المرجع يخالف اسم العقد (signed_in / failed / current_set /
    | state_changed / role_changed / completed). العقد هو المكتوب هنا؛ تسمية المرجع هي القيمة.
    |
    */
    'actions' => [
        'unknown' => 'إجراء غير معروف',

        'auth.login' => 'تسجيل دخول',
        'auth.logout' => 'تسجيل خروج',
        'auth.login_failed' => 'محاولة دخول فاشلة',
        'auth.login_blocked' => 'حجب بعد محاولات متكررة',
        'user.created' => 'إنشاء حساب',
        'user.updated' => 'تعديل حساب',
        'user.deactivated' => 'تعطيل حساب',
        'user.password_changed' => 'تغيير كلمة المرور',
        'user.password_reset' => 'إعادة تعيين كلمة المرور',
        'user.roles_changed' => 'تغيير الدور أو الفروع',
        'user.branches_changed' => 'تغيير الفروع',
        'access.denied' => 'رفض وصول',
        'section.access_granted' => 'منح وصول مشترك',
        'term.created' => 'إنشاء فصل',
        'term.updated' => 'تعديل فصل',
        'term.status_changed' => 'تغيير حالة فصل',
        'term.set_current' => 'تعيين الفصل الحالي',
        'branch.created' => 'إنشاء فرع',
        'branch.updated' => 'تعديل فرع',
        'branch.deactivated' => 'تعطيل فرع',
        'course.created' => 'إنشاء مقرر',
        'course.updated' => 'تعديل مقرر',
        'program.created' => 'إنشاء برنامج',
        'program.updated' => 'تعديل برنامج',
        'program.deactivated' => 'تعطيل برنامج',
        'program.courses_changed' => 'تعديل مقررات البرنامج',
        'section.created' => 'إنشاء شعبة',
        'section.updated' => 'تعديل شعبة',
        'section.instructor_changed' => 'تغيير مدرس الشعبة',
        'student.created' => 'إنشاء طالب',
        'student.updated' => 'تعديل بيانات طالب',
        'enrollment.created' => 'تسجيل طالب في شعبة',
        'enrollment.withdrawn' => 'سحب طالب',
        'import.uploaded' => 'رفع ملف استيراد',
        'import.committed' => 'اكتمال استيراد',
        'import.resumed' => 'استئناف استيراد',
        'import.error_report_downloaded' => 'تنزيل تقرير أخطاء الاستيراد',
        'audit.exported' => 'تصدير سجل التدقيق',
    ],

    /*
     * أسماء الحقول في التصدير وكتلة ترويسته (T165).
     */
    'fields' => [
        'created_at' => 'الوقت',
        'user' => 'المستخدم',
        'user_id' => 'المستخدم',
        'action' => 'الإجراء',
        'entity_type' => 'نوع الكيان',
        'entity_id' => 'معرّف الكيان',
        'ip_address' => 'عنوان IP',
        'date_from' => 'من تاريخ',
        'date_to' => 'إلى تاريخ',
        'branch_id' => 'الفرع',
    ],

    'export' => [
        'title' => 'سجل التدقيق — تصدير',
        'generated_at' => 'تاريخ الإصدار',
        'requested_by' => 'طلبه',
        'filters' => 'عوامل التصفية المطبّقة',
        'no_filters' => 'بلا تصفية — السجل كاملاً ضمن نطاق الطالب',
    ],

    'subtitle' => 'كل تغيير حسّاس، ومَن أجراه ومتى',
    'empty' => 'لا مدخلات مطابقة لهذه التصفية.',
    'details' => 'التفاصيل',
    'before' => 'قبل',
    'after' => 'بعد',

    /*
     * FR-031 — يُعرض دائماً ولا يُخفى. ضمانة لا يعلم بها أحد ضمانةٌ لا يبني عليها أحد: من يرى
     * مدخلاً خاطئاً عليه أن يعرف أن العلاج مدخل مصحِّح لا تعديل، قبل أن يبحث عن زرّ تعديل لا وجود له.
     */
    'immutable_notice' => 'مدخلات هذا السجل لا تُعدَّل ولا تُحذف. تصحيح أي معلومة يكون بإجراء جديد يُسجَّل بدوره.',

    'entities' => [
        'User' => 'مستخدم',
        'Branch' => 'فرع',
        'AcademicTerm' => 'فصل دراسي',
        'Course' => 'مقرر',
        'Program' => 'برنامج',
        'Section' => 'شعبة',
        'Student' => 'طالب',
        'SectionEnrollment' => 'تسجيل',
        'ImportJob' => 'عملية استيراد',
    ],

    'activity' => [
        'caption' => 'سجل هذا السجلّ',
        'empty_title' => 'لا نشاط ظاهر لك',
        /*
         * «ظاهر لك» لا «لا نشاط». كل سجلّ يحمل مدخل إنشائه على الأقل، فالفراغ هنا يعني غالباً أن
         * المدخلات خارج نطاق القارئ — لا أن شيئاً لم يحدث.
         */
        'empty_message' => 'لا توجد مدخلات على هذا السجلّ ضمن نطاقك.',
        'see_all' => 'عرض السجل كاملاً',
    ],
];
