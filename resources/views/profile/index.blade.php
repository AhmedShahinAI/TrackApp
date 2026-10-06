{{--
    T129 — the profile screen, built to the ProfileScreen reference.

    Two cards side by side: account details, and the change-password form. The details are a <dl>
    rather than a table, because they are name/value pairs and a screen reader announces them as
    such.
--}}

@php
    $user = auth()->user();
    $locale = app()->getLocale();
@endphp

<x-app-layout :title="__('home.profile.title')">
    <x-track-page-header :title="__('home.profile.title')">
        <x-slot:breadcrumbs>
            <x-track-breadcrumbs :items="[
                ['label' => __('nav.home'), 'url' => route('home')],
                ['label' => __('home.profile.title')],
            ]" />
        </x-slot:breadcrumbs>
    </x-track-page-header>

    <div class="track-home-grid">
        <x-track-card :title="$locale === 'en' ? $user->name_en : $user->name_ar">
            <dl class="track-dl">
                <dt>{{ __('users.fields.name_ar') }}</dt>
                <dd>{{ $user->name_ar }}</dd>

                <dt>{{ __('users.fields.name_en') }}</dt>
                {{-- dir="ltr" so a Latin name inside an Arabic page is not reordered by bidi. --}}
                <dd dir="ltr">{{ $user->name_en }}</dd>

                <dt>{{ __('users.fields.email') }}</dt>
                <dd dir="ltr">{{ $user->email }}</dd>

                <dt>{{ __('users.fields.role') }}</dt>
                <dd>
                    @foreach ($user->getRoleNames() as $role)
                        {{ __('users.roles.'.$role) }}
                    @endforeach
                </dd>

                @if ($user->coordinatorBranches->isNotEmpty())
                    <dt>{{ __('users.fields.branches') }}</dt>
                    <dd>
                        @foreach ($user->coordinatorBranches as $branch)
                            {{ $locale === 'en' ? $branch->campus_name_en : $branch->campus_name_ar }}
                            @if (! $loop->last)،@endif
                        @endforeach
                    </dd>
                @endif

                <dt>{{ __('users.fields.last_login') }}</dt>
                <dd>
                    @if ($user->last_login_at)
                        <span class="num">{{ $user->last_login_at->format('d/m/Y H:i') }}</span>
                    @else
                        {{ __('users.never_signed_in') }}
                    @endif
                </dd>
            </dl>
        </x-track-card>

        <x-track-card :title="__('auth.password_change.title')">
            <x-track-button variant="secondary" icon="key-round" :href="route('profile.password.edit')" :block="true">
                {{ __('auth.password_change.title') }}
            </x-track-button>
        </x-track-card>
    </div>
</x-app-layout>
