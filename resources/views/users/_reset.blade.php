{{--
    T133 — the password-reset confirmation (FR-015, FR-082).

    Two consequences stated before the act: every session of that user ends, and no email is sent.
    The second is the one a Super Admin will not expect — there is no SMTP dependency in this
    feature by design, so they have to pass the password on themselves, and discovering that
    afterwards means a locked-out colleague.
--}}

@php
    // Sent back with errors: reopen on the same account (x-track-dialog, "Coming back with errors").
    $returned = old('_dialog') === 'user-reset' && ctype_digit((string) old('_dialog_user'));
@endphp

<div x-data="trackUserTarget"
     data-dialog="user-reset" data-base="{{ url('/users') }}" data-suffix="reset-password"
     data-user-id="{{ $returned ? (int) old('_dialog_user') : '' }}"
     data-user-name="{{ $returned ? (string) old('_dialog_subject', '') : '' }}"
     x-on:track-dialog-open.window="receive($event)">
    <form method="POST" x-bind:action="action()"
          novalidate x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf
        <x-track-dialog-marker for="user-reset" />
        <input type="hidden" name="_dialog_user" :value="userId">
        <input type="hidden" name="_dialog_subject" :value="userName">

        <x-track-dialog id="user-reset" size="form" :title="__('users.reset_password.title')">
            <p style="margin: 0 0 var(--space-3)">
                <strong x-text="userName"></strong>
            </p>

            <x-track-alert tone="warning">
                {{ __('users.reset_password.message') }}
            </x-track-alert>

            <div style="margin-top: var(--space-4)">
                <x-track-input
                    name="password"
                    type="password"
                    :label="__('users.fields.password')"
                    icon="key-round"
                    :hint="__('auth.password_change.rule')"
                    :error="$returned ? $errors->first('password') : null"
                    :required="true"
                    autocomplete="new-password"
                />
            </div>

            <x-slot:footer>
                <x-track-button variant="secondary" type="button"
                                x-on:click="$dispatch('track-dialog-close', { id: 'user-reset' })">
                    {{ __('common.actions.cancel') }}
                </x-track-button>

                <x-track-button variant="danger" type="submit" icon="key-round"
                                x-bind:class="{ 'track-btn--loading': submitting }"
                                x-bind:disabled="submitting">
                    {{ __('users.reset_password.confirm') }}
                </x-track-button>
            </x-slot:footer>
        </x-track-dialog>
    </form>
</div>
