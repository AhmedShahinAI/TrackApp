{{--
    T084 — x-track-card.

    `padding=0` for a card that hosts tabs plus a table: the table draws its own cell padding and
    the tab strip sits flush to the card edge, so the card's own 20px would double it.
--}}

@props([
    'title' => null,
    'padding' => null,
])

<section {{ $attributes->class(['track-card', 'track-card--flush' => $padding === 0 || $padding === '0']) }}>
    @if ($title || isset($action))
        <header class="track-card__head">
            @if ($title)<h2 class="track-card__title">{{ $title }}</h2>@endif
            @isset($action)<div>{{ $action }}</div>@endisset
        </header>
    @endif

    <div class="track-card__body">{{ $slot }}</div>
</section>
