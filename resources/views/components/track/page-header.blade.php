{{--
    T092 — the page header: breadcrumbs → h1 → optional subtitle → right-aligned actions.

    One h1 per page, here. A screen-reader user navigating by heading level should land on the page
    name, and if each card supplied its own h1 they would land on a card.
--}}

@props([
    'title',
    'subtitle' => null,
])

<header {{ $attributes->class('track-page-header') }}>
    @isset($breadcrumbs){{ $breadcrumbs }}@endisset

    <div class="track-page-header__row">
        <div>
            <h1 class="track-page-header__title">{{ $title }}</h1>
            @if ($subtitle)<p class="track-page-header__subtitle">{{ $subtitle }}</p>@endif
        </div>

        @isset($actions)
            <div class="track-page-header__actions">{{ $actions }}</div>
        @endisset
    </div>
</header>
