{{--
    T245 — step 5, importing, with the chunk counter ("الدفعة 2 من 4") and the same poller.

    A failed commit lands here too, not on the report: the work is not finished, and the next thing
    the operator can do is resume it. The alert says what survived — every completed chunk — because
    "failed" alone reads as "nothing happened", and the operator who believes that uploads again.
--}}

@use('App\Modules\Shared\Support\Format')

@php $chunk = $job->chunkProgress(); @endphp

@if ($job->status === 'failed')
    <div class="track-form">
        <x-track-alert tone="danger" :title="__('imports.report.failed')">
            {{ $job->failure_reason }} {{ __('imports.report.failed_hint') }}
        </x-track-alert>

        <div class="track-stats">
            <x-track-stat-card :value="Format::number($job->processed)" :label="__('imports.fields.processed')" />
            <x-track-stat-card :value="Format::number($job->imported)" :label="__('imports.fields.imported')" tone="green" />
            <x-track-stat-card :value="Format::number($job->duplicates)" :label="__('imports.fields.duplicates')" />
            <x-track-stat-card :value="Format::number($job->errors)" :label="__('imports.fields.errors')" tone="dark" />
        </div>

        @if ($canRun && $job->canBeResumed())
            <form method="POST" action="{{ route('api.v1.imports.resume', $job) }}" class="track-import__actions">
                @csrf
                <x-track-button type="submit" variant="primary" icon="rotate-cw">{{ __('imports.actions.resume') }}</x-track-button>
            </form>
        @endif
    </div>
@else
    <div class="track-import__progress" data-track-poll="{{ route('api.v1.imports.show', $job) }}">
        <x-track-icon name="loader-circle" :size="36" class="track-import__spinner" />

        <p class="track-import__progress-title">{{ __('imports.committing.title') }}</p>

        <x-track-progress :value="$job->progressPercent()" tone="green" :label="__('imports.steps.import')" class="track-import__bar" />

        <p class="track-import__counter num" x-text="chunkText()">
            {{ __('imports.committing.chunk', ['current' => $chunk['current'], 'total' => $chunk['total']]) }}
        </p>

        <x-track-alert tone="info" :title="__('imports.queue.waiting_title')" x-show="waiting" x-cloak>
            {{ __('imports.queue.waiting') }}
        </x-track-alert>

        {{-- The worker died between chunks (ImportJob::workerWentQuiet). Completed chunks are kept,
             and Resume continues from the checkpoint exactly as it does after a failure. --}}
        @if ($job->canBeResumed())
            <x-track-alert tone="danger" :title="__('imports.queue.stalled_title')">
                {{ __('imports.queue.stalled_commit') }}
            </x-track-alert>

            @if ($canRun)
                <form method="POST" action="{{ route('api.v1.imports.resume', $job) }}">
                    @csrf
                    <x-track-button type="submit" variant="primary" icon="rotate-cw">{{ __('imports.actions.resume') }}</x-track-button>
                </form>
            @endif
        @endif

        <p class="track-import__hint">{{ __('imports.checking.hint') }}</p>

        <x-track-button variant="secondary" size="sm" icon="house" :href="route('home')">{{ __('imports.actions.home') }}</x-track-button>
    </div>
@endif
