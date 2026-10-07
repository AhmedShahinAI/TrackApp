{{--
    T152 — the full-page "not found" (FR-024, FR-025, FR-080).

    Deliberately identical in structure and tone to 403.blade.php. The two must be visibly different
    pages — a person who genuinely mistyped a URL needs different wording from one who lacks a
    permission — while neither reveals which situation produced it for any *particular* record.

    This is also the page a coordinator sees for another branch's section, which is the whole of
    FR-024: out of scope and nonexistent are the same answer, and they are the same page.
--}}
<x-app-layout :title="__('authorization.refusal.missing_title')">
    <div class="track-refusal">
        <x-track-empty-state
            icon="file-question"
            :title="__('authorization.refusal.missing_title')"
            :message="__('authorization.refusal.missing_body')"
        >
            <a href="{{ url('/home') }}" class="track-btn track-btn--primary">
                {{ __('authorization.refusal.back') }}
            </a>
        </x-track-empty-state>
    </div>
</x-app-layout>
