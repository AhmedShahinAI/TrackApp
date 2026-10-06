{{-- T074 — x-track-checkbox. Square, filled --track-blue when on, label beside it. --}}

@props([
    'name',
    'label' => null,
    'checked' => false,
    'value' => '1',
    'disabled' => false,
    'id' => null,
])

@php $id = $id ?? 'c-'.$name; @endphp

<label class="track-check" for="{{ $id }}">
    {{-- The hidden companion means an unchecked box still posts a value. Without it, "deactivate
         this account" unticked simply omits the key and the server cannot tell it from a field that
         was never on the form. --}}
    <input type="hidden" name="{{ $name }}" value="0">

    <input
        id="{{ $id }}"
        type="checkbox"
        name="{{ $name }}"
        value="{{ $value }}"
        class="track-check__box"
        @checked(old($name, $checked))
        @disabled($disabled)
        {{ $attributes }}
    >

    @if ($label)<span>{{ $label }}</span>@endif
    {{ $slot }}
</label>
