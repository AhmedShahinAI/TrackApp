{{--
    T239 — the imports list, to the ImportsScreen reference.

    Scoped like every other list: a Super Admin sees every job, a coordinator the jobs that target
    their branches' sections, an instructor their own (ImportJob's global scope). The Resume and
    download actions are shown only where they can work — Resume to the person who owns the failed
    job, the report where there are errors — because a button that answers 403 or 404 when pressed
    is a button that should not have been drawn.

    The five counters are numerics and the status is a badge with its label, never colour alone
    (FR-077).
--}}

@use('App\Modules\Shared\Support\Format')

@php
    $locale = app()->getLocale();

    $rows = $jobs->getCollection()->map(fn (App\Models\ImportJob $job) => [
        'id' => $job->id,
        'file' => $job->original_filename,
        'mode' => __('imports.modes.'.$job->mode)
            .($job->section ? ' · '.$job->section->section_number : ''),
        'ran_by' => $job->user?->{'name_'.$locale} ?? $job->user?->name_ar ?? '—',
        'date' => Format::dateTime($job->created_at),
        '_sort' => ['date' => $job->created_at?->getTimestamp()],
        'rows' => Format::number($job->total_rows),
        'imported' => Format::number($job->imported),
        'duplicates' => Format::number($job->duplicates),
        'errors' => Format::number($job->errors),
        'status' => $job->status,
        'can_resume' => $job->canBeResumed() && auth()->user()?->can('run', $job),
        'has_errors' => $job->errors > 0 && in_array($job->status, ['completed', 'failed'], true),
    ])->all();

    $columns = [
        ['key' => 'file', 'label' => __('imports.fields.file')],
        ['key' => 'mode', 'label' => __('imports.fields.mode')],
        ['key' => 'ran_by', 'label' => __('imports.fields.ran_by')],
        ['key' => 'date', 'label' => __('imports.fields.date'), 'numeric' => true],
        ['key' => 'rows', 'label' => __('imports.fields.rows'), 'numeric' => true],
        ['key' => 'imported', 'label' => __('imports.fields.imported'), 'numeric' => true],
        ['key' => 'duplicates', 'label' => __('imports.fields.duplicates'), 'numeric' => true],
        ['key' => 'errors', 'label' => __('imports.fields.errors'), 'numeric' => true],
        ['key' => 'status', 'label' => __('imports.fields.status')],
        ['key' => 'actions', 'label' => __('imports.fields.actions'), 'sortable' => false],
    ];

    // The closure receives the ROW and its return is escaped unless it is an HtmlString — see
    // sections/index.blade.php for what each of those cost the first time.
    $renderers = [
        'file' => fn (array $row) => new Illuminate\Support\HtmlString(Illuminate\Support\Facades\Blade::render(
            '<a class="track-import-file" href="{{ $url }}"><x-track-icon name="file-spreadsheet" :size="18" /><bdi dir="ltr">{{ $file }}</bdi></a>',
            ['file' => $row['file'], 'url' => route('imports.show', $row['id'])],
        )),

        'status' => fn (array $row) => new Illuminate\Support\HtmlString(Illuminate\Support\Facades\Blade::render(
            '<x-track-status-badge :status="$tone">{{ $label }}</x-track-status-badge>',
            ['tone' => App\Models\ImportJob::BADGES[$row['status']] ?? 'draft', 'label' => __('imports.status.'.$row['status'])],
        )),

        'actions' => fn (array $row) => new Illuminate\Support\HtmlString(Illuminate\Support\Facades\Blade::render(
            '@if ($resume)'
                .'<form method="POST" action="{{ $resumeUrl }}" class="track-inline-form">@csrf'
                .'<x-track-button type="submit" variant="ghost" size="sm" icon="rotate-cw">{{ __(\'imports.actions.resume\') }}</x-track-button>'
                .'</form>'
            .'@elseif ($hasErrors)'
                .'<x-track-button variant="ghost" size="sm" icon="download" :href="$downloadUrl">{{ __(\'imports.actions.download_short\') }}</x-track-button>'
            .'@endif',
            [
                'resume' => $row['can_resume'],
                'hasErrors' => $row['has_errors'],
                'resumeUrl' => route('api.v1.imports.resume', $row['id']),
                'downloadUrl' => route('api.v1.imports.errors.download', $row['id']),
            ],
        )),
    ];
@endphp

<x-app-layout :title="__('imports.title')">
    <x-track-page-header :title="__('imports.title')" :subtitle="__('imports.subtitle')">
        <x-slot:actions>
            {{-- US8 — the course catalogue and staff accounts, from the imports list too. --}}
            @can('import.course.run')
                <x-track-button variant="secondary" icon="book-open" :href="route('imports.create.courses')">
                    {{ __('imports.actions.courses') }}
                </x-track-button>
            @endcan

            @can('import.user.run')
                <x-track-button variant="secondary" icon="user-plus" :href="route('imports.create.users')">
                    {{ __('imports.actions.users') }}
                </x-track-button>
            @endcan

            {{-- T334 — the registration unit's own export (FR-096), beside the central import. --}}
            @can('import.registrar.run')
                <x-track-button variant="secondary" icon="file-spreadsheet" :href="route('imports.create.registrar')">
                    {{ __('imports.actions.registrar') }}
                </x-track-button>
            @endcan

            @can('create', App\Models\ImportJob::class)
                <x-track-button variant="primary" icon="file-up" :href="route('imports.create')">
                    {{ __('imports.actions.central') }}
                </x-track-button>
            @elsecan('import.section.run')
                {{-- An instructor imports from a section's own page; this takes them to their sections,
                     as the reference's "استيراد شعبة" does. --}}
                <x-track-button variant="primary" icon="file-up" :href="route('sections.index')">
                    {{ __('imports.modes.section') }}
                </x-track-button>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    @if ($errors->has('conflict'))
        <x-track-alert tone="danger" class="mb-4">{{ $errors->first('conflict') }}</x-track-alert>
    @endif

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('imports.title')"
            :empty="auth()->user()?->can('create', App\Models\ImportJob::class) ? __('imports.empty.message') : __('imports.empty.instructor')"
            empty-icon="file-up"
            :columns="$columns"
            :rows="$rows"
            {{-- No row-url. A clickable row is role="link", and these rows hold a Resume form and a
                 download link: interactive controls inside a link are an axe violation and a click
                 on Resume would also navigate. The file name is the link instead. --}}
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($jobs->hasPages())
        <div class="track-pagination">{{ $jobs->links() }}</div>
    @endif
</x-app-layout>
