<?php

declare(strict_types=1);

/*
 * T134 — sign-in and password copy.
 *
 * Every Arabic string here is taken **verbatim** from design-system/ui_kits/web-app/i18n.js. The
 * design system is the authority on the copy register (design-system.md §12), and paraphrasing it
 * is how a platform ends up with three different ways of saying the same refusal. Where a key name
 * differs from the kit's, the kit's name is noted beside it.
 */

return [
    'title' => 'تسجيل الدخول',
    'subtitle' => 'ادخل بريدك المؤسسي وكلمة المرور',
    'email' => 'البريد الإلكتروني المؤسسي',
    'password' => 'كلمة المرور',
    'sign_in' => 'تسجيل الدخول',
    'sign_out' => 'تسجيل الخروج',

    /*
     * FR-009. One message for a wrong email and a wrong password alike. The wording never says
     * which was wrong, because "no account with that email" tells an attacker which addresses are
     * worth attacking.
     */
    'failed' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',

    // FR-010. :seconds is substituted from the throttle's retry_after.
    'locked' => 'محاولات كثيرة. حاول مجددًا بعد :seconds ثانية.',

    // FR-011. Directs the person to the administrator, because there is nothing they can do alone.
    'deactivated' => 'تم تعطيل هذا الحساب. يرجى التواصل مع مسؤول النظام.',

    // FR-001. Stated on the screen, not merely enforced by a missing route.
    'no_registration' => 'لا يوجد تسجيل ذاتي — يُنشئ مسؤول النظام جميع الحسابات.',
    'support_link' => 'طالب وتواجه مشكلة؟ تواصل مع الدعم',

    'tagline_ar' => 'معًا .. نحو أثر أكاديمي أكبر',
    'tagline_en' => 'Track Today, a Brighter Tomorrow',

    // FR-012. A recommendation, not a demand — the banner is dismissable and blocks nothing.
    'first_login' => [
        'title' => 'يُنصح بتغيير كلمة المرور الأولية',
        'message' => 'أنت تستخدم كلمة المرور التي أنشأها المسؤول. يمكنك تغييرها الآن أو لاحقًا من ملفك الشخصي.',
        'action' => 'تغيير كلمة المرور',
    ],

    'password_change' => [
        'title' => 'تغيير كلمة المرور',
        'current' => 'كلمة المرور الحالية',
        'new' => 'كلمة المرور الجديدة',
        'confirm' => 'تأكيد كلمة المرور',
        'rule' => '12 حرفًا على الأقل، تتضمن حرفًا ورقمًا.',
        'required' => 'عيّن مسؤول النظام كلمة مرورك. اختر كلمة مرور خاصة بك للمتابعة.',
        'changed' => 'تم تغيير كلمة المرور',
        'changed_message' => 'تم إنهاء جلساتك الأخرى.',
    ],

    'errors' => [
        'policy' => 'يجب أن تكون 12 حرفًا على الأقل وتتضمن حرفًا ورقمًا.',
        'same_as_current' => 'كلمة المرور الجديدة مطابقة للحالية.',
        'mismatch' => 'التأكيد لا يطابق كلمة المرور الجديدة.',
        'wrong_current' => 'كلمة المرور الحالية غير صحيحة.',
    ],
];
