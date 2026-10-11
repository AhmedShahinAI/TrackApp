{{--
    T073 — x-track-select.

    A native <select>, styled to match Input. Not a custom listbox: the native control gets mobile
    pickers, keyboard type-ahead, and screen-reader support for free, and a 9-programme or 3,000-
    section list is exactly where a hand-built listbox starts failing.

    Options accept either plain strings or {value, label} pairs, so a caller can pass a settings
    list straight through.
--}}

@props([
    'name',
    'options' => [],
    'label' => null,
    'selected' => null,
    'icon' => null,
    'hint' => null,
    'error' => null,
    'size' => 'md',
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'id' => null,
])

@php
    $id = $id ?? 'f-'.$name;
    // $errors is injected by the web group's ShareErrorsFromSession, so it is always present in a
    // request — but not when a component is rendered outside one: a mail template, a queued job, or
    // the component gallery being rendered directly. isset() rather than assuming it.
    $error = $error ?? (isset($errors) && $errors->has($name) ? $errors->first($name) : null);
    $current = old($name, $selected);

    $classes = collect([
        'track-input', 'track-select',
        $size !== 'md' ? 'track-input--'.$size : null,
        $icon ? 'track-input--with-icon' : null,
        $error ? 'track-input--error' : null,
    ])->filter()->implode(' ');
@endphp

<x-track-field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required">
    <div class="track-input-wrap">
        @if ($icon)
            <span class="track-input-wrap__icon"><x-track-icon :name="$icon" :size="18" /></span>
        @endif

        <select
            id="{{ $id }}"
            name="{{ $name }}"
            class="{{ $classes }}"
            @if ($error) aria-invalid="true" @endif
            @required($required)
            @disabled($disabled)
            {{ $attributes }}
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            {{-- A plain list of strings (the mapping step's headers) is its own value; any keyed map
                 submits its KEY. The test was `is_int($key)`, which made every id-keyed map — terms,
                 branches, courses, programmes, instructors — submit its label instead of its id, so
                 those forms failed validation when posted from a browser.

                 The one shape this cannot tell apart: a map whose keys are exactly 0, 1, 2 … in
                 order IS a list. Database ids never start at 0, so id maps are safe; a hand-written
                 map keyed 0..n-1 must pass [['value' => …, 'label' => …]] rows instead, which are
                 always read as written. --}}
            @php $isList = array_is_list($options); @endphp

            @foreach ($options as $key => $option)
                @php
                    $optValue = is_array($option) ? ($option['value'] ?? $key) : ($isList ? $option : $key);
                    $optLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? $key) : $option;
                @endphp
                <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>

        {{-- The native chevron is hidden by appearance:none so the control matches Input; this one
             replaces it and is decorative. --}}
        <span class="track-select-wrap__chevron" aria-hidden="true">
            <x-track-icon name="chevron-down" :size="16" />
        </span>
    </div>
</x-track-field>
