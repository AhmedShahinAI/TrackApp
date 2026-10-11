{{--
    T274 — preferences: the interface language, stored on the account (FR-074).

    The same control as the topbar's switch, so a person who has used one recognises the other. It
    lives here as well because the topbar switch is easy to miss, and "where do I change the language"
    is the one setting every user of a bilingual system will look for on their profile.

    The choice is written to `users.locale` by LocaleController, which is why it follows the person to
    any device they sign in on, and why the change is immediate: the next page renders in it.
--}}

@php $locale = app()->getLocale(); @endphp

<x-app-layout :title="__('home.preferences.title')">
    <x-track-page-header :title="__('home.preferences.title')" :subtitle="__('home.preferences.subtitle')">
        <x-slot:breadcrumbs>
            <x-track-breadcrumbs :items="[
                ['label' => __('nav.home'), 'url' => route('home')],
                ['label' => __('home.profile.title'), 'url' => route('profile')],
                ['label' => __('home.preferences.title')],
            ]" />
        </x-slot:breadcrumbs>
    </x-track-page-header>

    <x-track-card :title="__('common.labels.language')">
        <div class="track-preference">
            <div class="track-preference__text">
                <p class="track-preference__label">{{ __('home.preferences.language') }}</p>
                <p class="track-preference__hint">{{ __('home.preferences.language_hint') }}</p>
            </div>

            <x-track-locale-switch :label="__('home.preferences.language')" />
        </div>
    </x-track-card>
</x-app-layout>
