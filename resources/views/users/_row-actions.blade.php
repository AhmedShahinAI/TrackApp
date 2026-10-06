{{--
    T131 — the per-row action cluster.

    Each control is wrapped in @can for the permission its endpoint checks. A row that offers an
    action the next click would refuse is worse than one that omits it.

    The dialogs are opened by event rather than one dialog per row: 25 rows would otherwise mean 75
    dialogs in the DOM. The row's data travels in `data-` attributes and the handler reads
    `$el.dataset`.

    It does NOT travel as @js(...) inside the x-on expression, which was the first attempt: @js
    emits a double-quoted JSON string, and a double quote inside a double-quoted HTML attribute
    closes the attribute. Every row produced "Invalid or unexpected token" in the console and no
    dialog opened — found by driving the screen in a browser rather than by reading it.
--}}

@props(['user'])

<div class="track-row-actions">
    @can('user.edit')
        <x-track-icon-button
            :label="__('users.edit')"
            icon="pencil"
            size="sm"
            data-user-id="{{ $user->id }}"
            data-user-name-ar="{{ $user->name_ar }}"
            data-user-name-en="{{ $user->name_en }}"
            data-user-email="{{ $user->email }}"
            data-user-locale="{{ $user->locale }}"
            x-on:click="$dispatch('track-dialog-open', {
                id: 'user-dialog',
                user: {
                    id: $el.dataset.userId,
                    name_ar: $el.dataset.userNameAr,
                    name_en: $el.dataset.userNameEn,
                    email: $el.dataset.userEmail,
                    locale: $el.dataset.userLocale,
                },
            })"
        />
    @endcan

    @can('user.reset_password')
        <x-track-icon-button
            :label="__('users.reset_password.action')"
            icon="key-round"
            size="sm"
            data-user-id="{{ $user->id }}"
            data-user-name="{{ app()->isLocale('en') ? $user->name_en : $user->name_ar }}"
            x-on:click="$dispatch('track-dialog-open', {
                id: 'user-reset',
                userId: $el.dataset.userId,
                userName: $el.dataset.userName,
            })"
        />
    @endcan

    @can('user.deactivate')
        {{-- Absent for an already-inactive account and for the caller's own row: the endpoint
             refuses both, and offering an action that will be refused is a worse screen. --}}
        @if ($user->is_active && ! $user->is(auth()->user()))
            <x-track-icon-button
                :label="__('users.deactivate.action')"
                icon="user-x"
                size="sm"
                data-user-id="{{ $user->id }}"
                data-user-name="{{ app()->isLocale('en') ? $user->name_en : $user->name_ar }}"
                x-on:click="$dispatch('track-dialog-open', {
                    id: 'user-deactivate',
                    userId: $el.dataset.userId,
                    userName: $el.dataset.userName,
                })"
            />
        @endif
    @endcan
</div>
