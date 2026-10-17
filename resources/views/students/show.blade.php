{{--
    T202 — one student, to the StudentDetail reference.

    This page is the proof of FR-043 from the other direction: one record, with every enrollment the
    caller can see listed beneath it. A withdrawn enrollment stays in the table, greyed, with its
    reason beside it — removing it would turn "withdrew in week three, because X" into "was never
    here", which is exactly the history the platform exists to keep (constitution V).

    The enrollments are already scoped: Student::enrollments() runs through SectionEnrollment, which
    carries the section scope. There is no visibility check in this template and there must not be.
--}}

@use('App\Modules\Shared\Support\Format')

@php
    $locale = app()->getLocale();

    // T468 — v2 shows each section's programme, degree and activity (Amendment 1); they belong to the
    // section, so they are columns of the enrollment, not fields of the student.
    $optionLabels = fn (string $key) => collect(App\Modules\Settings\Settings::options($key))
        ->mapWithKeys(fn (array $option) => [$option['value'] => $option[$locale] ?? $option['ar']]);
    $degreeLabels = $optionLabels(App\Modules\Settings\Settings::SECTION_DEGREES);
    $activityLabels = $optionLabels(App\Modules\Settings\Settings::SECTION_ACTIVITIES);

    $enrollmentRows = $student->enrollments->map(fn ($enrollment) => [
        'id' => $enrollment->id,
        'section' => $enrollment->section?->section_number,
        'course' => $enrollment->section?->course?->code,
        'program' => ($enrollment->section?->program?->{'name_'.$locale} ?: $enrollment->section?->program?->name_ar) ?? '—',
        'degree' => $degreeLabels[$enrollment->section?->degree] ?? ($enrollment->section?->degree ?? '—'),
        'activity' => $activityLabels[$enrollment->section?->activity] ?? ($enrollment->section?->activity ?? '—'),
        'branch' => $enrollment->section?->branch?->{'campus_name_'.$locale}
            ?? $enrollment->section?->branch?->campus_name_ar,
        'enrolled_at' => Format::date($enrollment->enrolled_at),
        '_sort' => ['enrolled_at' => $enrollment->enrolled_at?->getTimestamp()],
        'status' => $enrollment->status,
        'reason' => $enrollment->withdraw_reason,
        'withdrawn' => $enrollment->status === 'withdrawn',
    ])->all();

    $enrollmentColumns = [
        ['key' => 'section', 'label' => __('students.fields.section'), 'numeric' => true],
        ['key' => 'course', 'label' => __('students.fields.course')],
        ['key' => 'program', 'label' => __('students.fields.program'), 'wrap' => true],
        ['key' => 'degree', 'label' => __('students.fields.degree')],
        ['key' => 'activity', 'label' => __('students.fields.activity')],
        ['key' => 'branch', 'label' => __('students.fields.branch')],
        ['key' => 'enrolled_at', 'label' => __('students.fields.enrolled_at')],
        ['key' => 'status', 'label' => __('students.fields.status')],
    ];

    $renderers = [
        'section' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr" class="track-code">'.e((string) $row['section']).'</span>'
        ),

        /*
         * The badge and the reason in one cell. The reason has to be *here*, next to the status it
         * explains: in its own column it is blank for every active row, which reads as a column
         * that is broken rather than one that only applies sometimes.
         */
        'status' => fn (array $row) => new Illuminate\Support\HtmlString(
            Illuminate\Support\Facades\Blade::render(
                '<x-track-status-badge :status="$s" />'
                .'@if ($reason)<p class="track-table__note">{{ $reason }}</p>@endif',
                ['s' => $row['status'], 'reason' => $row['reason']],
            )
        ),
    ];

    // Greys the whole row rather than just the badge, so a withdrawn enrollment is distinguishable
    // at a glance down the table and not only by reading each status cell.
    $rowClass = fn (array $row) => $row['withdrawn'] ? 'track-table__row--muted' : null;
@endphp

<x-app-layout :title="$student->name_ar">
    <x-track-breadcrumbs :items="[
        ['label' => __('students.title'), 'url' => route('students.index')],
        ['label' => $student->{'name_'.$locale} ?? $student->name_ar],
    ]" />

    <x-track-page-header
        :title="$student->{'name_'.$locale} ?? $student->name_ar"
        :subtitle="$student->university_id"
    />

    <div x-data="{ tab: 'enrollments' }" x-on:track-tab-changed="tab = $event.detail.tab">
        <x-track-tabs
            id="student-tabs"
            :panels="true"
            :tabs="[
                'enrollments' => __('students.tabs.enrollments'),
                'activity' => __('students.tabs.activity'),
            ]"
        />

        <div
            id="student-tabs-panel-enrollments"
            role="tabpanel"
            aria-labelledby="student-tabs-tab-enrollments"
            x-show="tab === 'enrollments'"
        >
            <x-track-card :padding="0">
                <x-track-data-table
                    :caption="__('students.fields.enrollments')"
                    :empty="__('students.empty.enrollments')"
                    empty-icon="clipboard-list"
                    :columns="$enrollmentColumns"
                    :rows="$enrollmentRows"
                    :renderers="$renderers"
                    :row-class="$rowClass"
                />
            </x-track-card>
        </div>

        {{-- One Alpine scope around the strip AND both panels. Rendering the panels as siblings of
             x-track-tabs puts them outside its x-data, so nothing hides either and both show at
             once — the mistake sections/show.blade.php records. --}}
        <div
            id="student-tabs-panel-activity"
            role="tabpanel"
            aria-labelledby="student-tabs-tab-activity"
            x-show="tab === 'activity'"
            x-cloak
        >
            <x-track-card :padding="0">
                <x-track-activity-tab entity-type="Student" :entity-id="$student->id" />
            </x-track-card>
        </div>
    </div>

    @include('students._withdraw-dialog')
</x-app-layout>
