{{--
    T395 — the public support form (Amendment 2, US9). No sign-in, no account (FR-124).

    The proof-of-work challenge runs in the browser (resources/js/components/support-form.js, which
    loads resources/js/pow on this page only): it fetches GET /support/challenge, solves it off the main
    thread, and fills the two hidden fields. Until then the submit button is disabled, and if the
    challenge cannot be fetched or solved it stays disabled with a message — the form fails closed
    (FR-129). Without JavaScript there is no solution to send, so the <noscript> note says why.

    The honeypot is hidden from sight AND from assistive technology (aria-hidden, out of the tab
    order): a screen-reader user must never be invited to fill the field that rejects them
    (research.md R-08). `.track-sr-only` would be the wrong tool — it is made to be read aloud.

    No file control (FR-125). The category labels come from the `support_categories` setting.
--}}

@extends('layouts.public', ['title' => __('support.form.title')])

@section('content')
    <h1 class="track-public__title">{{ __('support.form.title') }}</h1>
    <p class="track-public__lead">{{ __('support.form.lead') }}</p>

    <form method="POST" action="{{ route('support.store') }}" novalidate class="track-public__card"
          x-data="trackSupportForm"
          data-challenge-url="{{ route('support.challenge') }}"
          data-description-max="{{ $descriptionMax }}"
          data-count-template="{{ __('support.form.description_count') }}"
          data-working="{{ __('support.form.challenge_working') }}"
          data-ready="{{ __('support.form.challenge_ready') }}"
          data-failed="{{ __('support.form.challenge_failed') }}"
          x-on:submit="submit">
        @csrf
        <input type="hidden" name="_issued_at" value="{{ $issuedAt }}">
        <input type="hidden" name="challenge" x-bind:value="challenge">
        <input type="hidden" name="challenge_solution" x-bind:value="solution">

        @if ($errors->has('challenge'))
            <x-track-alert tone="danger">{{ $errors->first('challenge') }}</x-track-alert>
        @endif

        <noscript>
            <x-track-alert tone="warning">{{ __('support.form.needs_javascript') }}</x-track-alert>
        </noscript>

        <x-track-input
            name="university_id"
            :label="__('support.form.university_id')"
            :hint="__('support.form.university_id_hint')"
            :value="old('university_id')"
            autocomplete="off"
            inputmode="numeric"
            required
        />

        <x-track-select
            name="category"
            :label="__('support.form.category')"
            :options="$categories"
            :placeholder="__('support.form.category_placeholder')"
            required
        />

        <x-track-field
            :label="__('support.form.description')"
            for="support-description"
            :error="$errors->first('description')"
            required
        >
            <textarea
                class="track-textarea"
                id="support-description"
                name="description"
                rows="6"
                maxlength="{{ $descriptionMax }}"
                required
                aria-describedby="support-description-count"
                x-on:input="count"
                @error('description') aria-invalid="true" @enderror
            >{{ old('description') }}</textarea>
            <p class="track-field__hint" id="support-description-count" x-text="countLabel">
                {{ __('support.form.description_count', ['count' => mb_strlen((string) old('description', '')), 'max' => $descriptionMax]) }}
            </p>
        </x-track-field>

        <x-track-input
            name="contact"
            :label="__('support.form.contact')"
            :hint="__('support.form.contact_hint')"
            :value="old('contact')"
            autocomplete="off"
        />

        <div class="track-public__trap" aria-hidden="true">
            <label for="support-honeypot">{{ __('support.form.honeypot') }}</label>
            <input type="text" id="support-honeypot" name="_honeypot" value="" tabindex="-1" autocomplete="off">
        </div>

        <p class="track-public__challenge" role="status" aria-live="polite" x-text="statusLabel"></p>

        <div>
            <x-track-button type="submit" variant="primary" icon="send" x-bind:disabled="notReady">
                {{ __('support.form.submit') }}
            </x-track-button>
        </div>
    </form>
@endsection
