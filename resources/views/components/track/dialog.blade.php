{{--
    T079 — x-track-dialog.

    Traps focus while open and restores it to the trigger on close (SC-014). Both halves matter: a
    dialog that does not trap lets Tab walk behind the scrim into controls the user cannot see, and
    one that does not restore drops a keyboard user back at the top of the document after every
    confirmation.

    Widths 480 (confirm) and 560 (form). The `inline` variant drops the overlay for a panel that
    lives in the page — the import wizard's confirmation step uses it.
--}}

@props([
    'id',
    'title' => null,
    'size' => 'confirm',
    'inline' => false,
    'open' => false,
    'closeOnScrim' => true,
])

@php
    $classes = collect([
        'track-dialog',
        $size === 'form' ? 'track-dialog--form' : null,
        $inline ? 'track-dialog--inline' : null,
    ])->filter()->implode(' ');
@endphp

<div
    x-data="trackDialog(@js($open))"
    x-id="['{{ $id }}']"
    @if (! $inline)
        x-show="open"
        x-cloak
        class="track-dialog-scrim"
        x-on:click.self="{{ $closeOnScrim ? 'close()' : '' }}"
        x-on:keydown.escape.window="close()"
    @endif
    x-on:track-dialog-open.window="if ($event.detail?.id === '{{ $id }}') show($event.target)"
    x-on:track-dialog-close.window="if ($event.detail?.id === '{{ $id }}') close()"
>
    <div
        class="{{ $classes }}"
        role="dialog"
        aria-modal="{{ $inline ? 'false' : 'true' }}"
        @if ($title) aria-labelledby="{{ $id }}-title" @endif
        x-ref="panel"
        x-on:keydown.tab="trapFocus($event)"
        {{ $attributes }}
    >
        @if ($title || isset($close))
            <header class="track-dialog__header">
                @if ($title)
                    <h2 id="{{ $id }}-title" class="track-dialog__title">{{ $title }}</h2>
                @endif

                @if (! $inline)
                    <x-track-icon-button
                        :label="__('common.actions.close')"
                        icon="x"
                        size="sm"
                        x-on:click="close()"
                    />
                @endif
            </header>
        @endif

        <div class="track-dialog__body">{{ $slot }}</div>

        @isset($footer)
            <footer class="track-dialog__footer">{{ $footer }}</footer>
        @endisset
    </div>
</div>
