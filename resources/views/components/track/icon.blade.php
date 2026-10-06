{{--
    T067 — x-track-icon.

    One <use> reference into the self-hosted sprite. No inline SVG per icon: forty inline copies of
    the same path on a table page is forty copies the browser cannot cache, and the sprite is one
    22 KB request shared by every page.

    `name` accepts either a Lucide name directly, or a concept key from config('icons.concepts').
    Resolving concepts here is what lets the §7 name map live in one file.
--}}

@props([
    'name',
    'size' => null,
    'color' => 'currentColor',
    'label' => null,
])

@php
    $resolved = config('icons.concepts.'.$name, $name);
    $px = $size ?? config('icons.default_size', 20);
@endphp

<svg
    class="track-icon"
    width="{{ $px }}"
    height="{{ $px }}"
    style="color: {{ $color }}"
    {{-- Decorative by default. An icon that carries meaning is given a label, and only then does it
         enter the accessibility tree — which is the right default, because most icons in this
         system sit beside the text that already says what they mean. --}}
    @if ($label)
        role="img" aria-label="{{ $label }}"
    @else
        aria-hidden="true" focusable="false"
    @endif
    {{ $attributes }}
>
    <use href="{{ config('icons.sprite') }}#{{ $resolved }}"></use>
</svg>
