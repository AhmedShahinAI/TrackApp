{{--
    T083 — x-track-empty-state.

    Also the renderer for full-page 403 and 404 (FR-024, FR-025). One component for all three
    because they are the same thing from the user's side: a page with nothing on it and a sentence
    saying what to do next. Separate implementations is how the 403 ends up less helpful than the
    empty table.
--}}

@props([
    'icon' => 'inbox',
    'title' => null,
    'message' => null,
])

<div {{ $attributes->class('track-empty') }}>
    <span class="track-empty__icon"><x-track-icon :name="$icon" :size="40" /></span>

    <h2 class="track-empty__title">{{ $title ?? __('common.empty.title') }}</h2>

    @if ($message !== false)
        <p class="track-empty__message">{{ $message ?? __('common.empty.message') }}</p>
    @endif

    @isset($action)<div>{{ $action }}</div>@endisset
    {{ $slot }}
</div>
