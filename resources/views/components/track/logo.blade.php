{{--
    T068 — x-track-logo.

    The six approved variants, from design-system.md §11. Assets are raster and roughly 3x upscaled
    from the brand board (ui-conflicts C-06, still flagged): adequate at sidebar, topbar and login
    sizes, which is all Phase 1 renders. C-06 must be resolved before the PDF export work, where a
    3x upscale reaches accreditation reviewers.

    Copied into public/img/brand at build time by scripts/copy-brand-assets.mjs rather than served
    from design-system/, which is vendored read-only.
--}}

@props([
    'variant' => 'primary',
    'height' => 32,
    'alt' => null,
])

@php
    $files = [
        'primary' => 'logo-primary.png',
        'reversed' => 'logo-reversed.png',
        'mono' => 'logo-mono.png',
        'lockup' => 'logo-lockup-full.png',
        'mark' => 'logo-mark.png',
        'mark-reversed' => 'logo-mark-reversed.png',
    ];

    $file = $files[$variant] ?? $files['primary'];
@endphp

<img
    src="{{ asset('img/brand/'.$file) }}"
    height="{{ $height }}"
    style="height: {{ $height }}px; width: auto"
    {{-- The wordmark is the platform name, so an empty alt would lose it for a screen reader on the
         login screen where the logo is the only thing naming the product. --}}
    alt="{{ $alt ?? config('app.name') }}"
    {{ $attributes->class('track-logo') }}
>
