{{--
    SEC-074 (security plan G-12) — the server-error page.

    Self-contained on purpose: a 500 is rendered when something is already broken, so this page asks
    nothing of the database, the session or the signed-in user — the things x-app-layout needs. It
    is built like welcome.blade.php: the brand, one translated sentence, design tokens only, and no
    detail about the failure (that is in the log, under the request id, never on the page).
--}}

@php
    $locale = app()->getLocale();
    $rtl = $locale === 'ar';
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('common.errors.server') }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @vite([$rtl ? 'resources/css/app-rtl.scss' : 'resources/css/app-ltr.scss'])
</head>
<body>
    <main class="track-content" style="display: grid; place-items: center; min-height: 100vh">
        <x-track-card>
            <div style="display: grid; place-items: center; gap: var(--space-4); text-align: center">
                <x-track-logo variant="primary" :height="40" />
                <h1 class="track-page-header__title">{{ __('common.errors.server') }}</h1>
                <p class="track-page-header__subtitle">{{ __('common.errors.serverMessage') }}</p>
                <a href="{{ url('/') }}" class="track-btn track-btn--primary">{{ __('common.errors.reload') }}</a>
            </div>
        </x-track-card>
    </main>
</body>
</html>
