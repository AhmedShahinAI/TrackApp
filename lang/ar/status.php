<?php

declare(strict_types=1);

/*
 * T099 — the fixed status vocabulary from design-system.md §8.
 *
 * "Fixed" means exactly that: these nineteen labels are approved wording and are not to be
 * paraphrased. A coordinator who learns that متأخرة means one thing on the sections screen must
 * find it meaning the same thing on the imports screen.
 *
 * Phase 1 renders fifteen of them. `progress`, `late`, `pending`, and `notStarted` belong to the
 * Phase 2 assignment workflow and are seeded here so the two locales stay at parity from the start
 * (LocalizationParityTest, T054).
 */

return [
    'active' => 'فعّال',
    'completed' => 'مكتمل',
    'progress' => 'قيد التنفيذ',
    'late' => 'متأخرة',
    'pending' => 'بانتظار الاعتماد',
    'notStarted' => 'لم تبدأ',
    'closed' => 'مغلق',
    'draft' => 'مسودة',
    'archived' => 'مؤرشف',
    'withdrawn' => 'منسحب',
    'inactive' => 'موقوف',
    'failed' => 'فشل',
    'queued' => 'في الانتظار',
    'running' => 'قيد التشغيل',
    'warning' => 'تحذير',
    'error' => 'خطأ',
    'valid' => 'صالح',
    'shared' => 'وصول مشترك',
    'current' => 'الحالي',
];
