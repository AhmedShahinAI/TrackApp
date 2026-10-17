{{--
    T419 — one support request in full (Amendment 2, US10).

    Opening it moved a `new` request to `in_progress` already (SupportQueueController::show, T413).

    Everything the student wrote — the description, the contact — and every staff response is echoed
    with Blade's escaping and nothing else: no markup is interpreted anywhere (FR-153). `white-space:
    pre-line` keeps their line breaks without turning text into HTML.

    The contact carries a **visible** unverified marker (FR-145): the student typed it and nobody
    checked it, and the payload says so too, so no client can lose the flag.
--}}

@php
    $badgeTone = ['new' => 'pending', 'in_progress' => 'progress', 'resolved' => 'completed', 'closed' => 'closed', 'rejected' => 'rejected'];
    $closed = $request->status === App\Modules\Support\Enums\SupportRequestStatus::Closed;
@endphp

<x-app-layout :title="__('support.staff.detail_title', ['reference' => $detail['reference']])">
    <x-track-breadcrumbs :items="[
        ['label' => __('support.staff.title'), 'url' => route('support-requests.index')],
        ['label' => $detail['reference']],
    ]" />

    <x-track-page-header
        :title="__('support.staff.detail_title', ['reference' => $detail['reference']])"
        :subtitle="$detail['category']['label']"
    />

    @if ($errors->has('conflict'))
        <x-track-alert tone="danger" class="mb-4">{{ $errors->first('conflict') }}</x-track-alert>
    @endif

    <div class="track-support">
        <div class="track-support__main">
            <x-track-card>
                <h2 class="track-support__heading">{{ __('support.staff.fields.description') }}</h2>
                <p class="track-support__text">{{ $detail['description'] }}</p>

                @if ($detail['rejection_reason'])
                    <h2 class="track-support__heading">{{ __('support.staff.fields.rejection_reason') }}</h2>
                    <p class="track-support__text">{{ $detail['rejection_reason'] }}</p>
                @endif
            </x-track-card>

            <x-track-card>
                <h2 class="track-support__heading">{{ __('support.staff.thread') }}</h2>

                @if ($detail['responses_total'] > count($detail['responses']))
                    <p class="track-support__empty">{{ __('support.staff.showing_latest', ['shown' => App\Modules\Shared\Support\Format::number(count($detail['responses'])), 'total' => App\Modules\Shared\Support\Format::number($detail['responses_total'])]) }}</p>
                @endif

                @forelse ($detail['responses'] as $response)
                    <article class="track-support__message">
                        <header class="track-support__message-meta">
                            <strong>{{ $response['author']['name'] ?? '—' }}</strong>
                            <span>{{ App\Modules\Shared\Support\Format::dateTime($response['created_at']) }}</span>
                        </header>
                        <p class="track-support__text">{{ $response['body'] }}</p>
                    </article>
                @empty
                    <p class="track-support__empty">{{ __('support.staff.no_responses') }}</p>
                @endforelse

                @can('respond', $request)
                    @include('support.staff._respond', ['request' => $request, 'closed' => $closed])
                @endcan
            </x-track-card>
        </div>

        <aside class="track-support__side">
            <x-track-card>
                <dl class="track-support__facts">
                    <dt>{{ __('support.staff.fields.status') }}</dt>
                    <dd>
                        <x-track-status-badge :status="$badgeTone[$detail['status']['value']] ?? 'pending'">{{ $detail['status']['label'] }}</x-track-status-badge>
                    </dd>

                    <dt>{{ __('support.staff.fields.student') }}</dt>
                    <dd>
                        @if ($detail['student']['removed'])
                            {{ __('support.staff.student_removed') }}
                        @else
                            {{ $detail['student']['name'] }}<br>
                            <span dir="ltr" class="track-code">{{ $detail['student']['university_id'] }}</span>
                        @endif
                    </dd>

                    <dt>{{ __('support.staff.fields.branch') }}</dt>
                    <dd>{{ implode(__('support.staff.list_separator'), $detail['branches']) ?: '—' }}</dd>

                    <dt>{{ __('support.staff.fields.term') }}</dt>
                    <dd>{{ $detail['term'] }}</dd>

                    <dt>{{ __('support.staff.fields.contact') }}</dt>
                    <dd>
                        @if ($detail['contact']['value'])
                            <span dir="auto">{{ $detail['contact']['value'] }}</span>
                            <span class="track-support__unverified" title="{{ __('support.staff.unverified_hint') }}">
                                <x-track-icon name="circle-alert" :size="14" />
                                {{ __('support.staff.unverified') }}
                            </span>
                            <span class="track-sr-only">{{ __('support.staff.unverified_hint') }}</span>
                        @else
                            {{ __('support.staff.no_contact') }}
                        @endif
                    </dd>

                    <dt>{{ __('support.staff.fields.assignee') }}</dt>
                    <dd>{{ $detail['assignee']['name'] ?? __('support.staff.unassigned') }}</dd>

                    <dt>{{ __('support.staff.fields.waiting') }}</dt>
                    <dd>{{ $detail['waiting_label'] ?? '—' }}</dd>

                    <dt>{{ __('support.staff.fields.ip') }}</dt>
                    {{-- Cleared by the retention sweep (T451) on an anonymised request. --}}
                    <dd>
                        @if ($detail['submitted_ip'] !== null)
                            <span dir="ltr" class="track-code">{{ $detail['submitted_ip'] }}</span>
                        @else
                            —
                        @endif
                    </dd>
                </dl>
            </x-track-card>

            @can('transition', $request)
                <x-track-card>
                    @include('support.staff._transition', ['request' => $request, 'targets' => $targets])
                </x-track-card>
            @endcan

            @can('assign', $request)
                <x-track-card>
                    <form method="POST" action="{{ route('api.v1.support-requests.assignee', $request) }}" class="track-form">
                        @csrf
                        @method('PATCH')
                        <x-track-select
                            name="assigned_to"
                            :label="__('support.staff.assign.label')"
                            :options="$assignees"
                            :placeholder="__('support.staff.unassigned')"
                            :selected="$request->assigned_to"
                        />
                        <div>
                            <x-track-button type="submit" variant="secondary" size="sm" icon="user-check">
                                {{ __('support.staff.assign.submit') }}
                            </x-track-button>
                        </div>
                    </form>
                </x-track-card>
            @endcan

            <x-track-card>
                <h2 class="track-support__heading">{{ __('support.staff.history') }}</h2>

                @if ($detail['history_total'] > count($detail['history']))
                    <p class="track-support__empty">{{ __('support.staff.showing_latest', ['shown' => App\Modules\Shared\Support\Format::number(count($detail['history'])), 'total' => App\Modules\Shared\Support\Format::number($detail['history_total'])]) }}</p>
                @endif
                <ol class="track-support__history">
                    @foreach ($detail['history'] as $entry)
                        <li>
                            <x-track-status-badge :status="$badgeTone[$entry['status']] ?? 'pending'">{{ $entry['label'] }}</x-track-status-badge>
                            <span>{{ $entry['by']['name'] ?? __('support.staff.history_by_student') }} · {{ $entry['at'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </x-track-card>
        </aside>
    </div>
</x-app-layout>
