{{--
    T081 — x-track-skeleton.

    The pulse is defined in tokens/base.css inside a prefers-reduced-motion guard, so a user who
    asked for less motion gets a static block (FR-079). This one matters more than most: it is the
    only animation in the system that runs continuously.

    aria-hidden, because a placeholder has nothing to announce. The container that swaps it for real
    content is what carries aria-busy.
--}}

@props([
    'width' => '100%',
    'height' => '1rem',
    'radius' => null,
])

<span
    {{ $attributes->class('track-skeleton') }}
    style="width: {{ $width }}; height: {{ $height }}@if ($radius); border-radius: {{ $radius }}@endif"
    aria-hidden="true"
></span>
