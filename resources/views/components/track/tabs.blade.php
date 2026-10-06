{{--
    T089 — x-track-tabs.

    Full keyboard support: arrow keys move between tabs, Home and End jump to the ends, and only the
    active tab is in the tab order (roving tabindex). That is what the ARIA tabs pattern requires,
    and it is also what makes a six-tab strip usable without a mouse.

    Variants underline (green indicator) and segmented (gray track). The active tab carries
    aria-selected and a weight change as well as the colour.
--}}

@props([
    'tabs' => [],
    'active' => null,
    'variant' => 'underline',
    'id' => 'tabs',
    // Whether the caller renders a panel per tab. aria-controls is only emitted when it does: an
    // aria-controls pointing at an id that is not in the document is a critical
    // aria-valid-attr-value violation, and a tab strip used purely for filtering has no panels.
    'panels' => false,
])

@php
    $activeKey = $active ?? (array_key_first($tabs) !== null ? (is_int(array_key_first($tabs)) ? ($tabs[0]['key'] ?? null) : array_key_first($tabs)) : null);
@endphp

<div x-data="trackTabs(@js($activeKey))" {{ $attributes }}>
    <div class="track-tabs track-tabs--{{ $variant }}" role="tablist">
        @foreach ($tabs as $key => $tab)
            @php
                $tabKey = is_array($tab) ? ($tab['key'] ?? $key) : $key;
                $tabLabel = is_array($tab) ? ($tab['label'] ?? $tabKey) : $tab;
                $count = is_array($tab) ? ($tab['count'] ?? null) : null;
            @endphp

            <button
                type="button"
                class="track-tab"
                :class="{ 'track-tab--active': current === @js($tabKey) }"
                role="tab"
                id="{{ $id }}-tab-{{ $tabKey }}"
                @if ($panels) aria-controls="{{ $id }}-panel-{{ $tabKey }}" @endif
                :aria-selected="current === @js($tabKey) ? 'true' : 'false'"
                :tabindex="current === @js($tabKey) ? 0 : -1"
                x-on:click="select(@js($tabKey))"
                x-on:keydown="move($event)"
            >
                {{ $tabLabel }}
                @if ($count !== null)
                    <span class="track-tab__count num">{{ $count }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{ $slot }}
</div>
