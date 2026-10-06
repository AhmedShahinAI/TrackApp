{{--
    T129 — the change-password form (FR-013, FR-014, FR-015).

    Three fields, each validating on blur and again on submit via field-validation.js. The hint on
    the new-password field states the policy before the person types rather than after they fail it,
    which is the difference between a rule and a trap.

    `current_password` is asked for even though the person is already signed in. That is FR-013, and
    the reason is session hijacking: whoever is at the keyboard may not be the account holder.
--}}

<x-app-layout :title="__('auth.password_change.title')">
    <x-track-page-header :title="__('auth.password_change.title')">
        <x-slot:breadcrumbs>
            <x-track-breadcrumbs :items="[
                ['label' => __('nav.home'), 'url' => route('home')],
                ['label' => __('home.profile.title'), 'url' => route('profile')],
                ['label' => __('auth.password_change.title')],
            ]" />
        </x-slot:breadcrumbs>
    </x-track-page-header>

    <div style="max-width: 520px">
        <x-track-card>
            <form method="POST" action="{{ route('profile.password.update') }}" novalidate
                  x-data="{ submitting: false }" x-on:submit="submitting = true">
                @csrf
                @method('PUT')

                <div style="display: flex; flex-direction: column; gap: var(--space-4)">
                    <x-track-input
                        name="current_password"
                        type="password"
                        :label="__('auth.password_change.current')"
                        icon="lock"
                        autocomplete="current-password"
                        :required="true"
                    />

                    <x-track-input
                        name="password"
                        type="password"
                        :label="__('auth.password_change.new')"
                        icon="key-round"
                        :hint="__('auth.password_change.rule')"
                        autocomplete="new-password"
                        :required="true"
                        minlength="8"
                    />

                    <x-track-input
                        name="password_confirmation"
                        type="password"
                        :label="__('auth.password_change.confirm')"
                        icon="key-round"
                        autocomplete="new-password"
                        :required="true"
                    />

                    {{-- FR-015, stated before the act rather than reported after it. --}}
                    <x-track-alert tone="info">
                        {{ __('auth.password_change.changed_message') }}
                    </x-track-alert>

                    <div style="display: flex; gap: var(--space-2)">
                        <x-track-button type="submit" x-bind:class="{ 'track-btn--loading': submitting }"
                                        x-bind:disabled="submitting">
                            {{ __('common.actions.save') }}
                        </x-track-button>

                        <x-track-button variant="secondary" :href="route('profile')">
                            {{ __('common.actions.cancel') }}
                        </x-track-button>
                    </div>
                </div>
            </form>
        </x-track-card>
    </div>
</x-app-layout>
