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

    /*
     * Sent back with errors (x-track-dialog, "Coming back with errors"): reopen as the same create or
     * edit, with what was typed. `user` is rebuilt from the account id on the server, never from
     * posted JSON, and `draft` carries the typed values over it.
     */
    $returned = old('_dialog') === 'user-dialog';
    $returnedUser = $returned && ctype_digit((string) old('_dialog_user'))
        ? $users->getCollection()->firstWhere('id', (int) old('_dialog_user'))
        : null;
    $fieldError = fn (string $field): ?string => $returned ? $errors->first($field) : null;
@endphp

<div x-data="trackUserDialog"
     data-base="{{ url('/users') }}" data-store="{{ route('users.store') }}"
     data-user="{{ $returnedUser === null ? '' : json_encode(['id' => $returnedUser->id, 'name_ar' => $returnedUser->name_ar, 'name_en' => $returnedUser->name_en, 'email' => $returnedUser->email]) }}"
     data-draft="{{ $returned ? json_encode(['name_ar' => old('name_ar'), 'name_en' => old('name_en'), 'email' => old('email')]) : '' }}"
     data-role="{{ $returned ? (string) old('role', '') : '' }}">
    {{-- The form wraps the dialog, not the other way round: Blade hoists x-slot content out of
         the component body, so a form opened inside the default slot would not contain the footer
         and its submit button would be orphaned. --}}
    <form method="POST" x-bind:action="action()"
          novalidate x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf
        <x-track-dialog-marker for="user-dialog" />
        <input type="hidden" name="_dialog_user" :value="userId()">

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
                    :error="$fieldError('name_ar')"
                    x-bind:value="field('name_ar')"
                />

                <x-track-input
                    name="name_en"
                    :label="__('users.fields.name_en')"
                    :required="true"
                    dir="ltr"
                    :error="$fieldError('name_en')"
                    x-bind:value="field('name_en')"
                />

                <div style="grid-column: 1 / -1">
                    <x-track-input
                        name="email"
                        type="email"
                        :label="__('users.fields.email')"
                        icon="mail"
                        :required="true"
                        dir="ltr"
                        :error="$fieldError('email')"
                        x-bind:value="field('email')"
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
                            :error="$fieldError('password')"
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
                            :error="$fieldError('role')"
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
                                       :hint="__('users.errors.branches_required')"
                                       :error="$fieldError('branch_ids') ?: $fieldError('branch_ids.*')">
                            <select id="user-branches" name="branch_ids[]" multiple size="5"
                                    class="track-input" style="height: auto">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        @selected($returned && in_array((string) $branch->id, array_map('strval', (array) old('branch_ids', [])), true))>
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
