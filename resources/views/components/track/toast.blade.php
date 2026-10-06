{{--
    T078 — x-track-toast and its region.

    Fixed to the bottom-start corner, which is bottom-left in Arabic. Auto-dismisses after ~4s,
    except on the error tone: an error carries a retry action (FR-080, FR-081), and a message that
    disappears before the user can act on it is worse than no message.

    The region is aria-live, not each toast, so a screen reader announces arrivals without the
    region itself being re-read every time.

    Two forms in one component. `region` renders the Alpine-driven queue, mounted once in the
    layout; without it the component renders a single static toast for a server-rendered message
    that needs no queue. One file because they must stay visually identical, and two files is how
    they stop being.
--}}

@props([
    'region' => false,
    'tone' => 'info',
])

@if ($region)
    <div
        id="track-toasts"
        class="track-toast-region"
        role="region"
        aria-live="polite"
        aria-label="{{ __('common.labels.notifications') }}"
        x-data="trackToasts"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div class="track-toast" :class="`track-toast--${toast.tone}`">
                <span class="track-alert__icon">
                    <svg class="track-icon" width="18" height="18" aria-hidden="true" focusable="false">
                        <use :href="`{{ config('icons.sprite') }}#${toast.icon}`"></use>
                    </svg>
                </span>

                <div class="track-toast__body">
                    <span x-text="toast.message"></span>

                    {{-- Only the error tone gets a retry. A success toast with a button would invite
                         a second submission of something that already worked. --}}
                    <template x-if="toast.tone === 'error' && toast.retry">
                        <button type="button" class="track-btn track-btn--ghost track-btn--sm" x-on:click="retry(toast)">
                            {{ __('common.actions.retry') }}
                        </button>
                    </template>
                </div>

                <button
                    type="button"
                    class="track-icon-btn track-icon-btn--sm"
                    aria-label="{{ __('common.actions.dismiss') }}"
                    x-on:click="dismiss(toast.id)"
                >
                    <svg class="track-icon" width="16" height="16" aria-hidden="true" focusable="false">
                        <use href="{{ config('icons.sprite') }}#x"></use>
                    </svg>
                </button>
            </div>
        </template>
    </div>
@else
    <div {{ $attributes->class('track-toast track-toast--'.$tone) }} role="status" aria-live="polite">
        <span class="track-alert__icon">
            <x-track-icon :name="config('icons.tones.'.$tone, 'info')" :size="18" />
        </span>
        <div class="track-toast__body">{{ $slot }}</div>
    </div>
@endif
