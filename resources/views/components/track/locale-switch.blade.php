{{--
    T274 — the language switch, to the reference's LangSwitch (Shell.jsx): a two-option segmented
    control, «عربي | EN», the current language raised.

    ## A form, and two real submit buttons

    The locale is stored on the user (or, before sign-in, on the session), so choosing one is a write.
    Each option is a submit button carrying its own value: it works without JavaScript — the sign-in
    screen has to — and pressing the current language is a harmless no-op rather than a toggle that
    flips away from what the user just chose.

    `aria-pressed` on each option says which is current; colour and weight say it again visually, so
    the state is never colour alone (FR-077).

    Both labels are each language's own name for itself, which is why they are not translated: an
    English reader looking for Arabic looks for «عربي», and the other way round.
--}}

@props([
    'label' => null,
])

@php
    $current = app()->getLocale();
    $options = ['ar' => 'عربي', 'en' => 'EN'];
@endphp

<form method="POST" action="{{ route('locale.update') }}" {{ $attributes->class('track-segmented') }}
    role="group" aria-label="{{ $label ?? __('common.labels.language') }}">
    @csrf

    @foreach ($options as $value => $name)
        <button
            type="submit"
            name="locale"
            value="{{ $value }}"
            lang="{{ $value }}"
            class="track-segmented__option"
            aria-pressed="{{ $current === $value ? 'true' : 'false' }}"
        >{{ $name }}</button>
    @endforeach
</form>
