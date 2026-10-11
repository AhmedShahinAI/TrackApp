{{--
    T196 — the terms list, to the TermsScreen reference.

    Two things on this screen are destructive in a way a button label does not convey, so both are
    confirmations rather than plain actions:

      * A status change moves the term along ChangeTermStatus::TRANSITIONS, and leaving `closed`
        requires a reason. The dialog names the from-state and the to-state rather than saying
        "change status", because "active → closed" and "closed → active" are very different acts.
      * Setting the current term CLEARS the previous one. That is a single-row invariant held in a
        locked transaction, and the person clicking has no way to see the term they are about to
        displace unless this screen says so.

    `allowed_transitions` comes from the controller, so Blade never re-derives the state machine.
    A second copy of TRANSITIONS here would be the copy that goes stale.
--}}

@use('App\Modules\Shared\Support\Format')

@php
    $locale = app()->getLocale();

    $rows = $terms->map(fn ($term) => [
        'id' => $term->id,
        'name' => $term->{'name_'.$locale} ?? $term->name_ar,
        'academic_year' => $term->academic_year,
        'semester' => __('terms.semesters.'.$term->semester),
        'dates' => Format::date($term->start_date).' — '.Format::date($term->end_date),
        '_sort' => ['dates' => $term->start_date?->getTimestamp()],
        'sections' => $term->sections_count ?? 0,
        'status' => $term->status,
        'is_current' => (bool) $term->is_current,
    ])->all();

    $termColumns = [
        ['key' => 'name', 'label' => __('terms.fields.name')],
        ['key' => 'academic_year', 'label' => __('terms.fields.academic_year'), 'numeric' => true],
        ['key' => 'semester', 'label' => __('terms.fields.semester')],
        ['key' => 'dates', 'label' => __('terms.fields.start_date')],
        ['key' => 'sections', 'label' => __('terms.fields.sections'), 'numeric' => true],
        ['key' => 'status', 'label' => __('terms.fields.status')],
    ];

    // The closure receives the ROW, not the cell value, and its return is escaped unless it is an
    // HtmlString — both of which cost a baseline in US2. See sections/index.blade.php.
    $renderers = [
        'status' => fn (array $row) => new Illuminate\Support\HtmlString(
            Illuminate\Support\Facades\Blade::render(
                '<x-track-status-badge :status="$s" />'
                .'@if ($current)<x-track-status-badge status="current" icon="star" class="track-badge--trailing" />@endif',
                ['s' => $row['status'], 'current' => $row['is_current']],
            )
        ),
    ];
@endphp

<x-app-layout :title="__('terms.title')">
    <x-track-page-header :title="__('terms.title')" :subtitle="__('terms.subtitle')">
        <x-slot:actions>
            @can('term.create')
                <span x-data>
                    <x-track-button
                        variant="primary"
                        icon="plus"
                        x-on:click="$dispatch('track-dialog-open', { id: 'term-dialog' })"
                    >{{ __('terms.actions.create') }}</x-track-button>
                </span>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('terms.title')"
            :empty="__('terms.empty.message')"
            empty-icon="calendar-range"
            :columns="$termColumns"
            :rows="$rows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($terms->hasPages())
        <div class="track-pagination">{{ $terms->links() }}</div>
    @endif

    @include('terms._dialog')
</x-app-layout>
