{{--
    T396 — the confirmation. Shown once (GET /support/submitted reads a one-render flash), so the
    guidance to keep the reference is not a formality: a refresh will not bring it back.

    The reference is set `dir="ltr"` inside an Arabic page so its hyphenated groups read in order, and
    in the numeric face with a slashed zero, so 0 and the letters around it are told apart. Selecting it
    copies the reference alone; ReferenceGenerator::normalise forgives the direction marks a copy from
    an RTL page carries anyway.

    T432a (carried over from T396): the link to the status page, where the reference and the University
    ID together show what happened.
--}}

@extends('layouts.public', ['title' => __('support.submitted.title')])

@section('content')
    <h1 class="track-public__title">{{ __('support.submitted.title') }}</h1>

    <div class="track-public__card">
        <x-track-alert tone="success">{{ __('support.submitted.keep_it') }}</x-track-alert>

        <div>
            <p class="track-public__reference-label" id="support-reference-label">{{ __('support.submitted.reference_label') }}</p>
            <p class="track-public__reference" dir="ltr" aria-labelledby="support-reference-label">{{ $reference }}</p>
        </div>

        <div class="track-public__actions">
            <x-track-button variant="primary" icon="search" :href="route('support.status.form')">{{ __('support.submitted.check_status') }}</x-track-button>
            <x-track-button variant="secondary" :href="route('support.create')">{{ __('support.submitted.new_request') }}</x-track-button>
        </div>
    </div>
@endsection
