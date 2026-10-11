{{--
    T246 — step 6, the report (FR-070, FR-071, FR-072).

    The four approved counters, in the approved order: processed · imported · duplicates · errors.
    The error-report button appears only when there is a report to download — the endpoint answers
    404 otherwise, and a button that leads to a 404 is a defect in the screen, not the server.
--}}

@use('App\Modules\Shared\Support\Format')

<div class="track-form">
    <x-track-alert tone="success" :title="__('imports.report.done')">
        <bdi dir="ltr">{{ $job->original_filename }}</bdi>
    </x-track-alert>

    <div class="track-stats track-stats--four">
        <x-track-stat-card :value="Format::number($job->processed)" :label="__('imports.fields.processed')" />
        <x-track-stat-card :value="Format::number($job->imported)" :label="__('imports.fields.imported')" tone="green" />
        <x-track-stat-card :value="Format::number($job->duplicates)" :label="__('imports.fields.duplicates')" />
        <x-track-stat-card :value="Format::number($job->errors)" :label="__('imports.fields.errors')" tone="dark" />
    </div>

    <div class="track-import__actions">
        @if ($job->errors > 0)
            <x-track-button variant="secondary" icon="download" :href="route('api.v1.imports.errors.download', $job)">
                {{ __('imports.actions.download_errors') }}
            </x-track-button>
        @endif

        @if ($isSectionMode && $section !== null)
            <x-track-button variant="secondary" :href="route('sections.show', $section)">{{ __('imports.actions.open_section') }}</x-track-button>
        @endif

        <x-track-button variant="ghost" :href="route('imports.index')">{{ __('imports.actions.back_to_list') }}</x-track-button>

        @if ($isSectionMode && $section !== null)
            @can('importRoster', $section)
                <x-track-button variant="primary" icon="rotate-ccw" :href="route('sections.import', $section)">{{ __('imports.actions.new_import') }}</x-track-button>
            @endcan
        @else
            @can('create', App\Models\ImportJob::class)
                <x-track-button variant="primary" icon="rotate-ccw" :href="route('imports.create')">{{ __('imports.actions.new_import') }}</x-track-button>
            @endcan
        @endif
    </div>
</div>

<x-track-toast tone="success">{{ __('imports.report.done') }}</x-track-toast>
