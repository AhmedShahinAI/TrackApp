{{--
    T086 — x-track-data-table.

    Client-side sort and filter without a reload, a loading state built from skeleton rows, an empty
    state built from x-track-empty-state, and — the part that matters most — rows that become
    stacked cards below 768px (FR-085). A 9-column roster on a phone as a table means horizontal
    scrolling, and horizontal scrolling hides exactly the column someone is looking for.

    `columns` is a list of:
        ['key' => 'name_ar', 'label' => '...', 'numeric' => false, 'sortable' => true]

    A column with a slot named after its key renders through that slot instead of printing the
    value, which is how a status cell becomes an x-track-status-badge without this component
    knowing what a status is.

    Sorting is Alpine over the rendered DOM rather than over a JS copy of the data. That keeps one
    source of truth — the markup Blade produced, including the slot-rendered cells — so a sorted
    table cannot show different content from an unsorted one.
--}}

@props([
    'columns' => [],
    'rows' => [],
    'rowKey' => 'id',
    'dense' => false,
    'stack' => true,
    'loading' => false,
    'loadingRows' => 5,
    'empty' => null,
    'emptyIcon' => 'inbox',
    'searchable' => false,
    'rowUrl' => null,
    'caption' => null,

    /*
     * Per-column cell renderers: ['status' => fn (array $row) => ...]. A closure rather than a
     * Blade slot because a slot is rendered once while a cell is rendered per row — the original
     * slot-based attempt could not see $row, which the accounts screen (T131) found immediately.
     *
     * The closure returns a string or a renderable; its output is echoed escaped unless it is an
     * Illuminate\Support\HtmlString, which is how a column renders a status badge.
     *
     * @var array<string, callable(array<string, mixed>): mixed>
     */
    'renderers' => [],
])

@php
    $classes = collect([
        'track-table',
        $dense ? 'track-table--dense' : null,
        $stack ? 'track-table--stack' : null,
    ])->filter()->implode(' ');
@endphp

<div x-data="trackDataTable" {{ $attributes }}>
    @if ($searchable)
        <div class="track-table__toolbar">
            <x-track-input
                name="table-search"
                :placeholder="__('common.actions.search')"
                icon="search"
                size="sm"
                x-model="query"
                x-on:input="filter()"
            />
        </div>
    @endif

    <div class="track-table-wrap">
        <table class="{{ $classes }}" x-ref="table" @if ($loading) aria-busy="true" @endif>
            @if ($caption)
                <caption class="track-sr-only">{{ $caption }}</caption>
            @endif

            <thead>
                <tr>
                    @foreach ($columns as $column)
                        @php
                            $key = $column['key'] ?? '';
                            $sortable = $column['sortable'] ?? true;
                        @endphp

                        {{-- aria-sort goes on the <th>, which is the columnheader. Putting it on the
                             button inside is a critical aria-allowed-attr violation: a <button> has
                             no sortable role for the attribute to describe. --}}
                        <th
                            scope="col"
                            @if ($column['numeric'] ?? false) class="track-table__cell--numeric" @endif
                            @if ($sortable)
                                x-bind:aria-sort="sortKey === @js($key) ? (sortAsc ? 'ascending' : 'descending') : 'none'"
                            @endif
                        >
                            @if ($sortable)
                                <button
                                    type="button"
                                    class="track-table__sort"
                                    x-on:click="sort(@js($key))"
                                >
                                    {{ $column['label'] ?? $key }}
                                    <svg class="track-icon" width="14" height="14" aria-hidden="true" focusable="false">
                                        <use :href="`{{ config('icons.sprite') }}#${sortKey === @js($key) ? (sortAsc ? 'arrow-up' : 'arrow-down') : 'arrow-up-down'}`"></use>
                                    </svg>
                                </button>
                            @else
                                {{ $column['label'] ?? $key }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @if ($loading)
                    @for ($i = 0; $i < $loadingRows; $i++)
                        <tr>
                            @foreach ($columns as $column)
                                <td data-label="{{ $column['label'] ?? '' }}">
                                    <x-track-skeleton height="0.875rem" />
                                </td>
                            @endforeach
                        </tr>
                    @endfor
                @elseif (count($rows) === 0)
                    <tr>
                        <td colspan="{{ count($columns) }}">
                            <x-track-empty-state :icon="$emptyIcon" :message="$empty" />
                        </td>
                    </tr>
                @else
                    @foreach ($rows as $row)
                        @php
                            $url = $rowUrl ? (is_callable($rowUrl) ? $rowUrl($row) : $rowUrl) : null;
                        @endphp

                        <tr
                            @if ($url)
                                class="track-table__row--clickable"
                                {{-- A clickable row is also keyboard-reachable. Without tabindex and
                                     the key handler, the whole table is mouse-only. --}}
                                tabindex="0"
                                role="link"
                                x-on:click="window.location = @js($url)"
                                x-on:keydown.enter="window.location = @js($url)"
                            @endif
                        >
                            @foreach ($columns as $column)
                                @php
                                    $key = $column['key'] ?? '';
                                    $numeric = $column['numeric'] ?? false;
                                    $value = data_get($row, $key);
                                @endphp

                                <td
                                    data-label="{{ $column['label'] ?? $key }}"
                                    data-sort-value="{{ is_scalar($value) ? $value : '' }}"
                                    @class(['track-table__cell--numeric' => $numeric])
                                >
                                    @if (isset($renderers[$key]))
                                        {{ $renderers[$key]($row) }}
                                    @else
                                        {{ $value ?? __('common.labels.none') }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    {{-- The filtered-to-nothing case is distinct from the empty-to-begin-with case above, and says
         something different: clear a filter rather than add a record. --}}
    <div x-show="visibleCount === 0 && query !== ''" x-cloak>
        <x-track-empty-state
            icon="search"
            :title="__('common.empty.noResults')"
            :message="__('common.empty.noResultsMessage')"
        />
    </div>
</div>
