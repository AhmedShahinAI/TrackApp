{{--
    T091 — x-track-stepper.

    Done steps are green with a check, the active step is blue with a soft ring, upcoming steps are
    gray. aria-current="step" on the active one, and each marker also shows its number or a check —
    so the position in the sequence is never colour-only.
--}}

@props([
    'steps' => [],
    'current' => 1,
    'label' => null,
])

<nav {{ $attributes->merge(['aria-label' => $label]) }}>
    <ol class="track-stepper">
        @foreach ($steps as $i => $step)
            @php
                $n = $i + 1;
                $state = $n < $current ? 'done' : ($n === $current ? 'active' : 'upcoming');
            @endphp

            <li class="track-step track-step--{{ $state }}" @if ($state === 'active') aria-current="step" @endif>
                <span class="track-step__marker">
                    @if ($state === 'done')
                        <x-track-icon name="check" :size="16" />
                        <span class="track-sr-only">{{ $n }}</span>
                    @else
                        {{ $n }}
                    @endif
                </span>
                <span class="track-step__label">{{ is_array($step) ? ($step['label'] ?? '') : $step }}</span>
            </li>

            @if ($n < count($steps))
                <li class="track-step__line @if ($n < $current) track-step__line--done @endif" aria-hidden="true"></li>
            @endif
        @endforeach
    </ol>
</nav>
