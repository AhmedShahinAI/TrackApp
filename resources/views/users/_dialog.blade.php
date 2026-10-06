{{--
    T132 — the create/edit dialog, built to the UserDialog reference.

    One dialog for both, filled from the event payload. `user-dialog` with no `user` in the detail
    is a create; with one it is an edit, and the form switches method and action accordingly.

    The branch multi-select appears only for BRANCH_COORDINATOR (FR-004). Shown conditionally rather
    than disabled, because a disabled control that becomes required when a sibling changes is a
    control nobody notices.

    560px — the form width from design-system.md §4.
--}}

@php
    $isEn = app()->isLocale('en');
@endphp

<div x-data="{
    user: null,
    role: '',
    init() {
        window.addEventListener('track-dialog-open', (event) => {
            if (event.detail?.id !== 'user-dialog') return;
            this.user = event.detail.user ?? null;
            this.role = '';
        });
    },
}">
    {{-- The form wraps the dialog, not the other way round: Blade hoists x-slot content out of
         the component body, so a form opened inside the default slot would not contain the footer
         and its submit button would be orphaned. --}}
    <form method="POST" x-bind:action="user ? `{{ url('/users') }}/${user.id}` : '{{ route('users.store') }}'"
          novalidate x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf

        <x-track-dialog id="user-dialog" size="form" :title="__('users.create')">
            <template x-if="user">
                {{-- Blade cannot emit @method conditionally for a client-side branch, so the
                     override is a hidden field the template adds only in the edit case. --}}
                <input type="hidden" name="_method" value="PUT">
            </template>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4)">
                <x-track-input
                    name="name_ar"
                    :label="__('users.fields.name_ar')"
                    :required="true"
                    x-bind:value="user?.name_ar ?? ''"
                />

                <x-track-input
                    name="name_en"
                    :label="__('users.fields.name_en')"
                    :required="true"
                    dir="ltr"
                    x-bind:value="user?.name_en ?? ''"
                />

                <div style="grid-column: 1 / -1">
                    <x-track-input
                        name="email"
                        type="email"
                        :label="__('users.fields.email')"
                        icon="mail"
                        :required="true"
                        dir="ltr"
                        x-bind:value="user?.email ?? ''"
                    />
                </div>

                {{-- Create only. An existing account's password is changed by its owner or reset by
                     a Super Admin, each of which invalidates sessions; a third path here would not. --}}
                <template x-if="! user">
                    <div style="grid-column: 1 / -1">
                        <x-track-input
                            name="password"
                            type="password"
                            :label="__('users.fields.password')"
                            icon="key-round"
                            :hint="__('auth.password_change.rule')"
                            :required="true"
                            autocomplete="new-password"
                        />
                    </div>
                </template>

                <template x-if="! user">
                    <div style="grid-column: 1 / -1">
                        <x-track-select
                            name="role"
                            :label="__('users.fields.role')"
                            :placeholder="__('users.filters.all')"
                            :required="true"
                            x-model="role"
                            :options="[
                                ['value' => 'SUPER_ADMIN', 'label' => __('users.roles.SUPER_ADMIN')],
                                ['value' => 'BRANCH_COORDINATOR', 'label' => __('users.roles.BRANCH_COORDINATOR')],
                                ['value' => 'INSTRUCTOR', 'label' => __('users.roles.INSTRUCTOR')],
                            ]"
                        />
                    </div>
                </template>

                {{-- FR-004 — required for a coordinator, absent otherwise. --}}
                <template x-if="role === 'BRANCH_COORDINATOR'">
                    <div style="grid-column: 1 / -1">
                        <x-track-field :label="__('users.fields.branches')" for="user-branches" :required="true"
                                       :hint="__('users.errors.branches_required')">
                            <select id="user-branches" name="branch_ids[]" multiple size="5"
                                    class="track-input" style="height: auto">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">
                                        {{ $isEn ? $branch->campus_name_en : $branch->campus_name_ar }}
                                    </option>
                                @endforeach
                            </select>
                        </x-track-field>
                    </div>
                </template>
            </div>

            <x-slot:footer>
                <x-track-button variant="secondary" type="button"
                                x-on:click="$dispatch('track-dialog-close', { id: 'user-dialog' })">
                    {{ __('common.actions.cancel') }}
                </x-track-button>

                <x-track-button type="submit" x-bind:class="{ 'track-btn--loading': submitting }"
                                x-bind:disabled="submitting">
                    {{ __('common.actions.save') }}
                </x-track-button>
            </x-slot:footer>
        </x-track-dialog>
    </form>
</div>
