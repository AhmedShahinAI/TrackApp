{{--
    T431 — "what happened to my request?" (Amendment 2, US11). A reference and a University ID; both,
    or nothing (FR-132).

    Re-rendered with status 422 for every failure — a reference that does not exist, the wrong
    University ID, an anonymised request — and identically each time (FR-135, SC-029): `$failed` is the
    only thing that varies, and it is the same for all of them. Nothing the visitor typed is echoed
    back, not even into the fields: an echo is the first thing that would make two failures differ —
    and a validation failure (an empty field) does not flash the input either (the controller).

    Both fields are LTR inside an Arabic page: a reference and a University ID are read and typed
    left to right whatever the language around them.
--}}

@extends('layouts.public', ['title' => __('support.status.title')])

@section('content')
    <h1 class="track-public__title">{{ __('support.status.title') }}</h1>
    <p class="track-public__lead">{{ __('support.status.lead') }}</p>

    <form method="POST" action="{{ route('support.status.lookup') }}" class="track-public__card" novalidate>
        @csrf

        {{-- A failed lookup (Phase 22 review): the one message sits under the reference as its field
             error — announced (role=alert), linked by aria-describedby, the field marked invalid — and
             the University ID field points at the same message and is marked invalid too, because the
             failure is the pair's. Focus lands on the reference, so a keyboard or screen-reader user
             starts where the correction starts. No `value`: nothing typed is printed back. --}}
        @php $failed = $failed ?? false; @endphp

        <x-track-input
            name="reference"
            dir="ltr"
            :label="__('support.status.reference')"
            :hint="__('support.status.reference_hint')"
            :error="$failed ? __('support.failures.status') : null"
            autocomplete="off"
            spellcheck="false"
            :autofocus="$failed"
            required
        />

        <x-track-input
            name="university_id"
            dir="ltr"
            inputmode="numeric"
            :label="__('support.form.university_id')"
            :aria-invalid="$failed ? 'true' : null"
            :aria-describedby="$failed ? 'f-reference-error' : null"
            autocomplete="off"
            required
        />

        <div>
            <x-track-button type="submit" variant="primary" icon="search">{{ __('support.status.submit') }}</x-track-button>
        </div>
    </form>

    <p class="track-public__lead track-public__aside">
        <a href="{{ route('support.create') }}">{{ __('support.status.new_request') }}</a>
    </p>
@endsection
