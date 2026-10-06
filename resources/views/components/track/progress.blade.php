{{--
    T080 — x-track-progress.

    Colour shifts by value unless `tone` overrides it: green at or above 70, blue in the middle, red
    below 30. The thresholds live here rather than in CSS because the number also has to reach
    aria-valuenow — a progress bar whose only statement of progress is its width is invisible to a
    screen reader.
--}}

@props([
    'value' => 0,
    'tone' => null,
    'showLabel' => true,
    'label' => null,
])

@php
    $pct = max(0, min(100, (int) $value));
    $resolved = $tone ?? match (true) {
        $pct >= 70 => 'green',
        $pct >= 30 => 'blue',
        default => 'danger',
    };
@endphp

<div {{ $attributes->class('track-progress track-progress--'.$resolved) }}>
    @if ($showLabel)
        <div class="track-progress__head">
            <span>{{ $label ?? '' }}</span>
            <span class="num">{{ $pct }}%</span>
        </div>
    @endif

    <div
        class="track-progress__rail"
        role="progressbar"
        aria-valuenow="{{ $pct }}"
        aria-valuemin="0"
        aria-valuemax="100"
        {{-- Always named. A role="progressbar" with no accessible name is a serious violation, and
             the case that produced it was the honest one: showLabel=false with no label passed. The
             fallback is generic but it is a name. --}}
        aria-label="{{ $label ?? __('common.labels.loading') }}"
    >
        <div class="track-progress__bar" style="width: {{ $pct }}%"></div>
    </div>
</div>
