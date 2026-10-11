{{--
    T371 — the refusal when a support limit is reached (FR-130), for a browser rather than a JSON
    client. Rendered by SupportLimitReached with status 429.

    One page for every limit: the message says a limit was reached and when to try again, never
    which one. The tone is warning, not danger — waiting fixes it.
--}}

@extends('layouts.public', ['title' => __('support.limit_title')])

@section('content')
    <h1 class="track-public__title">{{ __('support.limit_title') }}</h1>

    <div class="track-public__card">
        <x-track-alert tone="warning" icon="timer">{{ $message }}</x-track-alert>
    </div>
@endsection
