{{--
    T243 — step 3, checking, with the 5-second poller (FR-063, FR-079).

    ## The queue-waiting state (C-13)

    The reference has no screen for "no worker has picked this up", because a prototype has no queue.
    A real deployment does, and when its workers are down the honest message is that the import is
    saved and waiting — not a bar stuck at zero, which reads as broken and gets the file uploaded a
    second time. The alert is driven by ImportJob::isWaitingForWorker(), a heartbeat older than the
    queueing, and it disappears by itself on the first poll after a worker starts.

    The page reloads itself when the status moves on, which lands the operator on the preview.
--}}

@use('App\Modules\Shared\Support\Format')

<div class="track-import__progress" data-track-poll="{{ route('api.v1.imports.show', $job) }}">
    <x-track-icon name="loader-circle" :size="36" class="track-import__spinner" />

    <p class="track-import__progress-title">{{ __('imports.checking.title') }}</p>

    <x-track-progress :value="$job->progressPercent()" tone="green" :label="__('imports.steps.check')" class="track-import__bar" />

    <p class="track-import__counter num" x-text="counterText()">
        {{ __('imports.checking.counter', ['processed' => Format::number($job->processed), 'total' => Format::number($job->total_rows)]) }}
    </p>

    <x-track-alert tone="info" :title="__('imports.queue.waiting_title')" x-show="waiting" x-cloak>
        {{ __('imports.queue.waiting') }}
    </x-track-alert>

    {{-- A worker started and went silent: not "waiting", and not going to finish by itself. Nothing
         was written, so the way out is to check the mapping and validate again. --}}
    @if ($job->workerWentQuiet())
        <x-track-alert tone="danger" :title="__('imports.queue.stalled_title')">
            {{ __('imports.queue.stalled_validation') }}
        </x-track-alert>

        @if ($canRun)
            <x-track-button variant="primary" :href="route('imports.show', ['import' => $job, 'remap' => 1])">
                {{ __('imports.actions.remap') }}
            </x-track-button>
        @endif
    @endif

    <p class="track-import__hint">{{ __('imports.checking.hint') }} {{ __('imports.checking.nothing_saved') }}</p>

    <x-track-button variant="secondary" size="sm" icon="house" :href="route('home')">{{ __('imports.actions.home') }}</x-track-button>
</div>
