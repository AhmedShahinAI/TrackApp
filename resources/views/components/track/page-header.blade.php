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
            {{-- The default slot: badges about the page's subject. It was never rendered, so the section
                 detail's FR-026 "shared" badge had never appeared, and neither would the approval state
                 (Phase 27, T498). --}}
            @if ($slot->isNotEmpty())<div class="track-page-header__badges">{{ $slot }}</div>@endif
        </div>

        @isset($actions)
            <div class="track-page-header__actions">{{ $actions }}</div>
        @endisset
    </div>
</header>
