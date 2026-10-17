{{--
    T397 — the one answer to every verification failure (FR-119, SC-026, research.md R-10).

    The identifier is not on the roster, it is with no enrollment this term, or its only enrollment is
    withdrawn: all three render this page, byte for byte. It takes **no** variable from the request —
    not the identifier, not the category, not the description — so nothing in it can differ between
    the three. Re-showing the form with what the student typed would be kinder and is deliberately not
    done: the form carries a fresh timestamp and its fields echo input, and either would make the body
    vary. The cost is that the student types the description again (SupportRequestController::refused).
--}}

@extends('layouts.public', ['title' => __('support.refused.title')])

@section('content')
    <h1 class="track-public__title">{{ __('support.refused.title') }}</h1>

    <div class="track-public__card">
        <x-track-alert tone="danger">{{ __('support.failures.submission') }}</x-track-alert>

        <div>
            <x-track-button variant="secondary" :href="route('support.create')">{{ __('support.refused.try_again') }}</x-track-button>
        </div>
    </div>
@endsection
