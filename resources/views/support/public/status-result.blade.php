{{--
    T431 — a student's own request, as they may see it (FR-134): reference, category, date, state, the
    rejection reason where there is one, and each response with its date. No assignee, no staff names,
    no originating IP, and not the identifier they typed. Everything here is printed escaped and as text
    (FR-153); `white-space: pre-line` keeps a response's line breaks.

    No response yet is not an empty list but a sentence: the request is still being handled (FR-136).
--}}

@extends('layouts.public', ['title' => __('support.status.result_title')])

@php
    $badgeTone = ['new' => 'pending', 'in_progress' => 'progress', 'resolved' => 'completed', 'closed' => 'closed', 'rejected' => 'rejected'];
@endphp

@section('content')
    <h1 class="track-public__title">{{ __('support.status.result_title') }}</h1>

    <div class="track-public__card">
        <dl class="track-public__facts">
            <dt>{{ __('support.submitted.reference_label') }}</dt>
            <dd><span class="track-public__reference track-public__reference--inline" dir="ltr">{{ $result['reference'] }}</span></dd>

            <dt>{{ __('support.status.state') }}</dt>
            <dd><x-track-status-badge :status="$badgeTone[$result['status']['value']] ?? 'pending'">{{ $result['status']['label'] }}</x-track-status-badge></dd>

            <dt>{{ __('support.form.category') }}</dt>
            <dd>{{ $result['category'] }}</dd>

            <dt>{{ __('support.status.submitted_at') }}</dt>
            <dd>{{ $result['submitted_at'] }}</dd>
        </dl>

        @if ($result['rejection_reason'])
            <x-track-alert tone="warning">
                <strong>{{ __('support.status.rejection_reason') }}</strong>
                <span class="track-public__text">{{ $result['rejection_reason'] }}</span>
            </x-track-alert>
        @endif

        <h2 class="track-public__subtitle">{{ __('support.status.responses') }}</h2>

        @forelse ($result['responses'] as $response)
            <article class="track-public__message">
                <p class="track-public__message-date">{{ $response['at'] }}</p>
                <p class="track-public__text">{{ $response['body'] }}</p>
            </article>
        @empty
            <p class="track-public__lead">{{ __('support.status.still_handled') }}</p>
        @endforelse

        <div>
            <x-track-button variant="secondary" :href="route('support.status.form')">{{ __('support.status.another') }}</x-track-button>
        </div>
    </div>
@endsection
