{{--
    SEC-074 (security plan G-12) — the rate-limited page, in the platform's own words and both languages.

    Laravel's default page for this status is English-only and unbranded. Same structure as 404.blade.php,
    and like it, nothing here names a record, a limit or a reason beyond the status itself.
--}}
<x-app-layout :title="__('common.errors.tooMany')">
    <div class="track-refusal">
        <x-track-empty-state
            icon="timer"
            :title="__('common.errors.tooMany')"
            :message="__('common.errors.tooManyMessage')"
        >
            <a href="{{ url('/home') }}" class="track-btn track-btn--primary">
                {{ __('authorization.refusal.back') }}
            </a>
        </x-track-empty-state>
    </div>
</x-app-layout>
