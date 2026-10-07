{{--
    T152 — the full-page refusal (FR-025, FR-080).

    A full page rather than a toast or an inline alert, because a 403 is the end of a journey: the
    person clicked something, waited, and arrived nowhere. A dismissible strip over an empty screen
    leaves them with nothing to do next, which is how "the system is broken" tickets are born.

    The copy names no record, no branch and no id. A refusal message is a disclosure channel like
    any other, and "you may not edit section 214 in Zulfi" tells an attacker that section 214 in
    Zulfi exists (FR-024).

    The link back goes to /home, which every role can reach — not to the previous page, which is the
    page that just refused them.
--}}
<x-app-layout :title="__('authorization.refusal.forbidden_title')">
    <div class="track-refusal">
        <x-track-empty-state
            icon="shield-off"
            :title="__('authorization.refusal.forbidden_title')"
            :message="__('authorization.refusal.forbidden_body')"
        >
            <a href="{{ url('/home') }}" class="track-btn track-btn--primary">
                {{ __('authorization.refusal.back') }}
            </a>
        </x-track-empty-state>
    </div>
</x-app-layout>
