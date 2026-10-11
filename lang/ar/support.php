<?php

declare(strict_types=1);

/*
 * T356 + T376 — the student support channel (Amendment 2). Read as __('support.…'); key for key with
 * lang/en/support.php (LocalizationParityTest).
 *
 * The category labels are NOT here. They live with the categories in the `support_categories`
 * setting, which the Super Admin extends (constitution IV); a translation key per category would be
 * a second list that the sixth category added through settings would never reach.
 */

return [
    'layout' => [
        'title' => 'الدعم الفني للطلاب',
        'footer' => 'لا يتطلب هذا النموذج حسابًا ولا تسجيل دخول.',
    ],

    // The five states (data-model.md "State machine"); the badge and the status page read these.
    'statuses' => [
        'new' => 'جديد',
        'in_progress' => 'قيد المعالجة',
        'resolved' => 'تم الحل',
        'closed' => 'مغلق',
        'rejected' => 'مرفوض',
    ],

    // The two generic failures (contracts/support-public.md). Each is ONE message for several
    // causes, and must stay byte-identical across them (FR-119, FR-135) — never split by reason.
    'failures' => [
        'submission' => 'تعذّر إرسال الطلب. تحقّق من رقمك الجامعي وحاول مرة أخرى.',
        'status' => 'لم يتم العثور على طلب بهذا الرقم المرجعي.',
        'challenge_unavailable' => 'الخدمة غير متاحة مؤقتًا. حاول مرة أخرى بعد قليل.',
    ],

    // FR-130: a limit was reached and when to retry — never which limit.
    'limit_title' => 'محاولات كثيرة',
    'limit_reached' => '{1} تم بلوغ الحد المسموح من المحاولات. حاول مرة أخرى بعد دقيقة.|{2} تم بلوغ الحد المسموح من المحاولات. حاول مرة أخرى بعد دقيقتين.|[3,10] تم بلوغ الحد المسموح من المحاولات. حاول مرة أخرى بعد :minutes دقائق.|[11,*] تم بلوغ الحد المسموح من المحاولات. حاول مرة أخرى بعد :minutes دقيقة.',
];
