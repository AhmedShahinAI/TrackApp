{{-- T093 — the Panel primitive: a bordered white section with an optional titled header band. --}}

@props(['title' => null])

<section {{ $attributes->class('track-panel') }}>
    @if ($title)
        <header class="track-panel__head">{{ $title }}</header>
    @endif

    <div class="track-panel__body">{{ $slot }}</div>
</section>
