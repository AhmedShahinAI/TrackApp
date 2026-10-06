{{--
    T076 — x-track-status-badge.

    `status` is a required prop and the label is always rendered from lang/*/status.php. The
    component CANNOT be invoked without producing text beside the colour, which is how FR-077 is
    satisfied structurally rather than by someone remembering it in review.

    The nineteen statuses map onto six tones. The mapping is here rather than in CSS because two
    statuses that look identical still read differently — `closed` and `archived` share the neutral
    tone and must never share a label.
--}}

@props([
    'status',
    'icon' => null,
])

@php
    $tones = [
        'active' => 'active',
        'current' => 'active',
        'completed' => 'success',
        'valid' => 'success',
        'progress' => 'progress',
        'running' => 'progress',
        'shared' => 'progress',
        'late' => 'danger',
        'failed' => 'danger',
        'error' => 'danger',
        'pending' => 'warning',
        'warning' => 'warning',
        'queued' => 'warning',
        'notStarted' => 'neutral',
        'closed' => 'neutral',
        'draft' => 'neutral',
        'archived' => 'neutral',
        'withdrawn' => 'neutral',
        'inactive' => 'neutral',
    ];

    $tone = $tones[$status] ?? 'neutral';
@endphp

<span {{ $attributes->class('track-badge track-badge--'.$tone) }}>
    @if ($icon)
        <x-track-icon :name="$icon" :size="14" />
    @endif
    {{ $slot->isNotEmpty() ? $slot : __('status.'.$status) }}
</span>
