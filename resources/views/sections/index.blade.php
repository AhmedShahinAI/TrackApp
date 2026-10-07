{{--
    The sections list — the screen US2's scope is visible on.

    There is no filtering by branch here and no "all branches" toggle, and that absence is the
    feature: the list a coordinator opens is already their branches, enforced by a global scope on
    the query rather than by a default on a filter that someone could clear. FR-023 requires the
    screen and the API to agree, and they do because they are the same query.

    FR-026 — a section reached through a grant carries the shared-access badge. The flag is computed
    per caller (Section::sharedAccess()), so the same row is badged for the instructor who was given
    it and unbadged for the one who teaches it.
--}}

@php
    $locale = app()->getLocale();

    $rows = $sections->map(fn ($section) => [
        'id' => $section->id,
        'section_number' => $section->section_number,
        'course' => $section->course?->code,
        'branch' => $section->branch?->{'campus_name_'.$locale} ?? $section->branch?->campus_name_ar,
        'instructor' => $section->instructor?->{'name_'.$locale}
            ?? $section->instructor?->name_ar
            ?? __('sections.unassigned'),
        'enrollments' => $section->active_enrollments ?? 0,
        'shared' => $section->shared_access,
    ])->all();

    // Declared outside the attribute. A nested array literal inside a double-quoted Blade attribute
    // closes the attribute at its first inner quote, which cost a day in US1.
    /*
     * Three things this renderer got wrong on the way in, all of them visible only in a screenshot:
     *
     *   1. The closure receives the whole ROW, not the cell value (see data-table's @props). Taking
     *      the argument as the value made every row truthy, so every section was badged "shared".
     *   2. Its return is echoed escaped unless it is an HtmlString, so the badge first rendered as
     *      literal `<span class="track-badge...">` text in the cell.
     *   3. Blade::render, not view(...): the component expects a $slot, and rendering it as a plain
     *      view threw "Undefined variable $slot" — a 500 that the visual suite captured as this
     *      screen's baseline without complaint.
     */
    $renderers = [
        'shared' => fn (array $row) => $row['shared']
            ? new \Illuminate\Support\HtmlString(
                \Illuminate\Support\Facades\Blade::render('<x-track-status-badge status="shared" />')
            )
            : '',
    ];
@endphp

<x-app-layout :title="__('sections.title')">
    <x-track-page-header :title="__('sections.title')" :subtitle="__('sections.subtitle')" />

    <x-track-card>
        <x-track-data-table
            :caption="__('sections.title')"
            :empty="__('sections.empty.message')"
            empty-icon="users"
            :columns="[
                ['key' => 'section_number', 'label' => __('sections.fields.number')],
                ['key' => 'course', 'label' => __('sections.fields.course')],
                ['key' => 'branch', 'label' => __('sections.fields.branch')],
                ['key' => 'instructor', 'label' => __('sections.fields.instructor')],
                ['key' => 'enrollments', 'label' => __('sections.fields.enrollments')],
                ['key' => 'shared', 'label' => __('sections.fields.access'), 'sortable' => false],
            ]"
            :rows="$rows"
            :renderers="$renderers"
            row-url="/sections/{id}"
        />
    </x-track-card>

    @if ($sections->hasPages())
        <div style="margin-top: var(--space-4)">{{ $sections->links() }}</div>
    @endif
</x-app-layout>
