{{--
    T088 — x-track-topbar.

    64px, white, hairline bottom border. Drawer toggle (shown only below 768px), search, a
    notifications icon-button with its dot, help, and the user block which clicks through to the
    profile. The `end` slot holds the AR/EN switch.

    The user block shows name and role. The role is there because this platform's whole behaviour
    depends on it — a coordinator seeing fewer sections than a colleague needs to know why without
    opening a menu.
--}}

@props([
    'userName' => null,
    'userRole' => null,
    'profileUrl' => '#',
    'notificationCount' => 0,
    'searchAction' => null,
])

<header class="track-topbar">
    <div class="track-topbar__drawer-toggle" x-data>
        <x-track-icon-button
            :label="__('common.labels.menu')"
            icon="menu"
            x-on:click="$store.shell.drawerOpen = ! $store.shell.drawerOpen"
            x-bind:aria-expanded="$store.shell.drawerOpen ? 'true' : 'false'"
            aria-controls="track-sidebar"
        />
    </div>

    @if ($searchAction)
        <form class="track-topbar__search" action="{{ $searchAction }}" method="GET" role="search">
            <x-track-input
                name="q"
                icon="search"
                size="sm"
                :placeholder="__('common.actions.search')"
                :label="null"
            />
        </form>
    @endif

    <div class="track-topbar__actions">
        <x-track-icon-button
            :label="$notificationCount > 0
                ? __('common.labels.notifications').' ('.$notificationCount.')'
                : __('common.labels.notifications')"
            icon="bell"
            :dot="$notificationCount > 0"
        />

        <x-track-icon-button :label="__('common.labels.help')" icon="circle-help" />

        @isset($end){{ $end }}@endisset

        @if ($userName)
            <a href="{{ $profileUrl }}" class="track-topbar__user">
                <span>
                    <span class="track-topbar__user-name">{{ $userName }}</span>
                    @if ($userRole)
                        <span class="track-topbar__user-role">{{ $userRole }}</span>
                    @endif
                </span>
            </a>
        @endif
    </div>
</header>
