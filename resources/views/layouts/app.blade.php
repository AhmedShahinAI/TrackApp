{{--
    T095 — the application shell.

    Emits `lang` and `dir` from the active locale, loads the matching stylesheet, and composes
    sidebar + topbar + a scrolling content region at 28px 28px 48px.

    Three things here are easy to leave out and expensive to add back:

      • The skip link is the first focusable element on the page. Without it a keyboard user walks
        the whole sidebar on every navigation to reach the content.
      • The two above-the-fold font weights are preloaded. A stylesheet cannot preload itself early
        enough to matter, so the hints have to be in the document head.
      • `dir` drives both the stylesheet choice and every logical property in the CSS. One place
        decides it, from one source.
--}}

@php
    $locale = app()->getLocale();
    $rtl = $locale === 'ar';
    $stylesheet = $rtl ? 'resources/css/app-rtl.scss' : 'resources/css/app-ltr.scss';

    // Cairo 400 and 700 in the subset the active locale actually renders. Preloading the Arabic
    // subset on an English page would fetch 20 KB nobody sees, and vice versa.
    $subset = $rtl ? 'arabic' : 'latin';
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icons/app-icon-32.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('icons/app-icon-180.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="{{ asset('fonts/cairo-'.$subset.'-400-normal.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="{{ asset('fonts/cairo-'.$subset.'-700-normal.woff2') }}">

    @vite([$stylesheet, 'resources/js/app.js'])

    {{-- x-cloak needs a style rule before Alpine boots, or every x-show'd element flashes visible
         for one frame. It is here rather than in the stylesheet because it has to apply before the
         stylesheet has necessarily parsed. --}}
    <style>[x-cloak] { display: none !important; }</style>

    @stack('head')
</head>
<body>
    <a href="#track-content" class="track-skip-link">{{ __('common.labels.skipToContent') }}</a>

    <div class="track-shell">
        <x-track-sidebar :items="$navItems ?? []">
            <x-slot:footer>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <x-track-button variant="ghost" type="submit" icon="log-out" :block="true">
                        {{ __('common.actions.logout') }}
                    </x-track-button>
                </form>
            </x-slot:footer>
        </x-track-sidebar>

        <div class="track-main">
            <x-track-topbar
                :user-name="auth()->user()?->{'name_'.$locale} ?? auth()->user()?->name_ar"
                :user-role="$userRole ?? null"
                :profile-url="url('/profile')"
                :notification-count="$notificationCount ?? 0"
            >
                <x-slot:end>
                    {{-- Posts rather than links: the locale lives on the user record, so switching
                         it is a write (locale-switcher.js). --}}
                    <x-track-button
                        variant="ghost"
                        size="sm"
                        data-track-locale="{{ $rtl ? 'en' : 'ar' }}"
                        data-track-locale-action="{{ url('/locale') }}"
                    >
                        {{ $rtl ? 'EN' : 'ع' }}
                    </x-track-button>
                </x-slot:end>
            </x-track-topbar>

            <main id="track-content" class="track-content">
                {{-- The flash region is aria-live so a message that appears after a redirect is
                     announced, not merely painted. --}}
                <div aria-live="polite">
                    @if (session('status'))
                        <x-track-alert tone="success" class="mb-4">{{ session('status') }}</x-track-alert>
                    @endif

                    @if (session('error'))
                        <x-track-alert tone="danger" class="mb-4">{{ session('error') }}</x-track-alert>
                    @endif
                </div>

                {{-- FR-012: shown once, on an account that has never signed in before. --}}
                @isset($firstSignIn)
                    <div class="mb-4">{{ $firstSignIn }}</div>
                @endisset

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-track-toast :region="true" />

    @stack('scripts')
</body>
</html>
