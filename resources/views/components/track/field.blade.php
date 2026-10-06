{{--
    T093 — the Field wrapper: label + control + error-or-hint.

    One primitive rather than each control repeating the arrangement, because the rule that matters
    is subtle: when there is an error, the error REPLACES the hint rather than appearing beside it.
    Two messages under one field is how a user ends up reading the one that does not apply.
--}}

@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->class('track-field') }}>
    @if ($label)
        <label class="track-field__label" @if ($for) for="{{ $for }}" @endif>
            {{ $label }}
            @if ($required)
                <span class="track-field__required" aria-hidden="true">*</span>
                <span class="track-sr-only">{{ __('common.labels.required') }}</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($error)
        {{-- role="alert" so the message is announced the moment it appears, which is what FR-078
             asks for: validation on blur is useless to a screen-reader user who never hears it. --}}
        <p class="track-field__error" role="alert">{{ $error }}</p>
    @elseif ($hint)
        <p class="track-field__hint">{{ $hint }}</p>
    @endif
</div>
