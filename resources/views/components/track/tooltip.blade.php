{{--
    T082 — x-track-tooltip.

    Shows on hover AND on keyboard focus. Hover-only is the usual bug and it hides the tooltip from
    exactly the users most likely to need it — the ones navigating icon buttons by Tab.

    The text is also the trigger's aria-describedby, so it is announced rather than merely painted.
--}}

@props(['text'])

<span
    class="track-tooltip-host"
    x-data="{ shown: false }"
    x-on:mouseenter="shown = true"
    x-on:mouseleave="shown = false"
    x-on:focusin="shown = true"
    x-on:focusout="shown = false"
    {{ $attributes }}
>
    {{ $slot }}

    <span class="track-tooltip" role="tooltip" x-show="shown" x-cloak>{{ $text }}</span>
</span>
