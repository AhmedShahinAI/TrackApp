{{--
    T133 — the deactivate confirmation (FR-082).

    A high-impact action restates its consequence before it happens. Two sentences, and both matter:
    what will break (sessions end, sign-in refused), and what will not (the name stays on everything
    they created). The second is there because it is what people actually fear about "deactivate",
    and leaving it unsaid makes a Super Admin hesitate over a reversible action.
--}}

<div x-data="{ userId: null, userName: '' }"
     x-on:track-dialog-open.window="if ($event.detail?.id === 'user-deactivate') {
         userId = $event.detail.userId;
         userName = $event.detail.userName ?? '';
     }">
    <form method="POST" x-bind:action="`{{ url('/users') }}/${userId}/deactivate`"
          x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf
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
