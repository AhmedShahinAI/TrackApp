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
    'actions' => [
        'unknown' => 'إجراء غير معروف',

        'auth.login' => 'تسجيل دخول',
        'auth.logout' => 'تسجيل خروج',
        'auth.login_failed' => 'محاولة دخول فاشلة',
        'auth.login_blocked' => 'منع محاولة دخول',

        'access.denied' => 'رفض وصول',

        'user.created' => 'إنشاء حساب',
        'user.updated' => 'تعديل حساب',
        'user.deactivated' => 'تعطيل حساب',
        'user.password_changed' => 'تغيير كلمة المرور',
        'user.password_reset' => 'إعادة تعيين كلمة المرور',
        'user.roles_changed' => 'تغيير الأدوار',
        'user.branches_changed' => 'تغيير الفروع',

        'branch.created' => 'إضافة فرع',
        'branch.updated' => 'تعديل فرع',
        'branch.deleted' => 'تعطيل فرع',

        'term.set_current' => 'تعيين الفصل الحالي',
        'section.instructor_changed' => 'تغيير مدرّس الشعبة',
        'section.access_granted' => 'منح وصول لشعبة',
        'enrollment.withdrawn' => 'انسحاب من شعبة',
        'import.committed' => 'تنفيذ استيراد',
    ],
];
