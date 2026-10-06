{{--
    T087 — x-track-sidebar.

    Fixed 240px, on the inline-start edge — right in Arabic, left in English — which _shell.scss
    gets from logical properties rather than a direction check.

    Below 768px it becomes an off-canvas drawer. The scrim is a button, not a div: tapping outside
    to close has to work for a keyboard and switch-control user too.

    `items` is a list of ['key', 'route', 'icon', 'active'] or group headers
    ['group' => 'academic'].
--}}

@props([
    'items' => [],
    'logoVariant' => 'reversed',
])

<aside
    class="track-sidebar"
    :class="{ 'track-sidebar--open': $store.shell.drawerOpen }"
    x-data
    id="track-sidebar"
>
    <div class="track-sidebar__brand">
        <x-track-logo :variant="$logoVariant" :height="28" />
    </div>

    <nav class="track-sidebar__nav" aria-label="{{ __('common.labels.menu') }}">
        @foreach ($items as $item)
            @if (isset($item['group']))
                <div class="track-sidebar__group-label">{{ __('nav.groups.'.$item['group']) }}</div>
            @else
                @php
                    $key = $item['key'];
                    $active = $item['active'] ?? false;
                @endphp

                <a
                    href="{{ $item['url'] ?? '#' }}"
                    @class(['track-sidebar__item', 'track-sidebar__item--active' => $active])
                    {{-- aria-current, not just the class: the active item has to be announced, and
                         the green indicator bar is invisible to a screen reader. --}}
                    @if ($active) aria-current="page" @endif
                >
                    <span class="track-sidebar__item-icon">
                        <x-track-icon :name="$item['icon'] ?? $key" :size="19" />
                    </span>
                    <span>{{ __('nav.'.$key) }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    @isset($footer)
        <div class="track-sidebar__footer">{{ $footer }}</div>
    @endisset
</aside>

{{-- Rendered only while the drawer is open, so it never sits over the page on a desktop. --}}
<button
    type="button"
    class="track-sidebar__scrim"
    x-data
    x-show="$store.shell.drawerOpen"
    x-cloak
    x-on:click="$store.shell.drawerOpen = false"
    aria-label="{{ __('common.actions.close') }}"
></button>
