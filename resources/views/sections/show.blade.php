{{--
    T200 — one section, to the SectionDetail + SectionMeta + RosterTable reference.

    Reached only through the scoped route binding, so by the time this renders the caller is known
    to be allowed to see it — there is no visibility check in the template, and there must not be
    one, or the rule would live in two places and drift.

    Three tabs, which is three different questions about the same section:

      * Roster — who is enrolled, including who withdrew and why. A withdrawn row stays, greyed,
        with its reason: dropping it would answer "who is here" and lose "who left", and the reason
        WithdrawEnrollment insists on would be written and never read.
      * Instructor history — who has taught it, and who made each handover. Note that `changed_by`
        is a different person from `instructor_id`: the one who performed the reassignment and the
        one who received it. A column showing only the latter cannot answer "who moved this".
      * Activity — the audit trail, filtered to this record, from the same scoped query the audit
        screen uses, so a reader sees here exactly what they would see there.
--}}

@use('App\Modules\Shared\Support\Format')

@php
    $locale = app()->getLocale();

    $rosterRows = $roster->map(fn ($enrollment) => [
        'id' => $enrollment->id,
        'roster_sequence' => $enrollment->roster_sequence,
        'academic_status' => $enrollment->academic_status,
        'university_id' => $enrollment->student?->university_id,
        'name' => $enrollment->student?->{'name_'.$locale} ?? $enrollment->student?->name_ar,
        'enrolled_at' => Format::date($enrollment->enrolled_at),
        '_sort' => ['enrolled_at' => $enrollment->enrolled_at?->getTimestamp()],
        'status' => $enrollment->status,
        'reason' => $enrollment->withdraw_reason,
        'withdrawn' => $enrollment->status === 'withdrawn',
        'student_id' => $enrollment->student_id,
    ])->all();

    $rosterColumns = [
        ['key' => 'university_id', 'label' => __('students.fields.university_id'), 'numeric' => true],
        // The reader's-language name (above), so the column is "Name", not "Name in Arabic".
        ['key' => 'name', 'label' => __('students.fields.name')],
        ['key' => 'enrolled_at', 'label' => __('students.fields.enrolled_at')],
        ['key' => 'status', 'label' => __('students.fields.status')],

        /*
         * FR-111 — the registrar's academic_status in its own column, never folded into TRACK's
         * status. They answer different questions: `status` is whether TRACK still counts the
         * student in this section, `academic_status` is what the registrar's file last said. A
         * student excused by the registrar is still enrolled here, and merging the two would make
         * one of those facts disappear.
         */
        ['key' => 'academic_status', 'label' => __('students.fields.academic_status')],
        ['key' => 'actions', 'label' => __('common.labels.actions'), 'sortable' => false],
    ];

    // The registrar's sequence leads, where the roster came from a registrar file and has one.
    // A column of dashes on a hand-built roster would read as data that went missing. Decided for
    // the whole section (SectionController::show), so every page of one roster has the same columns.
    if ($sequenced ?? false) {
        array_unshift($rosterColumns, ['key' => 'roster_sequence', 'label' => __('students.fields.roster_sequence'), 'numeric' => true]);
    }

    $historyRows = $section->instructorHistory->map(fn ($row) => [
        'id' => $row->id,
        'instructor' => $row->instructor?->{'name_'.$locale} ?? $row->instructor?->name_ar,
        'from' => Format::date($row->assigned_at),
        'to' => $row->unassigned_at ? Format::date($row->unassigned_at) : __('sections.history.current'),
        // The current assignment has no end, so it sorts last.
        '_sort' => ['from' => $row->assigned_at?->getTimestamp(), 'to' => $row->unassigned_at?->getTimestamp() ?? PHP_INT_MAX],

        // The author of the handover, not its recipient. Two different people, one easily mistaken
        // for the other, and only this column can answer "who moved it".
        'changed_by' => $row->changedBy?->{'name_'.$locale} ?? $row->changedBy?->name_ar,
    ])->all();

    $historyColumns = [
        ['key' => 'instructor', 'label' => __('sections.fields.instructor')],
        ['key' => 'from', 'label' => __('sections.history.from')],
        ['key' => 'to', 'label' => __('sections.history.to')],
        ['key' => 'changed_by', 'label' => __('sections.history.changed_by')],
    ];

    $canWithdraw = auth()->user()?->can('enrollment.withdraw') ?? false;

    $rosterRenderers = [
        'university_id' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr" class="track-code">'.e((string) $row['university_id']).'</span>'
        ),

        // Its own badge; empty for an enrollment no registrar file has described yet.
        'academic_status' => fn (array $row) => $row['academic_status'] === null
            ? ''
            : new Illuminate\Support\HtmlString(
                Illuminate\Support\Facades\Blade::render(
                    '<x-track-status-badge :status="$s" />',
                    ['s' => $row['academic_status']],
                )
            ),

        // Badge and reason in one cell: in its own column the reason is blank for every active row,
        // which reads as a broken column rather than one that only sometimes applies.
        'status' => fn (array $row) => new Illuminate\Support\HtmlString(
            Illuminate\Support\Facades\Blade::render(
                '<x-track-status-badge :status="$s" />'
                .'@if ($reason)<p class="track-table__note">{{ $reason }}</p>@endif',
                ['s' => $row['status'], 'reason' => $row['reason']],
            )
        ),

        /*
         * The per-row withdraw action, which carries its target to the one shared dialog.
         *
         * `@js($detail)` rather than a hand-quoted object literal: a student named "O'Hara" would
         * close the attribute, and encoding it is Blade's job, not mine. The previous version
         * opened a per-row dialog — 400 modals for a 400-student roster — and before that it passed
         * a bare `$id`, which Blade leaves alone inside an attribute, so Alpine resolved it to its
         * own `$id` magic helper: the event carried a function, nothing matched, and the button did
         * nothing at all. Neither mistake looks wrong in the markup.
         */
        'actions' => fn (array $row) => $row['withdrawn'] || ! $canWithdraw
            ? new Illuminate\Support\HtmlString('')
            : new Illuminate\Support\HtmlString(
                Illuminate\Support\Facades\Blade::render(
                    '<span x-data><x-track-button size="sm" variant="ghost" icon="user-minus"'
                    /*
                     * The target travels in data attributes, escaped by Blade, and the handler reads
                     * `$el.dataset` — as the account row actions do. Not `Js::from($detail)`: for an
                     * array it compiles to `JSON.parse(...)`, and the CSP build of Alpine (security
                     * review R-01) has no access to `JSON`, so the button did nothing.
                     */
                    .' data-enrollment="{{ $detail[\'enrollment\'] }}" data-student="{{ $detail[\'student\'] }}" data-action="{{ $detail[\'action\'] }}"'
                    .' x-on:click="$dispatch(\'track-withdraw-open\', { enrollment: $el.dataset.enrollment, student: $el.dataset.student, action: $el.dataset.action })">'
                    .'{{ $label }}</x-track-button></span>',
                    [
                        'detail' => [
                            'enrollment' => $row['id'],
                            'student' => $row['name'],
                            'action' => route('api.v1.enrollments.withdraw', $row['id']),
                        ],
                        'label' => __('students.actions.withdraw'),
                    ],
                )
            ),
    ];

    $rowClass = fn (array $row) => $row['withdrawn'] ? 'track-table__row--muted' : null;
@endphp

<x-app-layout :title="$section->section_number">
    <x-track-breadcrumbs :items="[
        ['label' => __('sections.title'), 'url' => route('sections.index')],
        ['label' => __('sections.detail.title', ['number' => $section->section_number])],
    ]" />

    <x-track-page-header
        :title="__('sections.detail.title', ['number' => $section->section_number])"
        {{-- FR-094 — the programme on the detail too, as the reference's subtitle leads with it. Loaded
             with trashed, so a deactivated or deleted programme still names the section. --}}
        :subtitle="collect([
            $section->course?->{'name_'.$locale} ?? $section->course?->name_ar,
            $section->program?->{'name_'.$locale} ?? $section->program?->name_ar,
        ])->filter()->implode(' · ')"
    >
        @if ($section->shared_access)
            {{-- FR-026. Computed per caller, so the same row is badged for the instructor who was
                 granted it and unbadged for the one who teaches it. --}}
            <x-track-status-badge status="shared" />
        @endif

        <x-slot:actions>
            {{-- US6 — the reference's "استيراد كشف": only for a section the caller teaches or holds a
                 grant for (SectionPolicy::importRoster), not every section they can see. --}}
            @can('importRoster', $section)
                <x-track-button variant="secondary" icon="file-up" :href="route('sections.import', $section)">
                    {{ __('sections.actions.import_list') }}
                </x-track-button>
            @endcan

            @can('changeInstructor', $section)
                <span x-data>
                    <x-track-button
                        variant="secondary"
                        icon="user-cog"
                        x-on:click="$dispatch('track-dialog-open', { id: 'assign-dialog' })"
                    >{{ __('sections.actions.change_instructor') }}</x-track-button>
                </span>
            @endcan

            @can('enrollment.create')
                <span x-data>
                    <x-track-button
                        variant="primary"
                        icon="user-plus"
                        x-on:click="$dispatch('track-dialog-open', { id: 'enroll-dialog' })"
                    >{{ __('students.actions.enroll') }}</x-track-button>
                </span>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    {{-- The metadata panel, above the tabs rather than inside one. It answers "which section am I
         looking at", which every tab needs, so putting it behind a tab hides the context for the
         other two. --}}
    <x-track-card class="track-meta">
        <dl class="track-dl">
            <div><dt>{{ __('sections.fields.course') }}</dt><dd>{{ $section->course?->code }}</dd></div>
            <div><dt>{{ __('sections.fields.branch') }}</dt><dd>{{ $section->branch?->{'campus_name_'.$locale} ?? $section->branch?->campus_name_ar }}</dd></div>
            {{-- Derived from the branch, never stored on the section: one fact, one place. --}}
            <div><dt>{{ __('sections.fields.gender') }}</dt><dd>{{ __('sections.gender.'.$section->gender) }}</dd></div>
            <div><dt>{{ __('sections.fields.term') }}</dt><dd>{{ Format::term($section->term) }}</dd></div>
            <div>
                <dt>{{ __('sections.fields.instructor') }}</dt>
                <dd>{{ $section->instructor?->{'name_'.$locale} ?? $section->instructor?->name_ar ?? __('sections.unassigned') }}</dd>
            </div>
            <div><dt>{{ __('sections.fields.enrollments') }}</dt><dd>{{ $activeEnrollments }}</dd></div>
        </dl>
    </x-track-card>

    {{-- One Alpine scope around the strip AND the panels.

         The first version rendered the panels as siblings of <x-track-tabs>, outside its x-data,
         with nothing to hide either — so both were visible at once and the tab strip changed only
         its own highlight. trackTabs dispatches `track-tab-changed` on select precisely so a caller
         can hold the panel state; this listens for it. --}}
    <div x-data="{ tab: 'roster' }" x-on:track-tab-changed="tab = $event.detail.tab">
        <x-track-tabs
            id="section-tabs"
            :panels="true"
            :tabs="[
                'roster' => __('sections.tabs.roster'),
                'history' => __('sections.tabs.history'),
                'activity' => __('sections.tabs.activity'),
            ]"
        />

        <div
            id="section-tabs-panel-roster"
            role="tabpanel"
            aria-labelledby="section-tabs-tab-roster"
            x-show="tab === 'roster'"
        >
            <x-track-card :padding="0">
                <x-track-data-table
                    :caption="__('sections.tabs.roster')"
                    :empty="__('sections.empty.roster')"
                    empty-icon="users"
                    :columns="$rosterColumns"
                    :rows="$rosterRows"
                    :renderers="$rosterRenderers"
                    :row-class="$rowClass"
                />
            </x-track-card>

            @if ($roster->hasPages())
                <div class="track-pagination">{{ $roster->links() }}</div>
            @endif
        </div>

        <div
            id="section-tabs-panel-history"
            role="tabpanel"
            aria-labelledby="section-tabs-tab-history"
            x-show="tab === 'history'"
            x-cloak
        >
            <x-track-card :padding="0">
                <x-track-data-table
                    :caption="__('sections.tabs.history')"
                    :empty="__('sections.empty.history')"
                    empty-icon="rotate-ccw"
                    :columns="$historyColumns"
                    :rows="$historyRows"
                />
            </x-track-card>
        </div>

        <div
            id="section-tabs-panel-activity"
            role="tabpanel"
            aria-labelledby="section-tabs-tab-activity"
            x-show="tab === 'activity'"
            x-cloak
        >
            <x-track-card :padding="0">
                <x-track-activity-tab entity-type="Section" :entity-id="$section->id" />
            </x-track-card>
        </div>
    </div>

    @can('enrollment.create')
        @include('students._enroll-dialog', ['section' => $section])
    @endcan

    @include('students._withdraw-dialog')

    @can('changeInstructor', $section)
        @include('sections._assign-dialog', ['section' => $section])
    @endcan
</x-app-layout>
