{{--
    T202 — the students list; T468 (Phase 26) — extended to v2's StudentsAdminScreen.

    A student has no branch and no section of their own; they are visible only through an enrollment
    the caller can see (Student::constrainToBranches). The branch, section and programme filters (T468)
    therefore narrow by the student's current enrollments, and can only narrow the caller's own scope.

    Each student appears once however many in-scope sections they are in (FR-043). That is held by a
    `whereExists` in the model rather than a `distinct()` here, so a future caller cannot forget it.
--}}

@php
    use App\Modules\Settings\Settings;

    $locale = app()->getLocale();

    /*
     * T468 — v2's StudentsAdminScreen, the part of it this release can build.
     *
     * Adopted: the programme column, the degree and activity columns (Amendment 1), the number of
     * sections the student holds in the caller's scope this term, the open-requests count, and the
     * search, branch, section and programme filters.
     *
     * Not built, by decision: the email, phone and password-state columns, the reset-password and
     * deactivate actions, and the New-student and Import dialogs. Each needs a student account or a
     * student contact, which C-19 … C-21 retired and C-23 blocks (NoStudentAuthColumnsTest). The
     * Import dialog maps to none of the five import modes, so it is raised as conflict C-24 rather than
     * built as a sixth (ui-conflicts.md). The initiatives column and filter wait for the initiatives
     * feature, which has no screen in this release.
     *
     * A student's programme, degree and activity belong to their sections, not to them (Amendment 1), so
     * each cell lists the distinct values across the sections loaded for this row.
     */
    $settingLabels = fn (string $key) => collect(Settings::options($key))
        ->mapWithKeys(fn (array $option) => [$option['value'] => $option[$locale] ?? $option['ar']]);
    $degreeLabels = $settingLabels(Settings::SECTION_DEGREES);
    $activityLabels = $settingLabels(Settings::SECTION_ACTIVITIES);
    $joined = fn ($values) => $values->filter()->unique()->values()->implode(__('students.list_separator')) ?: '—';

    $rows = $students->map(function ($student) use ($locale, $degreeLabels, $activityLabels, $joined) {
        $sections = $student->enrollments->pluck('section')->filter();

        return [
            'id' => $student->id,
            'name' => ($locale === 'en' ? $student->name_en : null) ?: $student->name_ar,
            'university_id' => $student->university_id,
            'program' => $joined($sections->map(fn ($section) => $section->program?->{'name_'.$locale} ?: $section->program?->name_ar)),
            'degree' => $joined($sections->map(fn ($section) => $degreeLabels[$section->degree] ?? $section->degree)),
            'activity' => $joined($sections->map(fn ($section) => $activityLabels[$section->activity] ?? $section->activity)),
            'sections' => $sections->count(),
            'open_requests' => (int) ($student->open_requests_count ?? 0),
        ];
    })->all();

    $studentColumns = [
        ['key' => 'name', 'label' => __('students.fields.name'), 'wrap' => true],
        ['key' => 'university_id', 'label' => __('students.fields.university_id'), 'numeric' => true],
        ['key' => 'program', 'label' => __('students.fields.program'), 'wrap' => true],
        ['key' => 'degree', 'label' => __('students.fields.degree')],
        ['key' => 'activity', 'label' => __('students.fields.activity')],
        ['key' => 'sections', 'label' => __('students.fields.sections'), 'numeric' => true],
    ];

    // FR-142: the column exists only for a caller who may see support requests at all.
    if ($seesRequests) {
        $studentColumns[] = ['key' => 'open_requests', 'label' => __('students.fields.open_requests'), 'numeric' => true];
    }

    /*
     * The university id is forced LTR with tabular figures. Two separate problems:
     *
     *   - A 9-digit id inside an RTL cell keeps its digits in logical order but the surrounding
     *     neutral characters reorder, so a copied id can come back with its parts swapped.
     *   - Proportional figures make columns of ids impossible to scan for the one that differs by
     *     a single digit, which is the comparison a registrar actually performs here.
     */
    $renderers = [
        'name' => fn (array $row) => new Illuminate\Support\HtmlString('<strong>'.e($row['name']).'</strong>'),
        'university_id' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr" class="track-code">'.e($row['university_id']).'</span>'
        ),
        'sections' => fn (array $row) => App\Modules\Shared\Support\Format::number($row['sections']),
        'open_requests' => fn (array $row) => $row['open_requests'] > 0
            ? new Illuminate\Support\HtmlString('<span class="track-students__open">'.e(App\Modules\Shared\Support\Format::number($row['open_requests'])).'</span>')
            : '—',
    ];

    $asOptions = fn (array $map) => collect($map)->map(fn (string $label, int|string $value) => ['value' => (string) $value, 'label' => $label])->values()->all();

    $subtitle = auth()->user()?->can('scope.bypass_all') ? __('students.subtitle_all') : __('students.subtitle_scoped');
@endphp

<x-app-layout :title="__('students.title')">
    <x-track-page-header :title="__('students.title')" :subtitle="$subtitle" />

    <x-track-card class="mb-4">
        <form method="GET" action="{{ route('students.index') }}" class="track-filters">
            <x-track-input
                name="q"
                size="sm"
                icon="search"
                class="track-filters__search"
                :label="__('students.filters.q')"
                :placeholder="__('students.filters.q_placeholder')"
                :value="$filters['q'] ?? ''"
            />

            @if ($filterOptions['branches'] !== [])
                <x-track-select name="branch_id" size="sm" :label="__('students.filters.branch')"
                    :options="$asOptions($filterOptions['branches'])" :placeholder="__('students.filters.all_branches')"
                    :selected="isset($filters['branch_id']) ? (string) $filters['branch_id'] : null" />
            @endif

            @if ($filterOptions['sections'] !== [])
                <x-track-select name="section_id" size="sm" :label="__('students.filters.section')"
                    :options="$asOptions($filterOptions['sections'])" :placeholder="__('students.filters.all_sections')"
                    :selected="isset($filters['section_id']) ? (string) $filters['section_id'] : null"
                    :hint="$filterOptions['sections_truncated'] ? __('students.filters.sections_truncated', ['count' => App\Modules\Shared\Support\Format::number($filterOptions['sections_cap'])]) : null" />
            @endif

            <x-track-select name="program_id" size="sm" :label="__('students.filters.program')"
                :options="$asOptions($filterOptions['programs'])" :placeholder="__('students.filters.all_programs')"
                :selected="isset($filters['program_id']) ? (string) $filters['program_id'] : null" />

            <div>
                <x-track-button type="submit" variant="secondary" size="sm" icon="filter">
                    {{ __('common.actions.filter') }}
                </x-track-button>
            </div>

            <p class="track-students__count" aria-live="polite">
                {{ trans_choice('students.count', $students->total(), ['count' => App\Modules\Shared\Support\Format::number($students->total())]) }}
            </p>
        </form>
    </x-track-card>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('students.title')"
            :empty="__('students.empty.message')"
            empty-icon="graduation-cap"
            :row-url="fn (array $row) => route('students.show', $row['id'])"
            :columns="$studentColumns"
            :rows="$rows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($students->hasPages())
        <div class="track-pagination">{{ $students->links() }}</div>
    @endif
</x-app-layout>
