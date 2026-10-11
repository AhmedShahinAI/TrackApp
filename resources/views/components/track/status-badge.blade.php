{{--
    T076 — x-track-status-badge.

    `status` is a required prop and the label is always rendered from lang/*/status.php. The
    component CANNOT be invoked without producing text beside the colour, which is how FR-077 is
    satisfied structurally rather than by someone remembering it in review.

    The statuses map onto six tones. The mapping is here rather than in CSS because two
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
        'returned' => 'warning',
        'rejected' => 'danger',
        'committed' => 'success',
        'skipped' => 'neutral',
        // The registrar's academic_status (FR-110); `withdrawn` is shared with TRACK's status above.
        'regular' => 'active',
        'excused' => 'warning',
    ];

    $tone = $tones[$status] ?? 'neutral';

    /*
     * T261 — an unknown status with no label of its own would print the raw key "status.x" beside a
     * coloured dot: colour with no words, the one thing FR-077 forbids. It reads "Unknown status"
     * instead, in the neutral tone, and the value is logged so the vocabulary can catch up.
     *
     * Not an exception: this rendered once per table row, so one value nobody had labelled yet — a
     * new enum case, a migrated row — turned a whole roster into a 500. A caller with its own
     * vocabulary (the import states) passes a label and never reaches this.
     */
    $unknown = ! array_key_exists($status, $tones) && $slot->isEmpty();

    if ($unknown) {
        Illuminate\Support\Facades\Log::warning('x-track-status-badge: unknown status without a label', ['status' => $status]);
    }
@endphp

<span {{ $attributes->class('track-badge track-badge--'.$tone) }}>
    @if ($icon)
        <x-track-icon :name="$icon" :size="14" />
    @endif
    {{ $slot->isNotEmpty() ? $slot : __($unknown ? 'status.unknown' : 'status.'.$status) }}
</span>
