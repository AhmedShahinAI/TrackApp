{{--
    T072 — x-track-input.

    Validates on blur and again on submit (field-validation.js). Blur-only would let someone submit
    a form they never left a field in; submit-only would make them fix six things at once.

    `error` does three things together: turns the border red, replaces the hint with the message,
    and sets role="alert" on it. The border alone would be information carried by colour only.
--}}

@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'icon' => null,
    'hint' => null,
    'error' => null,
    'size' => 'md',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'placeholder' => null,
    'autocomplete' => null,
    'id' => null,
])

@php
    $id = $id ?? 'f-'.$name;
    // $errors is injected by the web group's ShareErrorsFromSession, so it is always present in a
    // request — but not when a component is rendered outside one: a mail template, a queued job, or
    // the component gallery being rendered directly. isset() rather than assuming it.
    $error = $error ?? (isset($errors) && $errors->has($name) ? $errors->first($name) : null);

    $classes = collect([
        'track-input',
        $size !== 'md' ? 'track-input--'.$size : null,
        $icon ? 'track-input--with-icon' : null,
        $error ? 'track-input--error' : null,
    ])->filter()->implode(' ');

    $describedBy = $error ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp

<x-track-field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required">
    <div class="track-input-wrap" @if ($error || $hint) x-data @endif>
        @if ($icon)
            <span class="track-input-wrap__icon"><x-track-icon :name="$icon" :size="18" /></span>
        @endif

        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            class="{{ $classes }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            @required($required)
            @disabled($disabled)
            @readonly($readonly)
            x-on:blur="$dispatch('field-blur', { name: '{{ $name }}' })"
            {{ $attributes }}
        >
    </div>
</x-track-field>
