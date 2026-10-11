{{--
    T348 — step 1 of a course or staff import, to the CourseImportDialog / UserImportDialog
    references: the drop zone, the columns the file needs, and a template to start from.

    The references are single dialogs that check and import in one go. Here the same upload opens
    the six-step wizard instead (FR-106): the check, the preview and the confirmation are the
    guarantees every import keeps, so the dialog's content becomes the wizard's first step rather
    than a shortcut around it. No term: a catalogue and a staff list are the college's.

    Expects $bulk: 'courses' or 'users'.
--}}

<form
    method="POST"
    action="{{ route('api.v1.imports.'.$bulk.'.store') }}"
    enctype="multipart/form-data"
    class="track-form track-import__upload"
    x-on:submit="submitting = true"
>
    @csrf

    <x-track-alert tone="info">{{ __('imports.bulk.'.$bulk.'.columns') }}</x-track-alert>

    @if ($bulk === 'users')
        {{-- FR-105, said before the upload as well as on the preview. --}}
        <x-track-alert tone="warning" icon="key-round">{{ __('imports.bulk.users.unusable') }}</x-track-alert>
    @endif

    @include('imports._dropzone')

    <div class="track-import__actions">
        <x-track-button variant="ghost" :href="route('imports.index')">{{ __('imports.actions.back_to_list') }}</x-track-button>

        <x-track-button variant="secondary" icon="download" :href="route('imports.'.$bulk.'.template')">
            {{ __('imports.actions.template') }}
        </x-track-button>

        <x-track-button
            type="submit"
            variant="primary"
            :icon-end="app()->isLocale('ar') ? 'arrow-left' : 'arrow-right'"
            x-bind:disabled="fileName === '' || clientError !== '' || submitting"
        >{{ __('imports.actions.upload') }}</x-track-button>
    </div>
</form>
