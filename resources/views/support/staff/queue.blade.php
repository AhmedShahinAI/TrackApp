{{--
    T418 — the support queue (Amendment 2, US10). Oldest waiting first: the queue exists to be worked
    down (FR-137).

    Already the caller's scope: SupportRequest's branch scope decides what a coordinator sees, and the
    branch filter only narrows within it (contracts/support-staff.md). There is no visibility check in
    this template and there must not be.

    The status badge takes the support state's own label: the five states are this feature's
    vocabulary, mapped onto the design system's badge tones rather than added to them.
--}}

@php
    $badgeTone = ['new' => 'pending', 'in_progress' => 'progress', 'resolved' => 'completed', 'closed' => 'closed', 'rejected' => 'rejected'];

    $tableRows = collect($rows)->map(fn (array $row) => [
        'id' => $row['reference'],
        'reference' => $row['reference'],
        'student' => $row['student']['removed'] ? __('support.staff.student_removed') : $row['student']['name'],
        'university_id' => $row['student']['university_id'],
        'branch' => implode(__('support.staff.list_separator'), $row['branches']) ?: '—',
        'category' => $row['category']['label'],
        'status' => $row['status'],
        'assignee' => $row['assignee']['name'] ?? __('support.staff.unassigned'),
        'waiting' => $row['waiting_label'] ?? '—',
        // A badge and a duration render as markup and words; these are what each sorts by. A finished
        // request has no waiting time and sorts as zero.
        '_sort' => ['waiting' => $row['waiting_seconds'] ?? 0, 'status' => $row['status']['label']],
    ])->all();

    $columns = array_values(array_filter([
        ['key' => 'reference', 'label' => __('support.staff.fields.reference')],
        ['key' => 'student', 'label' => __('support.staff.fields.student')],
        // T442 — only where there is more than one branch to tell apart.
        $showBranch ? ['key' => 'branch', 'label' => __('support.staff.fields.branch')] : null,
        ['key' => 'category', 'label' => __('support.staff.fields.category')],
        ['key' => 'status', 'label' => __('support.staff.fields.status')],
        ['key' => 'assignee', 'label' => __('support.staff.fields.assignee')],
        ['key' => 'waiting', 'label' => __('support.staff.fields.waiting'), 'numeric' => true],
    ]));

    $renderers = [
        // LTR and tabular inside an RTL row, so a copied reference keeps its groups in order.
        'reference' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr" class="track-code">'.e($row['reference']).'</span>'
        ),
        'student' => fn (array $row) => new Illuminate\Support\HtmlString(
            e($row['student']).($row['university_id']
                ? '<br><span dir="ltr" class="track-code track-table__note">'.e($row['university_id']).'</span>'
                : '')
        ),
        'status' => fn (array $row) => new Illuminate\Support\HtmlString(Illuminate\Support\Facades\Blade::render(
            '<x-track-status-badge :status="$tone">{{ $label }}</x-track-status-badge>',
            ['tone' => $badgeTone[$row['status']['value']] ?? 'pending', 'label' => $row['status']['label']],
        )),
    ];

    $statusOptions = collect(App\Modules\Support\Enums\SupportRequestStatus::cases())
        ->map(fn ($status) => ['value' => $status->value, 'label' => __($status->labelKey())])->all();

    $categoryOptions = collect(App\Modules\Settings\Settings::options(App\Modules\Settings\Settings::SUPPORT_CATEGORIES))
        ->map(fn (array $option) => ['value' => $option['value'], 'label' => App\Modules\Support\Http\SupportRequestPresenter::categoryLabel($option['value'])])
        ->all();
@endphp

<x-app-layout :title="__('support.staff.title')">
    <x-track-page-header :title="__('support.staff.title')" :subtitle="__('support.staff.subtitle')" />

    <x-track-card class="mb-4">
        <form method="GET" action="{{ route('support-requests.index') }}" class="track-filters">
            <x-track-input
                name="q"
                size="sm"
                class="track-filters__search"
                :label="__('support.staff.filters.q')"
                :placeholder="__('support.staff.filters.q_placeholder')"
                :value="$filters['q'] ?? ''"
            />

            <x-track-select name="status" size="sm" :label="__('support.staff.filters.status')"
                :options="$statusOptions" :placeholder="__('support.staff.filters.any')" :selected="$filters['status'] ?? null" />

            <x-track-select name="category" size="sm" :label="__('support.staff.filters.category')"
                :options="$categoryOptions" :placeholder="__('support.staff.filters.any')" :selected="$filters['category'] ?? null" />

            @if ($showBranch && count($branches) > 1)
                <x-track-select name="branch_id" size="sm" :label="__('support.staff.filters.branch')"
                    :options="$branches" :placeholder="__('support.staff.filters.any')" :selected="$filters['branch_id'] ?? null" />
            @endif

            <x-track-select name="assigned_to" size="sm" :label="__('support.staff.filters.assignee')"
                :options="$assignees" :placeholder="__('support.staff.filters.any')" :selected="$filters['assigned_to'] ?? null" />

            <x-track-input name="date_from" type="date" size="sm" :label="__('support.staff.filters.date_from')" :value="$filters['date_from'] ?? ''" />
            <x-track-input name="date_to" type="date" size="sm" :label="__('support.staff.filters.date_to')" :value="$filters['date_to'] ?? ''" />

            <div>
                <x-track-button type="submit" variant="secondary" size="sm" icon="filter">
                    {{ __('common.actions.filter') }}
                </x-track-button>
            </div>
        </form>
    </x-track-card>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('support.staff.title')"
            :empty="__('support.staff.empty')"
            empty-icon="life-buoy"
            :row-url="fn (array $row) => route('support-requests.show', $row['reference'])"
            :columns="$columns"
            :rows="$tableRows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($page->hasPages())
        <div class="track-pagination">{{ $page->links() }}</div>
    @endif
</x-app-layout>
