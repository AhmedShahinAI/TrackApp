{{--
    T100 — the component gallery.

    Every x-track-* component in every variant and state, on one page, reachable only when
    APP_ENV=local. This is the surface tests/Visual/components.spec.ts screenshots, so the order and
    the ids are load-bearing: a baseline is keyed to a selector, and renaming a section invalidates
    every shot in it.

    Each block carries `data-gallery="<name>"`, which is what the visual suite targets. That is also
    why the page has no sidebar or topbar chrome around the specimens — a shell that scrolls would
    move every component a few pixels between runs and every diff would fail for nothing.
--}}

@php
    $statuses = [
        'active', 'completed', 'progress', 'late', 'pending', 'notStarted', 'closed', 'draft',
        'archived', 'withdrawn', 'inactive', 'failed', 'queued', 'running', 'warning', 'error',
        'valid', 'shared', 'current',
    ];

    $tableColumns = [
        ['key' => 'number', 'label' => 'الشعبة', 'numeric' => true],
        ['key' => 'course', 'label' => 'المقرر'],
        ['key' => 'branch', 'label' => 'الفرع'],
        ['key' => 'students', 'label' => 'الطلبة', 'numeric' => true],
    ];

    $tableRows = [
        ['number' => '214', 'course' => 'اللغة الإنجليزية 2', 'branch' => 'الزلفي — طالبات', 'students' => 34],
        ['number' => '123', 'course' => 'مبادئ الرياضيات', 'branch' => 'المجمعة — طلاب', 'students' => 38],
        ['number' => '305', 'course' => 'مقدمة في الحاسب', 'branch' => 'الزلفي — طلاب', 'students' => 29],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Component gallery</title>
    @vite([app()->isLocale('ar') ? 'resources/css/app-rtl.scss' : 'resources/css/app-ltr.scss', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        /* Gallery chrome only. Not part of the design system, and deliberately not token-driven —
           it must never be mistaken for a component. */
        .gal { padding: 28px; max-width: 1100px; }
        .gal section { margin-bottom: 40px; }
        .gal h2 { font: var(--text-h3); margin: 0 0 12px; color: var(--text-secondary); }
        .gal .row { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; }
        .gal .col { display: flex; flex-direction: column; gap: 12px; max-width: 420px; }
        /* The shell components are position:fixed/sticky by design. Contained here so they can be
           screenshotted as specimens instead of overlaying the whole gallery. */
        .gal .shell-preview { position: relative; height: 420px; overflow: hidden;
            border: 1px solid var(--border-default); border-radius: var(--radius-lg); }
        .gal .shell-preview .track-sidebar,
        .gal .shell-preview .track-topbar { position: absolute; }
    </style>
</head>
<body>
<div class="gal">

<section data-gallery="button">
    <h2>button</h2>
    <div class="row">
        <x-track-button variant="primary">حفظ</x-track-button>
        <x-track-button variant="accent">اعتماد</x-track-button>
        <x-track-button variant="secondary">إلغاء</x-track-button>
        <x-track-button variant="ghost">تفاصيل</x-track-button>
        <x-track-button variant="danger">حذف</x-track-button>
    </div>
    <div class="row">
        <x-track-button size="sm">صغير</x-track-button>
        <x-track-button size="md">متوسط</x-track-button>
        <x-track-button size="lg">كبير</x-track-button>
    </div>
    <div class="row">
        <x-track-button icon="plus">إضافة شعبة</x-track-button>
        <x-track-button icon-end="chevron-left">التالي</x-track-button>
        <x-track-button :loading="true">جارٍ الحفظ</x-track-button>
        <x-track-button :disabled="true">غير متاح</x-track-button>
    </div>
</section>

<section data-gallery="icon-button">
    <h2>icon-button</h2>
    <div class="row">
        <x-track-icon-button label="تعديل" icon="pencil" variant="ghost" />
        <x-track-icon-button label="تنزيل" icon="download" variant="outline" />
        <x-track-icon-button label="إضافة" icon="plus" variant="filled" />
        <x-track-icon-button label="التنبيهات (3)" icon="bell" :dot="true" />
        <x-track-icon-button label="صغير" icon="x" size="sm" />
        <x-track-icon-button label="كبير" icon="search" size="lg" />
    </div>
</section>

<section data-gallery="icon">
    <h2>icon</h2>
    <div class="row">
        @foreach (array_values(config('icons.concepts')) as $name)
            <x-track-icon :name="$name" />
        @endforeach
    </div>
</section>

<section data-gallery="logo">
    <h2>logo</h2>
    <div class="row" style="background: var(--surface-sidebar); padding: 12px">
        <x-track-logo variant="reversed" :height="28" />
        <x-track-logo variant="mark-reversed" :height="28" />
    </div>
    <div class="row">
        <x-track-logo variant="primary" :height="28" />
        <x-track-logo variant="mono" :height="28" />
        <x-track-logo variant="mark" :height="28" />
        <x-track-logo variant="lockup" :height="40" />
    </div>
</section>

<section data-gallery="input">
    <h2>input</h2>
    <div class="col">
        <x-track-input name="g_email" label="البريد الإلكتروني" type="email" :required="true" icon="mail" hint="البريد الجامعي فقط" />
        <x-track-input name="g_id" label="الرقم الجامعي" value="441002315" />
        <x-track-input name="g_err" label="الرقم الجامعي" value="12" error="الرقم الجامعي يجب أن يكون 9 أرقام" />
        <x-track-input name="g_dis" label="لا يمكن التعديل" value="قيمة ثابتة" :disabled="true" />
        <x-track-input name="g_sm" label="صغير" size="sm" />
        <x-track-input name="g_lg" label="كبير" size="lg" />
    </div>
</section>

<section data-gallery="select">
    <h2>select</h2>
    <div class="col">
        <x-track-select
            name="g_degree"
            label="الدرجة"
            icon="library"
            :options="[['value' => 'bachelor', 'label' => 'البكالوريوس'], ['value' => 'intermediate_diploma', 'label' => 'الدبلوم المتوسط']]"
            selected="bachelor"
        />
        <x-track-select name="g_err2" label="الفرع" :options="['a' => 'المجمعة']" placeholder="اختر فرعًا" error="الفرع مطلوب" />
    </div>
</section>

<section data-gallery="checkbox">
    <h2>checkbox</h2>
    <div class="col">
        <x-track-checkbox name="g_c1" label="إظهار الشعب غير المُعيَّنة" />
        <x-track-checkbox name="g_c2" label="مُحدَّد" :checked="true" />
        <x-track-checkbox name="g_c3" label="غير متاح" :disabled="true" />
    </div>
</section>

<section data-gallery="switch">
    <h2>switch</h2>
    <div class="col">
        <x-track-switch name="g_s1" label="الحساب نشط" :checked="true" />
        <x-track-switch name="g_s2" label="موقوف" />
    </div>
</section>

<section data-gallery="status-badge">
    <h2>status-badge — all 19</h2>
    <div class="row">
        @foreach ($statuses as $status)
            <x-track-status-badge :status="$status" />
        @endforeach
    </div>
</section>

<section data-gallery="alert">
    <h2>alert</h2>
    <div class="col">
        <x-track-alert tone="info" title="تسجيل الدخول الأول">يُنصح بتغيير كلمة المرور.</x-track-alert>
        <x-track-alert tone="success">تم حفظ التعديلات.</x-track-alert>
        <x-track-alert tone="warning">هذا الملف مستورد سابقًا بنفس البصمة.</x-track-alert>
        <x-track-alert tone="danger" :dismissible="true">الحساب موقوف مؤقتًا لمدة 15 دقيقة.</x-track-alert>
        <x-track-alert tone="info" :title="__('common.queue.waiting')">
            {{ __('common.queue.waitingMessage') }}
            <x-slot:action><x-track-button size="sm" variant="ghost">تحديث</x-track-button></x-slot:action>
        </x-track-alert>
    </div>
</section>

<section data-gallery="toast">
    <h2>toast</h2>
    <div class="col">
        <x-track-toast tone="success">تم الاعتماد.</x-track-toast>
        <x-track-toast tone="error">تعذّر الحفظ.</x-track-toast>
        <x-track-toast tone="info">العملية في الانتظار.</x-track-toast>
    </div>
</section>

<section data-gallery="dialog">
    <h2>dialog — inline variant</h2>
    <x-track-dialog id="g-dlg" title="تأكيد الاعتماد" :inline="true" :open="true">
        سيتم اعتماد الشعبة وإتاحتها للمدرّس.
        <x-slot:footer>
            <x-track-button variant="secondary">إلغاء</x-track-button>
            <x-track-button variant="primary">تأكيد</x-track-button>
        </x-slot:footer>
    </x-track-dialog>
</section>

<section data-gallery="progress">
    <h2>progress</h2>
    <div class="col">
        <x-track-progress :value="12" label="الاستيراد" />
        <x-track-progress :value="48" label="الاستيراد" />
        <x-track-progress :value="91" label="الاستيراد" />
        <x-track-progress :value="60" tone="blue" :show-label="false" />
    </div>
</section>

<section data-gallery="skeleton">
    <h2>skeleton</h2>
    <div class="col">
        <x-track-skeleton width="220px" />
        <x-track-skeleton width="160px" height="0.75rem" />
        <x-track-skeleton width="100%" height="2.5rem" />
    </div>
</section>

<section data-gallery="tooltip">
    <h2>tooltip</h2>
    <div class="row">
        <x-track-tooltip text="تعديل الشعبة">
            <x-track-icon-button label="تعديل" icon="pencil" variant="outline" />
        </x-track-tooltip>
    </div>
</section>

<section data-gallery="empty-state">
    <h2>empty-state</h2>
    <x-track-card>
        <x-track-empty-state icon="inbox" />
    </x-track-card>
    <x-track-card>
        <x-track-empty-state icon="ban" :title="__('common.errors.forbidden')" :message="__('common.errors.forbiddenMessage')" />
    </x-track-card>
</section>

<section data-gallery="card">
    <h2>card</h2>
    <x-track-card title="الشعب الدراسية">
        <x-slot:action><x-track-button size="sm" icon="plus">إضافة</x-track-button></x-slot:action>
        محتوى البطاقة.
    </x-track-card>
</section>

<section data-gallery="panel">
    <h2>panel</h2>
    <x-track-panel title="بيانات الحساب">محتوى اللوحة.</x-track-panel>
</section>

<section data-gallery="stat-card">
    <h2>stat-card</h2>
    <div class="row">
        <x-track-stat-card value="3,214" label="الطلبة المسجّلون" tone="blue" icon="graduation-cap" />
        <x-track-stat-card value="92%" label="نسبة الإكمال" tone="green" trend="4%" trend-direction="up" />
        <x-track-stat-card value="47" label="الشعب" tone="dark" icon="users" />
    </div>
</section>

<section data-gallery="data-table">
    <h2>data-table</h2>
    <x-track-card :padding="0">
        <x-track-data-table :columns="$tableColumns" :rows="$tableRows" :searchable="true" caption="الشعب" />
    </x-track-card>
    <x-track-card :padding="0">
        <x-track-data-table :columns="$tableColumns" :rows="[]" :loading="true" />
    </x-track-card>
    <x-track-card :padding="0">
        <x-track-data-table :columns="$tableColumns" :rows="[]" />
    </x-track-card>
</section>

<section data-gallery="tabs">
    <h2>tabs</h2>
    <x-track-tabs
        id="g-tabs-u"
        :tabs="[['key' => 'all', 'label' => 'الكل', 'count' => 47], ['key' => 'mine', 'label' => 'شعبي', 'count' => 6]]"
        active="all"
    />
    <x-track-tabs
        id="g-tabs-s"
        variant="segmented"
        :tabs="[['key' => 'ar', 'label' => 'عربي'], ['key' => 'en', 'label' => 'English']]"
        active="ar"
    />
</section>

<section data-gallery="breadcrumbs">
    <h2>breadcrumbs</h2>
    <x-track-breadcrumbs :items="[
        ['label' => 'الرئيسية', 'url' => '#'],
        ['label' => 'الشعب الدراسية', 'url' => '#'],
        ['label' => 'الشعبة 214'],
    ]" />
</section>

<section data-gallery="stepper">
    <h2>stepper</h2>
    <x-track-stepper
        label="خطوات الاستيراد"
        :current="3"
        :steps="['رفع الملف', 'مطابقة الأعمدة', 'التحقّق', 'المعاينة', 'التنفيذ', 'النتيجة']"
    />
</section>

<section data-gallery="page-header">
    <h2>page-header</h2>
    <x-track-page-header title="الشعب الدراسية" subtitle="47 شعبة في الفصل الحالي">
        <x-slot:breadcrumbs>
            <x-track-breadcrumbs :items="[['label' => 'الرئيسية', 'url' => '#'], ['label' => 'الشعب الدراسية']]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-track-button variant="secondary" icon="download">تصدير</x-track-button>
            <x-track-button icon="plus">إضافة شعبة</x-track-button>
        </x-slot:actions>
    </x-track-page-header>
</section>

<section data-gallery="sidebar">
    <h2>sidebar</h2>
    <div class="shell-preview">
        <x-track-sidebar :items="[
            ['group' => 'overview'],
            ['key' => 'home', 'url' => '#', 'active' => true],
            ['key' => 'dashboard', 'url' => '#'],
            ['group' => 'academic'],
            ['key' => 'terms', 'url' => '#'],
            ['key' => 'branches', 'url' => '#'],
            ['key' => 'courses', 'url' => '#'],
            ['key' => 'programs', 'url' => '#'],
            ['key' => 'sections', 'url' => '#'],
            ['group' => 'people'],
            ['key' => 'users', 'url' => '#'],
            ['key' => 'students', 'url' => '#'],
            ['group' => 'operations'],
            ['key' => 'imports', 'url' => '#'],
            ['key' => 'audit', 'url' => '#'],
        ]">
            <x-slot:footer>
                <x-track-button variant="ghost" icon="log-out" :block="true">
                    {{ __('common.actions.logout') }}
                </x-track-button>
            </x-slot:footer>
        </x-track-sidebar>
    </div>
</section>

<section data-gallery="topbar">
    <h2>topbar</h2>
    <div class="shell-preview" style="height: 80px">
        <x-track-topbar
            user-name="مها السالم"
            user-role="مسؤول النظام"
            profile-url="#"
            :notification-count="3"
            search-action="#"
        >
            <x-slot:end>
                <x-track-button variant="ghost" size="sm">EN</x-track-button>
            </x-slot:end>
        </x-track-topbar>
    </div>
</section>

<section data-gallery="field">
    <h2>field</h2>
    <div class="col">
        <x-track-field label="حقل بتلميح" for="g-field-1" hint="نص مساعد">
            <input id="g-field-1" class="track-input" type="text" value="قيمة">
        </x-track-field>
        <x-track-field label="حقل بخطأ" for="g-field-2" error="هذه القيمة غير مسموح بها" :required="true">
            <input id="g-field-2" class="track-input track-input--error" type="text" value="قيمة">
        </x-track-field>
    </div>
</section>

</div>
</body>
</html>
