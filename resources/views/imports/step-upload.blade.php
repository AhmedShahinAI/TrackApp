{{--
    T241 — step 1, upload.

    The limits are stated before anyone chooses a file, read from the same settings FileValidator
    enforces, so the hint and the refusal can never name different numbers. The browser checks the
    extension and size on selection for an immediate answer; the server checks both again plus the
    content, and is the only check that counts (FR-055).

    The drop zone is a <label> for the file input, so it is one keyboard-reachable control with a
    name rather than a div that only a mouse can use.

    The re-upload warning (FR-067) appears on the next step, once the file's hash is known — it
    cannot be shown before the bytes have been read.
--}}

@use('App\Modules\Shared\Support\Format')

<form
    method="POST"
    action="{{ $isSectionMode ? route('api.v1.sections.imports.store', $section) : route('api.v1.imports.store') }}"
    enctype="multipart/form-data"
    class="track-form track-import__upload"
    x-on:submit="submitting = true"
>
    @csrf

    {{-- The central import chooses its term; a section list's term is its section's (UploadImport). --}}
    @if ($isSectionMode)
        <x-track-alert tone="info">{{ __('imports.upload.section_columns') }}</x-track-alert>
    @else
        <x-track-select
            name="term_id"
            :label="__('imports.fields.term')"
            :options="$terms"
            :selected="old('term_id', array_key_first($terms))"
            :required="true"
            class="track-import__term"
        />
    @endif

    @include('imports._dropzone')

    <div class="track-import__actions">
        @if ($isSectionMode)
            <x-track-button variant="secondary" icon="download" :href="route('sections.import.template', $section)">
                {{ __('imports.actions.template') }}
            </x-track-button>
        @endif

        <x-track-button
            type="submit"
            variant="primary"
            :icon-end="app()->isLocale('ar') ? 'arrow-left' : 'arrow-right'"
            x-bind:disabled="fileName === '' || clientError !== '' || submitting"
        >{{ __('imports.actions.upload') }}</x-track-button>
    </div>
</form>
