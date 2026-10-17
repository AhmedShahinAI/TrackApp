{{--
    T496 — the section approval queue, to v2's ApprovalsScreen.

    What this caller may decide, oldest waiting first (Section::scopeAwaitingDecisionBy). A coordinator
    sees instructors' requests in their branches; a Super Admin sees every pending section (FR-177,
    FR-184). A row opens the section, where the decision is made: the dialog needs the section's own
    page, with its roster and history beside it.

    Expects: $sections (the paginator), $rows (SectionApprovalController::queueRow).
--}}

@php
    $columns = [
        ['key' => 'section', 'label' => __('sections.fields.number'), 'numeric' => true],
        ['key' => 'course', 'label' => __('sections.fields.course'), 'wrap' => true],
        ['key' => 'branch', 'label' => __('sections.fields.branch')],
        ['key' => 'creator', 'label' => __('sections.approval.fields.creator'), 'wrap' => true],
        ['key' => 'role', 'label' => __('sections.approval.fields.role')],
        ['key' => 'enrollments', 'label' => __('sections.approval.fields.enrollments'), 'numeric' => true],
        ['key' => 'waiting', 'label' => __('sections.approval.fields.waiting_since')],
        ['key' => 'status', 'label' => __('sections.approval.fields.state'), 'sortable' => false],
    ];

    $tableRows = array_map(fn (array $row) => [
        'id' => $row['id'],
        'section' => $row['section_number'],
        'course' => trim(($row['course']['code'] ?? '').' · '.($row['course']['name'] ?? ''), ' ·'),
        'branch' => $row['branch'] ?? '—',
        'creator' => $row['creator']['name'] ?? '—',
        'role' => $row['creator']['role_label'] ?? '—',
        'enrollments' => $row['active_enrollments'],
        'waiting' => $row['waiting_label'],
        '_sort' => ['waiting' => $row['waiting_since']],
        'status' => $row['status'],
    ], $rows);

    $renderers = [
        'section' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr" class="track-code">'.e((string) $row['section']).'</span>'
        ),
        'enrollments' => fn (array $row) => App\Modules\Shared\Support\Format::number($row['enrollments']),
        'status' => fn (array $row) => new Illuminate\Support\HtmlString(Illuminate\Support\Facades\Blade::render(
            '<x-track-status-badge :status="$status">{{ $label }}</x-track-status-badge>',
            ['status' => $row['status'], 'label' => __('sections.approval.status.'.$row['status'])],
        )),
    ];

    $subtitle = auth()->user()?->can('scope.bypass_all') ? __('sections.approval.subtitle_all') : __('sections.approval.subtitle_scoped');
@endphp

<x-app-layout :title="__('sections.approval.title')">
    <x-track-breadcrumbs :items="[
        ['label' => __('sections.title'), 'url' => route('sections.index')],
        ['label' => __('sections.approval.title')],
    ]" />

    <x-track-page-header :title="__('sections.approval.title')" :subtitle="$subtitle" />

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('sections.approval.title')"
            :empty="__('sections.approval.empty')"
            empty-icon="clipboard-check"
            :row-url="fn (array $row) => route('sections.show', $row['id'])"
            :columns="$columns"
            :rows="$tableRows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($sections->hasPages())
        <div class="track-pagination">{{ $sections->links() }}</div>
    @endif
</x-app-layout>
