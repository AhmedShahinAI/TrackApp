{{--
    The holding page for `/`.

    Laravel's stock landing page lived here. It was deleted rather than allowlisted: it carried
    roughly 60 Tailwind hex values and a page of untranslated English, so it had to be excused from
    both NoHardcodedVisualValuesTest and NoHardcodedStringsTest — and a guard with two standing
    exemptions is a guard nobody trusts.

    Phase 4 (US1) replaces this with the sign-in redirect. Until then it is the smallest honest
    thing: the brand, one translated sentence, and nothing that is not a token.
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
    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @vite([$rtl ? 'resources/css/app-rtl.scss' : 'resources/css/app-ltr.scss'])
</head>
<body>
    <main class="track-content" style="display: grid; place-items: center; min-height: 100vh">
        <x-track-card>
            <div style="display: grid; place-items: center; gap: var(--space-5); text-align: center">
                <x-track-logo variant="primary" :height="40" />
                <p class="track-page-header__subtitle">{{ __('common.empty.message') }}</p>
            </div>
        </x-track-card>
    </main>
</body>
</html>
