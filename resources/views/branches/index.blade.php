{{--
    T197 — branches, to the BranchesScreen reference.

    The standing notice is the part worth defending. FR-049 makes deactivation the only removal
    path, and a person who comes here to delete a campus needs to be told that before they go
    looking for a button that does not exist. Saying it only when they try is saying it too late.
--}}

@php
    $locale = app()->getLocale();

    $rows = $branches->map(fn ($branch) => [
        'id' => $branch->id,
        'campus_name' => $branch->{'campus_name_'.$locale} ?? $branch->campus_name_ar,
        'gender' => __('branches.gender.'.$branch->gender),
        'sections' => $branch->sections_count ?? 0,

        // DISTINCT in the controller: a student in three sections of this branch is one student.
        'students' => $branch->students_count ?? 0,
        'status' => (bool) $branch->is_active,
    ])->all();

    $branchColumns = [
        ['key' => 'campus_name', 'label' => __('branches.fields.campus_name')],
        ['key' => 'gender', 'label' => __('branches.fields.gender')],
        ['key' => 'sections', 'label' => __('branches.fields.sections'), 'numeric' => true],
        ['key' => 'students', 'label' => __('branches.fields.students'), 'numeric' => true],
        ['key' => 'status', 'label' => __('branches.fields.is_active')],
    ];

    $renderers = [
        'status' => fn (array $row) => new Illuminate\Support\HtmlString(
            Illuminate\Support\Facades\Blade::render(
                '<x-track-status-badge :status="$s" />',
                ['s' => $row['status'] ? 'active' : 'inactive'],
            )
        ),
    ];
@endphp

<x-app-layout :title="__('branches.title')">
    <x-track-page-header :title="__('branches.title')" :subtitle="__('branches.subtitle')">
        <x-slot:actions>
            @can('branch.create')
                <span x-data>
                    <x-track-button
                        variant="primary"
                        icon="plus"
                        x-on:click="$dispatch('track-dialog-open', { id: 'branch-dialog' })"
                    >{{ __('branches.actions.create') }}</x-track-button>
                </span>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    {{-- FR-049, said where someone would otherwise look for a delete button. --}}
    <x-track-alert tone="info" class="track-notice">{{ __('branches.notice') }}</x-track-alert>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('branches.title')"
            :empty="__('branches.empty.message')"
            empty-icon="building-2"
            :columns="$branchColumns"
            :rows="$rows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($branches->hasPages())
        <div class="track-pagination">{{ $branches->links() }}</div>
    @endif

    @can('branch.create')
        @include('branches._dialog')
    @endcan
</x-app-layout>
