{{--
    T090 — x-track-breadcrumbs.

    The chevron is emitted as `chevron-left` or `chevron-right` from the active direction (FR-083)
    rather than being rotated with a transform. A rotated chevron's optical weight shifts — the
    stroke that was vertical becomes horizontal and reads thinner — and at 14px that is visible.

    `items` is a list of ['label' => ..., 'url' => ...]; the last entry renders as the current page
    with aria-current and no link.
--}}

@props(['items' => []])

@php
    $chevron = app()->isLocale('ar') ? 'chevron-left' : 'chevron-right';
    $last = count($items) - 1;
@endphp

<nav {{ $attributes->merge(['aria-label' => __('common.labels.page')]) }}>
    <ol class="track-breadcrumbs">
        @foreach ($items as $i => $item)
            <li>
                @if ($i === $last)
                    <span class="track-breadcrumbs__current" aria-current="page">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    <span class="track-breadcrumbs__sep" aria-hidden="true">
                        <x-track-icon :name="$chevron" :size="14" />
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
