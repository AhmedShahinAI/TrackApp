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
        'status' => 'لا يوجد طلب يطابق هذا الرقم المرجعي والرقم الجامعي معًا. تحقّق منهما وحاول مرة أخرى.',
        'challenge_unavailable' => 'الخدمة غير متاحة مؤقتًا. حاول مرة أخرى بعد قليل.',
        // One message for an unsolved or replayed challenge, a filled honeypot, a form sent too fast.
        'challenge' => 'لم يكتمل التحقق الأمني. أعد تحميل الصفحة وحاول مرة أخرى.',
    ],

    // T398 — the form (US9).
    'form' => [
        'title' => 'طلب دعم فني',
        'lead' => 'صف المشكلة التي تواجهها، وسيتابعها فريق الدعم. ستحصل على رقم مرجعي تتابع به طلبك.',
        'university_id' => 'الرقم الجامعي',
        'university_id_hint' => 'رقمك الجامعي كما هو في سجلّك.',
        'category' => 'نوع المشكلة',
        'category_placeholder' => 'اختر نوع المشكلة',
        'description' => 'وصف المشكلة',
        'description_count' => ':count من :max حرفًا',
        'contact' => 'وسيلة تواصل (اختياري)',
        'contact_hint' => 'بريد أو هاتف تكتبه بنفسك، إن رغبت أن نتواصل معك.',
        'honeypot' => 'اترك هذا الحقل فارغًا',
        'submit' => 'إرسال الطلب',
        'challenge_working' => 'جارٍ التحقق من أن المُرسل شخص وليس برنامجًا…',
        'challenge_ready' => 'اكتمل التحقق. يمكنك إرسال الطلب.',
        'challenge_failed' => 'تعذّر إكمال التحقق. أعد تحميل الصفحة وحاول مرة أخرى.',
        'needs_javascript' => 'يتطلب هذا النموذج تفعيل JavaScript لإكمال التحقق الأمني.',
    ],

    // T396 — the confirmation.
    'submitted' => [
        'title' => 'تم استلام طلبك',
        'reference_label' => 'الرقم المرجعي',
        'keep_it' => 'احتفظ بهذا الرقم. ستحتاجه مع رقمك الجامعي لمتابعة حالة طلبك، ولن يُعرض مرة أخرى.',
        'check_status' => 'متابعة حالة الطلب',
        'new_request' => 'إرسال طلب آخر',
    ],

    // T397 — the refusal page.
    'refused' => [
        'title' => 'تعذّر إرسال الطلب',
        'try_again' => 'العودة إلى النموذج',
    ],

    // T422 — the staff side (US10).
    'staff' => [
        'title' => 'طلبات الدعم',
        'subtitle' => 'الطلبات الواردة من الطلاب، الأقدم انتظارًا أولًا.',
        'detail_title' => 'طلب الدعم :reference',
        'empty' => 'لا توجد طلبات تطابق هذا البحث.',
        'fields' => [
            'reference' => 'الرقم المرجعي',
            'student' => 'الطالب',
            'university_id' => 'الرقم الجامعي',
            'branch' => 'الفرع',
            'category' => 'النوع',
            'status' => 'الحالة',
            'assignee' => 'المسؤول',
            'waiting' => 'مدة الانتظار',
            'submitted_at' => 'تاريخ الإرسال',
            'description' => 'وصف الطالب',
            'contact' => 'وسيلة التواصل',
            'term' => 'الفصل الدراسي',
            'ip' => 'عنوان الإرسال',
            'rejection_reason' => 'سبب الرفض',
        ],
        'filters' => [
            'status' => 'الحالة',
            'category' => 'النوع',
            'branch' => 'الفرع',
            'assignee' => 'المسؤول',
            'date_from' => 'من تاريخ',
            'date_to' => 'إلى تاريخ',
            'q' => 'بحث',
            'q_placeholder' => 'رقم مرجعي أو رقم جامعي أو اسم',
            'any' => 'الكل',
        ],
        // Phase 24 (T447) — joining a list of names, and a waiting time in words, in this language.
        'list_separator' => '، ',
        'duration' => [
            'days' => '{1} يوم|{2} يومان|[3,10] :count أيام|[11,*] :count يومًا',
            'hours' => '{1} ساعة|{2} ساعتان|[3,10] :count ساعات|[11,*] :count ساعة',
            'minutes' => '{1} دقيقة|{2} دقيقتان|[3,10] :count دقائق|[11,*] :count دقيقة',
            'join' => ' و',
            'under_a_minute' => 'أقل من دقيقة',
        ],
        'unassigned' => 'غير مُسند',
        'student_removed' => 'أُزيلت بيانات الطالب',
        'no_contact' => 'لم يُدخل الطالب وسيلة تواصل.',
        'unverified' => 'غير مؤكَّدة',
        'unverified_hint' => 'كتبها الطالب بنفسه ولم يُتحقق منها.',
        'thread' => 'الردود',
        'no_responses' => 'لم يُرسل أي رد بعد.',
        'history' => 'سجل الحالة',
        'showing_latest' => 'يُعرض أحدث :shown من :total.',
        'history_by_student' => 'الطالب',
        'respond' => [
            'label' => 'رد على الطالب',
            'hint' => 'يظهر الرد للطالب عند متابعة طلبه، ولا يمكن تعديله بعد إرساله.',
            'submit' => 'إرسال الرد',
            'closed' => 'الطلب مغلق ولا يقبل ردودًا.',
        ],
        'transition' => [
            'label' => 'تغيير الحالة',
            'to' => 'الحالة الجديدة',
            'submit' => 'تغيير الحالة',
            'none' => 'لا توجد حالة يمكن الانتقال إليها من هنا.',
        ],
        'reject' => [
            'title' => 'رفض الطلب',
            'impact' => 'سيُغلق الطلب دون حل، وسيرى الطالب سبب الرفض كما تكتبه هنا. لا يمكن التراجع عن الرفض.',
            'reason' => 'سبب الرفض',
            'reason_hint' => 'اكتبه للطالب، لا كملاحظة داخلية.',
            'confirm' => 'رفض الطلب',
            'open' => 'رفض…',
        ],
        'assign' => [
            'label' => 'إسناد الطلب',
            'submit' => 'حفظ الإسناد',
        ],
        'errors' => [
            'transition' => 'لا يمكن نقل الطلب من «:from» إلى «:to».',
            'close_forbidden' => 'إغلاق الطلب يتطلب صلاحية الإغلاق.',
            'resolve_needs_response' => 'لا يمكن وضع الطلب «تم الحل» قبل الرد على الطالب.',
            'reason_required' => 'اكتب سبب الرفض؛ سيظهر للطالب.',
            'closed' => 'الطلب مغلق ولا يقبل ردودًا.',
            'assignee_cannot_view' => 'لا يستطيع هذا المستخدم فتح الطلب، فإسناده إليه سيتركه دون متابعة.',
        ],
    ],

    // T432 — the status page (US11).
    'status' => [
        'title' => 'متابعة طلب دعم',
        'lead' => 'أدخل الرقم المرجعي الذي حصلت عليه عند إرسال طلبك، ورقمك الجامعي.',
        'reference' => 'الرقم المرجعي',
        'reference_hint' => 'مثل K7Q2-M9XV-T3X — يمكن كتابته بالأحرف الصغيرة ودون الشرطات.',
        'submit' => 'عرض حالة الطلب',
        'new_request' => 'تقديم طلب جديد',
        'result_title' => 'حالة طلبك',
        'state' => 'الحالة',
        'submitted_at' => 'تاريخ الإرسال',
        'rejection_reason' => 'سبب الرفض:',
        'responses' => 'ردود فريق الدعم',
        'still_handled' => 'لم يصل رد بعد. طلبك قيد المتابعة، وسيظهر الرد هنا عند إرساله.',
        'another' => 'متابعة طلب آخر',
    ],

    // FR-130: a limit was reached and when to retry — never which limit.
    'limit_title' => 'محاولات كثيرة',
    'limit_reached' => '{1} تم بلوغ الحد المسموح من المحاولات. حاول مرة أخرى بعد دقيقة.|{2} تم بلوغ الحد المسموح من المحاولات. حاول مرة أخرى بعد دقيقتين.|[3,10] تم بلوغ الحد المسموح من المحاولات. حاول مرة أخرى بعد :minutes دقائق.|[11,*] تم بلوغ الحد المسموح من المحاولات. حاول مرة أخرى بعد :minutes دقيقة.',
];
