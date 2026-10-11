{{--
    T079 — x-track-dialog.

    Traps focus while open and restores it to the trigger on close (SC-014). Both halves matter: a
    dialog that does not trap lets Tab walk behind the scrim into controls the user cannot see, and
    one that does not restore drops a keyboard user back at the top of the document after every
    confirmation.

    Widths 480 (confirm) and 560 (form). The `inline` variant drops the overlay for a panel that
    lives in the page — the import wizard's confirmation step uses it.

    ## Coming back with errors

    A dialog form posts a hidden `_dialog` naming its dialog (`x-track-dialog-marker`). When the server
    refuses — 422 beside a field, or a 409 flashed as `conflict` — the redirect brings that value back
    in the old input, and the dialog it names opens itself with the refusal showing. Without this, the
    page reloaded with the dialog shut and every error in it out of sight: the person saw nothing
    happen and nothing told them why.
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
    $reopened = ! $inline && $errors->any() && old('_dialog') === $id;

    if ($reopened) {
        request()->attributes->set('track.dialog_reopened', true);
    }

    $classes = collect([
        'track-dialog',
        $size === 'form' ? 'track-dialog--form' : null,
        $inline ? 'track-dialog--inline' : null,
    ])->filter()->implode(' ');
@endphp

<div
    {{-- The id goes on the element, not only into the event matching and aria-labelledby. The
         component took `id` as a prop and used it for both of those while rendering no id at all,
         so `#user-dialog` matched nothing — the dialogs were in the DOM and unreachable by id, which
         the US1 visual suite found by looking for one. --}}
    id="{{ $id }}"
    x-data="trackDialog(@js($open || $reopened))"
    @if (! $inline)
        x-show="open"
        x-cloak
        class="track-dialog-scrim"
        x-on:click.self="{{ $closeOnScrim ? 'close()' : '' }}"
        x-on:keydown.escape.window="close()"
    @endif
    x-on:track-dialog-open.window="onOpen($event)"
    x-on:track-dialog-close.window="onClose($event)"
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

        <div class="track-dialog__body">
            @if ($reopened && $errors->has('conflict'))
                <x-track-alert tone="danger">{{ $errors->first('conflict') }}</x-track-alert>
            @endif

            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="track-dialog__footer">{{ $footer }}</footer>
        @endisset
    </div>
</div>
