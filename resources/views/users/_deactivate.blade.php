{{--
    T133 — the deactivate confirmation (FR-082).

    A high-impact action restates its consequence before it happens. Two sentences, and both matter:
    what will break (sessions end, sign-in refused), and what will not (the name stays on everything
    they created). The second is there because it is what people actually fear about "deactivate",
    and leaving it unsaid makes a Super Admin hesitate over a reversible action.
--}}

@php
    // Sent back with errors: reopen on the same account (x-track-dialog, "Coming back with errors").
    $returned = old('_dialog') === 'user-deactivate' && ctype_digit((string) old('_dialog_user'));
@endphp

<div x-data="trackUserTarget"
     data-dialog="user-deactivate" data-base="{{ url('/users') }}" data-suffix="deactivate"
     data-user-id="{{ $returned ? (int) old('_dialog_user') : '' }}"
     data-user-name="{{ $returned ? (string) old('_dialog_subject', '') : '' }}"
     x-on:track-dialog-open.window="receive($event)">
    <form method="POST" x-bind:action="action()"
          x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf
        <x-track-dialog-marker for="user-deactivate" />
        <input type="hidden" name="_dialog_user" :value="userId">
        <input type="hidden" name="_dialog_subject" :value="userName">
        @method('PATCH')

        <x-track-dialog id="user-deactivate" :title="__('users.deactivate.title')">
            <p style="margin: 0 0 var(--space-3)">
                <strong x-text="userName"></strong>
            </p>

            <x-track-alert tone="danger">
                {{ __('users.deactivate.message') }}
            </x-track-alert>

            {{-- FR-018, FR-049 — said plainly, because it is the reassurance that makes the action
                 safe to take. --}}
            <p style="margin: var(--space-3) 0 0; font: var(--text-body-sm); color: var(--text-secondary)">
                {{ __('users.deactivate.preserved') }}
            </p>

            <x-slot:footer>
                <x-track-button variant="secondary" type="button"
                                x-on:click="$dispatch('track-dialog-close', { id: 'user-deactivate' })">
                    {{ __('common.actions.cancel') }}
                </x-track-button>

                <x-track-button variant="danger" type="submit" icon="user-x"
                                x-bind:class="{ 'track-btn--loading': submitting }"
                                x-bind:disabled="submitting">
                    {{ __('users.deactivate.confirm') }}
                </x-track-button>
            </x-slot:footer>
        </x-track-dialog>
    </form>
</div>
