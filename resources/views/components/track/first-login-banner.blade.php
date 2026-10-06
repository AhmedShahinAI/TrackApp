{{--
    T128 — x-track-first-login-banner (FR-012).

    Non-blocking and dismissable. "Non-blocking" is the requirement and it is the part that is easy
    to get wrong: this must not be a modal, must not steal focus, and must not prevent the person
    from doing what they signed in to do. It is a recommendation, which is why the tone is warning
    rather than danger.

    Shown only when the session flash says this was the first sign-in. The flash is set by
    LoginController from state captured BEFORE first_login_at was written, and a flash is read once —
    so it cannot reappear on a later sign-in even if the user reloads.
--}}

@if (session('first_login'))
    <x-track-alert tone="warning" :title="__('auth.first_login.title')" :dismissible="true" class="mb-4">
        {{ __('auth.first_login.message') }}

        <x-slot:action>
            <x-track-button variant="secondary" size="sm" icon="key-round"
                            :href="route('profile.password.edit')">
                {{ __('auth.first_login.action') }}
            </x-track-button>
        </x-slot:action>
    </x-track-alert>
@endif
