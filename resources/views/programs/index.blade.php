{{--
    T317 — academic programmes, to the ProgramsScreen reference (FR-089 to FR-095).

    The code is forced LTR and bold, as on the courses screen: `RE` inside an RTL row is still `RE`,
    but a longer code with a digit or hyphen is reordered by the bidi algorithm without isolation.

    Edit is one shared dialog opened by event (programs._dialog), not one dialog per row — the row's
    values travel in data attributes, as the account rows do.
--}}

@php
    $locale = app()->getLocale();

    $rows = $programs->map(fn ($program) => [
        'model' => $program,
        'id' => $program->id,
        'code' => $program->code,
        'name_ar' => $program->name_ar,
        'name_en' => $program->name_en,
        'unit' => __('programs.units.'.$program->unit),
        'courses' => $program->courses_count ?? 0,
        'sections' => $program->sections_count ?? 0,
        'status' => (bool) $program->is_active,
    ])->all();

    $programColumns = [
        ['key' => 'code', 'label' => __('programs.fields.code')],
        ['key' => 'name_ar', 'label' => __('programs.fields.name_ar')],
        ['key' => 'name_en', 'label' => __('programs.fields.name_en')],
        ['key' => 'unit', 'label' => __('programs.fields.unit')],
        ['key' => 'courses', 'label' => __('programs.fields.courses'), 'numeric' => true],
        ['key' => 'sections', 'label' => __('programs.fields.sections'), 'numeric' => true],
        ['key' => 'status', 'label' => __('programs.fields.is_active')],
    ];

    if (auth()->user()?->can('program.edit')) {
        $programColumns[] = ['key' => 'row_actions', 'label' => __('common.actions.more'), 'sortable' => false];
    }

    $renderers = [
        'code' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<b dir="ltr" class="track-code">'.e($row['code']).'</b>'
        ),

        'name_en' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr">'.e($row['name_en']).'</span>'
        ),

        'status' => fn (array $row) => new Illuminate\Support\HtmlString(
            Illuminate\Support\Facades\Blade::render(
                '<x-track-status-badge :status="$s" />',
                ['s' => $row['status'] ? 'active' : 'inactive'],
            )
        ),

        'row_actions' => fn (array $row) => new Illuminate\Support\HtmlString(
            view('programs._row-actions', ['program' => $row['model']])->render()
        ),
    ];
@endphp

<x-app-layout :title="__('programs.title')">
    <x-track-page-header :title="__('programs.title')" :subtitle="__('programs.subtitle')">
        <x-slot:actions>
            @can('import.course.run')
                {{-- T349 — the reference's "استيراد المقررات من Excel": straight to the course import. --}}
                <x-track-button variant="secondary" icon="file-spreadsheet" :href="route('imports.create.courses')">
                    {{ __('programs.actions.import_courses') }}
                </x-track-button>
            @endcan

            @can('program.create')
                <span x-data>
                    <x-track-button
                        variant="primary"
                        icon="plus"
                        x-on:click="$dispatch('track-dialog-open', { id: 'program-dialog' })"
                    >{{ __('programs.actions.create') }}</x-track-button>
                </span>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    {{-- Searched on the server, not in the table. The list is paginated, so the data table's own
         client-side search would look only at the 25 rows on this page and report "no results" for
         a programme on page 2, which invites creating a duplicate. GET, so a search is a URL. --}}
    <x-track-card>
        <form method="GET" action="{{ route('programs.index') }}" class="track-filters" role="search">
            <x-track-input name="q" :label="__('programs.filters.search')" :value="request('q')"
                class="track-filters__search" icon="search" size="sm" />

            <x-track-select name="unit" :label="__('programs.fields.unit')"
                :options="['admin' => __('programs.units.admin'), 'tech' => __('programs.units.tech')]"
                :selected="request('unit')" :placeholder="__('common.labels.all')" size="sm" />

            <x-track-select name="is_active" :label="__('programs.fields.is_active')"
                {{-- Value/label rows, not a ['1' => …, '0' => …] map: written the other way round, the
                     keys would be 0..1, read as a list, and submit their labels (review L1). --}}
                :options="[['value' => '1', 'label' => __('status.active')], ['value' => '0', 'label' => __('status.inactive')]]"
                :selected="request('is_active')" :placeholder="__('common.labels.all')" size="sm" />

            <x-track-button type="submit" variant="secondary" icon="filter">
                {{ __('common.actions.filter') }}
            </x-track-button>

            @if (request()->hasAny(['q', 'unit', 'is_active']))
                <x-track-button variant="ghost" :href="route('programs.index')">
                    {{ __('common.actions.clear') }}
                </x-track-button>
            @endif
        </form>
    </x-track-card>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('programs.title')"
            :empty="request()->hasAny(['q', 'unit', 'is_active']) ? __('programs.empty.filtered') : __('programs.empty.message')"
            empty-icon="graduation-cap"
            :columns="$programColumns"
            :rows="$rows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($programs->hasPages())
        <div class="track-pagination">{{ $programs->links() }}</div>
    @endif

    @if (auth()->user()?->can('program.create') || auth()->user()?->can('program.edit'))
        @include('programs._dialog')
    @endif
</x-app-layout>
