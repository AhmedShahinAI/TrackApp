{{--
    T077 — x-track-alert.

    Always icon + text. The icon comes from config('icons.tones'), so the tone is carried by shape
    as well as by colour (WCAG 1.4.1) and a warning is distinguishable from an error in greyscale.

    Used by the first-sign-in recommendation, the lockout countdown, scope notices, the
    previously-imported-file warning, and the queue-waiting state — which is why `action` is a slot
    rather than a button prop: each of those needs a different control.
--}}

@props([
    'tone' => 'info',
    'title' => null,
    'icon' => null,
    'dismissible' => false,
])

@php
    $resolved = $icon ?? config('icons.tones.'.$tone, 'info');
    $live = in_array($tone, ['danger', 'warning'], true) ? 'assertive' : 'polite';
@endphp

<div
    {{ $attributes->class('track-alert track-alert--'.$tone) }}
    role="{{ $tone === 'danger' ? 'alert' : 'status' }}"
    aria-live="{{ $live }}"
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" @endif
>
    <span class="track-alert__icon"><x-track-icon :name="$resolved" :size="18" /></span>

    <div class="track-alert__body">
        @if ($title)<p class="track-alert__title">{{ $title }}</p>@endif
        <div>{{ $slot }}</div>
        @isset($action)<div class="track-alert__action">{{ $action }}</div>@endisset
    </div>

    @if ($dismissible)
        <x-track-icon-button
            :label="__('common.actions.dismiss')"
            icon="x"
            size="sm"
            x-on:click="shown = false"
        />
    @endif
</div>
