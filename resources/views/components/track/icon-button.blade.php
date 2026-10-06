{{--
    T071 — x-track-icon-button.

    `label` is a required prop, not an optional one. A square button whose only content is an icon
    is unreachable by a screen reader without it, and the common failure is that it works for
    everyone who built it. Blade throws on a missing required prop, so the omission cannot ship.

    The label is both aria-label and title: the first for assistive technology, the second so a
    sighted mouse user also gets the name on hover.
--}}

@props([
    'label',
    'icon',
    'variant' => 'ghost',
    'size' => 'md',
    'dot' => false,
    'disabled' => false,
    'type' => 'button',
    'href' => null,
])

@php
    $classes = collect([
        'track-icon-btn',
        'track-icon-btn--'.$variant,
        $size !== 'md' ? 'track-icon-btn--'.$size : null,
    ])->filter()->implode(' ');

    $iconSize = match ($size) { 'sm' => 16, 'lg' => 22, default => 20 };
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href)
        href="{{ $href }}"
    @else
        type="{{ $type }}" @disabled($disabled)
    @endif
    aria-label="{{ $label }}"
    title="{{ $label }}"
    {{ $attributes->class($classes) }}
>
    <x-track-icon :name="$icon" :size="$iconSize" />

    {{-- Decorative. Whatever the dot stands for is already in the label, so nothing is lost by
         hiding it from the accessibility tree. --}}
    @if ($dot)
        <span class="track-icon-btn__dot" aria-hidden="true"></span>
    @endif
</{{ $tag }}>
