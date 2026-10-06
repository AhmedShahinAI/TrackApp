{{--
    T085 — x-track-stat-card.

    A 26px bold Inter numeral over an Arabic label. Inter for the figure even on an Arabic page,
    because --font-numeric is Inter and tabular-nums keeps a row of KPI cards from jittering as the
    numbers change.

    The green tone uses --stat-green, which is the §6-corrected #059669 rather than the brand
    #10B981: at 26px on white the brand green measures 2.54:1 against a 3.0:1 requirement.
--}}

@props([
    'value',
    'label',
    'tone' => 'blue',
    'icon' => null,
    'trend' => null,
    'trendDirection' => null,
])

<div {{ $attributes->class('track-stat track-stat--'.$tone) }}>
    @if ($icon)
        <span class="track-stat__icon"><x-track-icon :name="$icon" :size="22" /></span>
    @endif

    <span class="track-stat__value num">{{ $value }}</span>
    <span class="track-stat__label">{{ $label }}</span>

    @if ($trend)
        {{-- The arrow carries the direction for anyone who cannot distinguish the two colours. --}}
        <span class="track-stat__trend track-stat__trend--{{ $trendDirection ?? 'up' }}">
            <x-track-icon :name="($trendDirection ?? 'up') === 'up' ? 'arrow-up' : 'arrow-down'" :size="14" />
            <span class="num">{{ $trend }}</span>
        </span>
    @endif
</div>
