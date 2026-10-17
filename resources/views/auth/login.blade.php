{{--
    T127 — the sign-in screen, built to AuthScreens.jsx.

    Split layout: a 500px white column holding the form, and a flexible teal column carrying the
    tagline and the initiatives banner. Below 768px the teal column is dropped rather than stacked —
    on a phone it would push the form below the fold, and the form is the only thing on this page
    anyone came for.

    This is the one screen in the platform that renders outside the application shell: there is no
    sidebar or topbar until someone is signed in.

    The three alert states come from the server, not from client state: a generic failure (FR-009),
    the lockout countdown (FR-010), and the deactivated refusal (FR-011). All three arrive as a
    validation error on `email`, which is why the message is read from the bag rather than from
    separate flags — the controller deliberately gives the client no way to tell them apart beyond
    the text it chose to show.
--}}

@php
    $locale = app()->getLocale();
    $rtl = $locale === 'ar';

    // FR-010. The controller puts retry_after on the session when it refuses; the countdown ticks
    // down from it client-side so the person can see when they may try again.
    $retryAfter = session('retry_after');
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.title') }} · {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="{{ asset('fonts/cairo-'.($rtl ? 'arabic' : 'latin').'-400-normal.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="{{ asset('fonts/cairo-'.($rtl ? 'arabic' : 'latin').'-700-normal.woff2') }}">

    @vite([$rtl ? 'resources/css/app-rtl.scss' : 'resources/css/app-ltr.scss', 'resources/js/app.js'])
</head>
<body>
    <main class="track-login">
        <section class="track-login__form">
            <div class="track-login__head">
                <x-track-logo variant="lockup" :height="88" />

                {{-- A real form, not a JS-only switch: the sign-in screen must work for someone
                     with JavaScript disabled, and it is the one screen where that is plausible. --}}
                <x-track-locale-switch class="track-login__lang" />
            </div>

            <h1 class="track-login__title">{{ __('auth.title') }}</h1>
            <p class="track-login__subtitle">{{ __('auth.subtitle') }}</p>

            <form method="POST" action="{{ route('login') }}" novalidate class="track-login__fields"
                  x-data="trackLoginForm" data-retry-after="{{ (int) $retryAfter }}"
                  x-on:submit="submitting = true">
                @csrf

                @if ($errors->has('email'))
                    {{-- One alert for all three refusals. The tone follows the kind: a lockout is a
                         warning because waiting fixes it, the other two are danger. --}}
                    <x-track-alert :tone="$retryAfter ? 'warning' : 'danger'"
                                   :icon="$retryAfter ? 'timer' : null">
                        @if ($retryAfter)
                            <span x-text="remaining > 0
                                ? @js(__('auth.locked', ['seconds' => ':s'])).replace(':s', remaining)
                                : @js(__('auth.failed'))"></span>
                        @else
                            {{ $errors->first('email') }}
                        @endif
                    </x-track-alert>
                @endif

                <x-track-input
                    name="email"
                    type="email"
                    :label="__('auth.email')"
                    icon="mail"
                    autocomplete="username"
                    :required="true"
                    dir="ltr"
                />

                <x-track-input
                    name="password"
                    type="password"
                    :label="__('auth.password')"
                    icon="lock"
                    autocomplete="current-password"
                    :required="true"
                />

                <x-track-button
                    type="submit"
                    size="lg"
                    :block="true"
                    x-bind:disabled="submitting || remaining > 0"
                    x-bind:class="{ 'track-btn--loading': submitting }"
                >
                    <span x-text="remaining > 0 ? remaining + 's' : @js(__('auth.sign_in'))"></span>
                </x-track-button>
            </form>

            {{-- FR-001, said out loud. A person who cannot find a "create account" link should be
                 told why rather than left looking for it. --}}
            <p class="track-login__note">{{ __('auth.no_registration') }}</p>

            {{-- T463, FR-114. Students have no account here; the support channel is their way in. Behind
                 `support_channel_enabled`, off by default, so the link appears when the channel is opened
                 to students and not with the deploy that ships it. --}}
            @if (App\Modules\Settings\Settings::enabled(App\Modules\Settings\Settings::SUPPORT_CHANNEL_ENABLED))
                <p class="track-login__note">
                    <a href="{{ route('support.create') }}">{{ __('auth.support_link') }}</a>
                </p>
            @endif
        </section>

        <aside class="track-login__brand" aria-hidden="true">
            <div>
                <p class="track-login__tagline">{{ __('auth.tagline_ar') }}</p>
                <p class="track-login__tagline-latin" dir="ltr">{{ __('auth.tagline_en') }}</p>
                <span class="track-login__rule"></span>
            </div>

            {{-- alt="" and the aria-hidden above: decorative, and its meaning is already carried by
                 the tagline beside it. --}}
            <img src="{{ asset('img/brand/banner-initiatives.png') }}" alt=""
                 class="track-login__banner" width="760" height="428">
        </aside>
    </main>
</body>
</html>
