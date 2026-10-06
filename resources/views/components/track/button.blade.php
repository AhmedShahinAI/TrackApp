{{--
    T070 — x-track-button.

    Variants primary / accent / secondary / ghost / danger; sizes sm 32 / md 40 / lg 48.

    `icon` renders on the inline-start side and `iconEnd` on the inline-end side, which puts `icon`
    on the right in Arabic without a direction check — the contract's "icon on the right in RTL" is
    what logical ordering gives for free.

    `loading` holds the button's width while it spins. The label stays in the flow at zero opacity
    and the spinner is absolutely centred over it, because a toolbar that reflows the instant
    someone clicks is a toolbar where the next click lands on the wrong control.
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconEnd' => null,
    'loading' => false,
    'disabled' => false,
    'block' => false,
    'type' => 'button',
    'href' => null,
])

@php
    $classes = collect([
        'track-btn',
        'track-btn--'.$variant,
        $size !== 'md' ? 'track-btn--'.$size : null,
        $loading ? 'track-btn--loading' : null,
        $block ? 'track-btn--block' : null,
    ])->filter()->implode(' ');

    $iconSize = $size === 'sm' ? 16 : 18;
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href)
        href="{{ $disabled || $loading ? '#' : $href }}"
        @if ($disabled || $loading) aria-disabled="true" tabindex="-1" @endif
    @else
        type="{{ $type }}"
        @disabled($disabled || $loading)
    @endif
    @if ($loading) aria-busy="true" @endif
    {{ $attributes->class($classes) }}
>
    @if ($loading)
        <span class="track-btn__spinner">
            <x-track-icon name="loader-circle" :size="$iconSize" class="track-spin" />
        </span>
    @endif

    @if ($icon)
        <span class="track-btn__icon"><x-track-icon :name="$icon" :size="$iconSize" /></span>
    @endif

    <span class="track-btn__label">{{ $slot }}</span>

    @if ($iconEnd)
        <span class="track-btn__icon"><x-track-icon :name="$iconEnd" :size="$iconSize" /></span>
    @endif
</{{ $tag }}>
