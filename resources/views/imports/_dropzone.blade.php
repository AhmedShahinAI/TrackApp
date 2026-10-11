{{--
    The file drop zone every upload step shares (T241, T334): a <label> for the file input, so it is
    one keyboard-reachable control with a name, plus the browser's and the server's refusals. Driven
    by trackImportWizard (pick, drop, fileName, clientError), so it must sit inside the wizard frame.
--}}

@use('App\Modules\Shared\Support\Format')

    <label
        for="import-file"
        class="track-dropzone"
        :class="{ 'track-dropzone--active': dragging, 'track-dropzone--chosen': fileName !== '', 'track-dropzone--error': clientError !== '' }"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="drop($event)"
    >
        <x-track-icon name="cloud-upload" :size="36" class="track-dropzone__icon" />

        <span class="track-dropzone__title" x-show="fileName === ''">{{ __('imports.upload.drop') }}</span>
        <span class="track-dropzone__hint" x-show="fileName === ''">
            {{ __('imports.upload.drop_hint', ['mb' => $maxFileMb, 'rows' => Format::number($maxRows)]) }}
        </span>

        <span class="track-dropzone__file" x-show="fileName !== ''" x-cloak>
            <span class="track-sr-only">{{ __('imports.upload.selected') }}</span>
            <bdi dir="ltr" x-text="fileName"></bdi>
            <span class="track-dropzone__hint num" x-text="fileSize"></span>
        </span>

        <span class="track-btn track-btn--secondary track-btn--sm" aria-hidden="true">
            <x-track-icon name="folder-open" :size="16" />
            {{ __('imports.actions.choose') }}
        </span>

        <input
            id="import-file"
            x-ref="file"
            type="file"
            name="file"
            accept=".xlsx,.csv"
            class="track-sr-only"
            required
            x-on:change="pick($event)"
        >
    </label>

    <x-track-alert tone="danger" x-show="clientError !== ''" x-cloak>
        <span x-text="clientError"></span>
    </x-track-alert>

    {{-- The server's refusal, focused on arrival (review L4): the page reloads after a refused
         upload, and without this a screen-reader user starts again from the top and has to find
         out why. role="alert" (x-track-alert, danger) also announces it. --}}
    @if ($errors->has('file'))
        <x-track-alert tone="danger" tabindex="-1" class="track-import__refusal" x-data x-init="$el.focus()">{{ $errors->first('file') }}</x-track-alert>
    @endif
