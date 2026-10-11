{{--
    T199 — the sections list, to the SectionsScreen reference. Also the screen US2's scope is
    visible on.

    ## There is no "all branches" option, and that absence is the feature

    The list a coordinator opens is already their branches, enforced by a global scope on the query
    rather than by a default on a filter someone could clear. The filter options themselves come
    from the caller's own scoped sections (SectionController::filterOptions), so the select cannot
    offer a branch, term, course or instructor they are not allowed to see. FR-023 requires the
    screen and the API to agree, and they do because they are the same query.

    ## The instructor variant

    An instructor has one branch, so the branch select is dropped rather than rendered with a single
    option — a control that cannot change anything is worse than no control. When they have no
    sections at all, the empty state says so in their own terms ("لم تُسند إليك أي شعبة بعد")
    instead of the generic "nothing in your scope", because for them the cause is specific and
    actionable: ask for an assignment.

    ## FR-026 — the shared badge

    A section reached through a grant carries it. The flag is computed per caller
    (Section::sharedAccess()), so the same row is badged for the instructor who was given it and
    unbadged for the one who teaches it.
--}}

@php
    $locale = app()->getLocale();

    $rows = $sections->map(fn ($section) => [
        'id' => $section->id,
        'section_number' => $section->section_number,
        'course' => $section->course?->code,
        // FR-094. Eager-loaded with the page (SectionController::index), so no query per row.
        'program' => $section->program?->{'name_'.$locale} ?? $section->program?->name_ar,
        'branch' => $section->branch?->{'campus_name_'.$locale} ?? $section->branch?->campus_name_ar,

        // Derived from the branch (FR-040), never stored on the section: one fact, one place.
        'gender' => __('sections.gender.'.$section->gender),
        'instructor' => $section->instructor?->{'name_'.$locale}
            ?? $section->instructor?->name_ar
            ?? __('sections.unassigned'),
        'enrollments' => $section->active_enrollments ?? 0,
        'shared' => $section->shared_access,
    ])->all();

    // Declared outside the attribute. A nested array literal inside a double-quoted Blade attribute
    // closes the attribute at its first inner double quote, which cost a day in US1.
    $sectionColumns = [
        ['key' => 'section_number', 'label' => __('sections.fields.number'), 'numeric' => true],
        ['key' => 'course', 'label' => __('sections.fields.course')],
        ['key' => 'program', 'label' => __('sections.fields.program')],
        ['key' => 'branch', 'label' => __('sections.fields.branch')],
        ['key' => 'gender', 'label' => __('sections.fields.gender')],
        ['key' => 'instructor', 'label' => __('sections.fields.instructor')],
        ['key' => 'enrollments', 'label' => __('sections.fields.enrollments'), 'numeric' => true],
        ['key' => 'shared', 'label' => __('sections.fields.access'), 'sortable' => false],
    ];

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
        'section_number' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr" class="track-code">'.e((string) $row['section_number']).'</span>'
        ),

        'shared' => fn (array $row) => $row['shared']
            ? new Illuminate\Support\HtmlString(
                Illuminate\Support\Facades\Blade::render('<x-track-status-badge status="shared" />')
            )
            : '',
    ];

    $hasFilters = request()->hasAny(['term_id', 'branch_id', 'course_id', 'program_id', 'instructor_id', 'q']);
@endphp

<x-app-layout :title="__('sections.title')">
    <x-track-page-header :title="__('sections.title')" :subtitle="__('sections.subtitle')">
        <x-slot:actions>
            @can('create', App\Models\Section::class)
                <span x-data>
                    <x-track-button
                        variant="primary"
                        icon="plus"
                        x-on:click="$dispatch('track-dialog-open', { id: 'section-dialog' })"
                    >{{ __('sections.actions.create') }}</x-track-button>
                </span>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    {{-- GET, so a filtered list is a URL someone can bookmark and send to a colleague — who, being
         scoped, sees their own slice of it rather than a 403 or somebody else's branch. --}}
    <x-track-card>
        <form method="GET" action="{{ route('sections.index') }}" class="track-filters">
            <x-track-select name="term_id" :label="__('sections.fields.term')"
                :options="$filters['terms']" :selected="request('term_id')"
                :placeholder="__('common.labels.all')" size="sm" />

            @if ($filters['has_multiple_branches'])
                <x-track-select name="branch_id" :label="__('sections.fields.branch')"
                    :options="$filters['branches']" :selected="request('branch_id')"
                    :placeholder="__('common.labels.all')" size="sm" />
            @endif

            <x-track-select name="course_id" :label="__('sections.fields.course')"
                :options="$filters['courses']" :selected="request('course_id')"
                :placeholder="__('common.labels.all')" size="sm" />

            {{-- FR-094 — the programme is a filter as well as a column. --}}
            <x-track-select name="program_id" :label="__('sections.fields.program')" icon="graduation-cap"
                :options="$filters['programs']" :selected="request('program_id')"
                :placeholder="__('common.labels.all')" size="sm" />

            <x-track-select name="instructor_id" :label="__('sections.fields.instructor')"
                :options="$filters['instructors']" :selected="request('instructor_id')"
                :placeholder="__('common.labels.all')" size="sm" />

            <x-track-input name="q" :label="__('sections.fields.number')" :value="request('q')"
                class="track-filters__search" dir="ltr" size="sm" />

            <x-track-button type="submit" variant="secondary" icon="filter">
                {{ __('common.actions.filter') }}
            </x-track-button>

            @if ($hasFilters)
                <x-track-button variant="ghost" :href="route('sections.index')">
                    {{ __('common.actions.clear') }}
                </x-track-button>
            @endif
        </form>
    </x-track-card>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('sections.title')"
            {{-- Keyed to WHY the list is empty, not to how many branches there are. The branch count
                 decides whether a filter is useful; it says nothing about whether this person is
                 waiting on an assignment — and a Super Admin with no sections was being told that
                 none had been assigned to them. --}}
            :empty="$filters['scoped_by_assignment'] ? __('sections.empty.instructor') : __('sections.empty.message')"
            empty-icon="users"
            :columns="$sectionColumns"
            :rows="$rows"
            :renderers="$renderers"
            {{-- A closure, not the string "/sections/{id}". The component uses a non-callable rowUrl
                 verbatim, so the literal placeholder linked every row at a URL that 404s. --}}
            :row-url="fn (array $row) => route('sections.show', $row['id'])"
        />
    </x-track-card>

    @if ($sections->hasPages())
        <div class="track-pagination">{{ $sections->links() }}</div>
    @endif

    @can('create', App\Models\Section::class)
        @include('sections._dialog')
    @endcan
</x-app-layout>
