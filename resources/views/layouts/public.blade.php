{{--
    T374 — the public layout: the student support pages (Amendment 2).

    Standalone. No sidebar, no topbar, and **no link into any authenticated area** (FR-163) — not
    even the logo, which in the shell goes home. A student is not a user of the platform, and a page
    that offered them a way toward the staff side would be an invitation to try it.

    The language switch works without a sign-in (FR-161): it is the same form the sign-in page uses,
    which stores the choice on the guest session. `lang` and `dir` come from the locale that request
    resolved, so the page, its stylesheet and every logical property in it agree.

    Pages pass `title` and fill the `content` section.
--}}

@php
    $locale = app()->getLocale();
    $rtl = $locale === 'ar';
    $subset = $rtl ? 'arabic' : 'latin';
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- A support page has no business in a search index: it is reached from the institution's
         own links, and an indexed form is a target list for the scripts the challenge exists for. --}}
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? __('support.layout.title') }} · {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="{{ asset('fonts/cairo-'.$subset.'-400-normal.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="{{ asset('fonts/cairo-'.$subset.'-700-normal.woff2') }}">

    @vite([$rtl ? 'resources/css/app-rtl.scss' : 'resources/css/app-ltr.scss', 'resources/js/app.js'])

    @stack('head')
</head>
<body>
    <a href="#track-public-content" class="track-skip-link">{{ __('common.labels.skipToContent') }}</a>

    <div class="track-public">
        <header class="track-public__header">
            <x-track-logo variant="primary" :height="40" />

            <x-track-locale-switch class="track-public__lang" />
        </header>

        <main id="track-public-content" class="track-public__main" tabindex="-1">
            @yield('content')
        </main>

        <footer class="track-public__footer">
            {{ __('support.layout.footer') }}
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
