{{--
    T075 — x-track-switch.

    Green when on. This is one of the non-text uses design-system.md §6 exempts, so the brand
    #10B981 is retained rather than the darkened --green-600: the fill is a state indicator, not a
    label, and it sits beside text that says what it controls.

    role="switch" rather than a styled checkbox role, so a screen reader announces "on"/"off"
    instead of "checked"/"unchecked" — which is what the control actually means.
--}}

@props([
    'name',
    'label' => null,
    'checked' => false,
    'disabled' => false,
    'id' => null,
])

@php $id = $id ?? 's-'.$name; @endphp

<label class="track-switch" for="{{ $id }}">
    <input type="hidden" name="{{ $name }}" value="0">

    <input
        id="{{ $id }}"
        type="checkbox"
        role="switch"
        name="{{ $name }}"
        value="1"
        class="track-switch__track"
        @checked(old($name, $checked))
        @disabled($disabled)
        {{ $attributes }}
    >

    @if ($label)<span>{{ $label }}</span>@endif
    {{ $slot }}
</label>
