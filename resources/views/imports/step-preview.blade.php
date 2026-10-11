{{--
    T244 — step 4, the preview (FR-061, FR-062, FR-082).

    Everything here is read from the staging table; nothing has been written yet, and the first line
    says so. The summary tiles are create-versus-match, then warnings and errors; the table is the
    first hundred rows, each with its own status badge and its reasons in the reader's language.

    The commit goes through a confirmation that restates the counts (FR-082). An operator who has just
    scrolled past a hundred rows has not necessarily read the totals at the top, and "Confirm" with no
    restatement is the moment a five-thousand-row mistake gets made.
--}}

@use('App\Modules\Shared\Support\Format')

@php
    $summary = $preview['summary'];
    $create = $summary['will_create'] ?? [];
    $match = $summary['will_match'] ?? [];
    $total = (int) ($summary['total_rows'] ?? 0);
    $matched = (int) ($match['students'] ?? 0);

    $rows = array_map(fn (array $row) => [
        'id' => $row['row_number'],
        'row' => $row['row_number'],
        'uid' => $row['values']['university_id'] ?? '',
        'student' => $row['values']['student_name'] ?? '',
        'section' => trim(($row['values']['course_code'] ?? '').' / '.($row['values']['section_number'] ?? ''), ' /'),
        'status' => $row['status'],
        'reasons' => $row['reasons'],
    ], $preview['rows']);

    // A section list's rows all go to the one section in the page header, so the column would repeat
    // it a hundred times.
    $columns = array_values(array_filter([
        ['key' => 'row', 'label' => __('imports.preview.row'), 'numeric' => true],
        ['key' => 'uid', 'label' => __('imports.targets.university_id'), 'numeric' => true],
        ['key' => 'student', 'label' => __('imports.preview.student')],
        $isSectionMode ? null : ['key' => 'section', 'label' => __('imports.preview.course_section'), 'numeric' => true],
        ['key' => 'status', 'label' => __('imports.fields.status')],
        ['key' => 'reasons', 'label' => __('imports.preview.reasons'), 'sortable' => false],
    ]));

    /*
     * US8 — a course or staff file previews its own columns: no student, no section. The reasons
     * column and the status badge are the same in every mode.
     */
    $bulkMode = in_array($job->mode, ['course', 'user'], true) ? $job->mode : null;

    if ($bulkMode !== null) {
        $rows = array_map(fn (array $row) => [
            'id' => $row['row_number'],
            'row' => $row['row_number'],
            'key' => $bulkMode === 'course' ? ($row['values']['course_code'] ?? '') : ($row['values']['email'] ?? ''),
            'name' => $row['values']['name_ar'] ?? '',
            'detail' => $bulkMode === 'course' ? ($row['values']['programs'] ?? '') : ($row['values']['role'] ?? ''),
            'status' => $row['status'],
            'reasons' => $row['reasons'],
        ], $preview['rows']);

        $columns = [
            ['key' => 'row', 'label' => __('imports.preview.row'), 'numeric' => true],
            ['key' => 'key', 'label' => __('imports.targets.'.($bulkMode === 'course' ? 'course_code' : 'email')), 'numeric' => true],
            ['key' => 'name', 'label' => __('imports.targets.name_ar')],
            ['key' => 'detail', 'label' => __('imports.targets.'.($bulkMode === 'course' ? 'programs' : 'role'))],
            ['key' => 'status', 'label' => __('imports.fields.status')],
            ['key' => 'reasons', 'label' => __('imports.preview.reasons'), 'sortable' => false],
        ];
    }

    $renderers = [
        'uid' => fn (array $row) => new Illuminate\Support\HtmlString('<bdi dir="ltr">'.e($row['uid'] !== '' ? $row['uid'] : '—').'</bdi>'),
        // A course code or an email: Latin text inside an RTL row, isolated as the id is.
        'key' => fn (array $row) => new Illuminate\Support\HtmlString('<bdi dir="ltr">'.e(($row['key'] ?? '') !== '' ? $row['key'] : '—').'</bdi>'),
        'status' => fn (array $row) => new Illuminate\Support\HtmlString(Illuminate\Support\Facades\Blade::render(
            '<x-track-status-badge :status="$s" />', ['s' => $row['status']],
        )),
        'reasons' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<ul class="track-import__reasons track-import__reasons--'.e($row['status']).'">'
            .implode('', array_map(fn (string $r) => '<li>'.e($r).'</li>', $row['reasons']))
            .'</ul>'
        ),
    ];

    $options = [
        'courses' => $job->option('create_missing_courses') ? __('imports.preview.option_create') : __('imports.preview.option_skip'),
        'instructors' => $job->option('create_missing_instructors') ? __('imports.preview.option_create') : __('imports.preview.option_skip'),
    ];
@endphp

<div class="track-form">
    <x-track-alert tone="info" icon="shield-check">{{ __('imports.preview.nothing_saved') }}</x-track-alert>

    @if ($duplicateOf)
        <x-track-alert tone="warning" icon="copy">{{ __('imports.upload.previously_imported') }}</x-track-alert>
    @endif

    @if ($total === 0)
        <x-track-alert tone="warning">{{ __('imports.preview.no_rows') }}</x-track-alert>
    @else
        @if ($bulkMode === 'course')
        <div class="track-stats">
            <x-track-stat-card :value="Format::number($create['courses'] ?? 0)" :label="__('imports.preview.new_courses')" tone="green" />
            <x-track-stat-card :value="Format::number($match['courses'] ?? 0)" :label="__('imports.preview.matched_courses')" />
            <x-track-stat-card :value="Format::number($create['programs'] ?? 0)" :label="__('imports.preview.new_programs')" />
            <x-track-stat-card :value="Format::number($summary['warnings'] ?? 0)" :label="__('imports.preview.warnings')" tone="dark" />
            <x-track-stat-card :value="Format::number($summary['errors'] ?? 0)" :label="__('imports.preview.errors')" tone="dark" />
        </div>

        <p class="track-import__hint">{{ __('imports.preview.course_options_chosen', [
            'programs' => $job->option('create_missing_programs') ? __('imports.preview.option_create') : __('imports.preview.option_skip'),
            'inactive' => $job->option('associate_inactive_programs') ? __('imports.preview.option_associate') : __('imports.preview.option_leave_out'),
        ]) }}</p>
        @elseif ($bulkMode === 'user')
        <div class="track-stats">
            <x-track-stat-card :value="Format::number($create['accounts'] ?? 0)" :label="__('imports.preview.new_accounts')" tone="green" />
            <x-track-stat-card :value="Format::number($summary['warnings'] ?? 0)" :label="__('imports.preview.warnings')" tone="dark" />
            <x-track-stat-card :value="Format::number($summary['errors'] ?? 0)" :label="__('imports.preview.errors')" tone="dark" />
        </div>

        {{-- FR-105, said before the commit as well as after it. --}}
        <x-track-alert tone="info" icon="key-round">{{ __('imports.preview.accounts_unusable') }}</x-track-alert>
        @else
        <div class="track-stats">
            @unless ($isSectionMode)
                <x-track-stat-card :value="Format::number($create['sections'] ?? 0)" :label="__('imports.preview.new_sections')" />
            @endunless
            <x-track-stat-card :value="Format::number($create['students'] ?? 0)" :label="__('imports.preview.new_students')" tone="green" />
            <x-track-stat-card :value="Format::number($create['enrollments'] ?? 0)" :label="__('imports.preview.new_enrollments')" tone="green" />
            <x-track-stat-card :value="Format::number($matched)" :label="__('imports.preview.matched')" />
            @if (($create['courses'] ?? 0) > 0)
                <x-track-stat-card :value="Format::number($create['courses'])" :label="__('imports.preview.new_courses')" />
            @endif
            @if (($create['instructors'] ?? 0) > 0)
                <x-track-stat-card :value="Format::number($create['instructors'])" :label="__('imports.preview.new_instructors')" />
            @endif
            <x-track-stat-card :value="Format::number($summary['warnings'] ?? 0)" :label="__('imports.preview.warnings')" tone="dark" />
            <x-track-stat-card :value="Format::number($summary['errors'] ?? 0)" :label="__('imports.preview.errors')" tone="dark" />
        </div>

        @unless ($isSectionMode)
            <p class="track-import__hint">{{ __('imports.preview.options_chosen', $options) }}</p>
        @endunless
        @endif

        {{-- Registrar mode (review M3): facts about the job's one section that no row carries — a
             degree or activity the settings list lacks, or an existing section that keeps its own. --}}
        @foreach ($summary['notices'] ?? [] as $notice)
            <x-track-alert tone="warning">{{ __('imports.registrar.notices.'.$notice['key'], $notice['params'] ?? []) }}</x-track-alert>
        @endforeach

        @if (($summary['will_skip'] ?? 0) > 0)
            <x-track-alert tone="warning">{{ __('imports.preview.will_skip', ['count' => Format::number($summary['will_skip'])]) }}</x-track-alert>
        @endif

        <h2 class="track-import__subhead">{{ __('imports.preview.first_rows') }}</h2>

        <x-track-data-table
            dense
            :caption="__('imports.preview.first_rows')"
            :columns="$columns"
            :rows="$rows"
            :renderers="$renderers"
            :empty="__('imports.preview.no_rows')"
        />
    @endif

    @if ($canRun && $job->status === 'validated')
        <div class="track-import__actions">
            <x-track-button variant="ghost" :href="route('imports.show', ['import' => $job, 'remap' => 1])">
                {{ __('imports.actions.remap') }}
            </x-track-button>

            @if ($total > 0)
                <span x-data>
                    <x-track-button
                        variant="primary"
                        icon="check"
                        x-on:click="$dispatch('track-dialog-open', { id: 'import-confirm' })"
                    >{{ __('imports.actions.confirm_import') }}</x-track-button>
                </span>
            @endif
        </div>
    @endif
</div>

@if ($canRun && $job->status === 'validated' && $total > 0)
    <x-track-dialog id="import-confirm" :title="__('imports.preview.confirm_title')">
        <p>
            @if ($duplicateOf)
                {{ __('imports.preview.confirm_duplicate') }}
            @elseif ($bulkMode === 'course')
                {{ __('imports.preview.confirm_courses', [
                    'courses' => Format::number($create['courses'] ?? 0),
                    'matched' => Format::number($match['courses'] ?? 0),
                    'programs' => Format::number($create['programs'] ?? 0),
                    'errors' => Format::number($summary['errors'] ?? 0),
                ]) }}
            @elseif ($bulkMode === 'user')
                {{ __('imports.preview.confirm_accounts', [
                    'accounts' => Format::number($create['accounts'] ?? 0),
                    'errors' => Format::number($summary['errors'] ?? 0),
                ]) }}
            @else
                {{ __('imports.preview.confirm_body', [
                    'sections' => Format::number($create['sections'] ?? 0),
                    'students' => Format::number($create['students'] ?? 0),
                    'enrollments' => Format::number($create['enrollments'] ?? 0),
                    'matched' => Format::number(($match['enrollments'] ?? 0) + $matched),
                    'errors' => Format::number($summary['errors'] ?? 0),
                ]) }}
            @endif
        </p>

        <form id="import-confirm-form" method="POST" action="{{ route('api.v1.imports.commit', $job) }}">
            @csrf
            <input type="hidden" name="confirm" value="1">
        </form>

        <x-slot:footer>
            {{-- form="", because the footer renders outside the <form> element and a submit button
                 there would otherwise submit nothing. --}}
            <x-track-button type="submit" variant="primary" icon="check" form="import-confirm-form">
                {{ __('imports.actions.confirm_import') }}
            </x-track-button>
        </x-slot:footer>
    </x-track-dialog>
@endif
