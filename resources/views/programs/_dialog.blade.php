{{--
    T318 — create and edit a programme, to the ProgramsScreen dialog reference.

    560px, two columns: code (LTR) beside the specialty unit, the two names, then the active switch
    spanning both. One dialog for both cases, filled from the event payload: `program-dialog` with no
    `program` in the detail is a create; with one it is an edit, and the form switches method and
    action (trackProgramDialog).

    The cannot-delete notice appears only when editing (FR-092). Saying it on a create would answer a
    question nobody creating a programme is asking; saying it on an edit is saying it exactly where
    someone came looking for a delete button.

    The switch is `is_active`, not a delete control. x-track-switch emits a hidden `0` first so an
    unchecked box arrives as false rather than as a missing key.
--}}

@php
    $unitOptions = [
        'admin' => __('programs.units.admin'),
        'tech' => __('programs.units.tech'),
    ];

    /*
     * Sent back with errors (x-track-dialog, "Coming back with errors"): reopen as the same create or
     * edit, with what was typed. The programme is rebuilt from its id on the server, never from
     * posted JSON, and `draft` carries the typed values over it.
     */
    $returned = old('_dialog') === 'program-dialog';
    $returnedProgram = $returned && ctype_digit((string) old('_dialog_program'))
        ? $programs->getCollection()->firstWhere('id', (int) old('_dialog_program'))
        : null;
    $fieldError = fn (string $field): ?string => $returned ? $errors->first($field) : null;

    // The title follows the case: x-text reads trackProgramDialog's title(), from the parent scope.
    $title = new Illuminate\Support\HtmlString(
        '<span x-text="title()">'.e(__('programs.actions.create')).'</span>'
    );
@endphp

<div x-data="trackProgramDialog"
     data-base="{{ url('/api/v1/programs') }}" data-store="{{ route('api.v1.programs.store') }}"
     data-create-title="{{ __('programs.actions.create') }}" data-edit-title="{{ __('programs.actions.edit') }}"
     data-program="{{ $returnedProgram === null ? '' : json_encode($returnedProgram->only(['id', 'code', 'name_ar', 'name_en', 'unit']) + ['is_active' => $returnedProgram->is_active ? '1' : '0', 'locked' => ($returnedProgram->sections_count ?? 0) > 0 ? '1' : '0']) }}"
     data-draft="{{ $returned ? json_encode(['code' => old('code'), 'name_ar' => old('name_ar'), 'name_en' => old('name_en'), 'unit' => old('unit'), 'is_active' => old('is_active')]) : '' }}">
    {{-- The form wraps the dialog: Blade hoists x-slot content out of the component body, so a form
         opened inside the default slot would not contain the footer and its submit button. --}}
    <form method="POST" x-bind:action="action()" novalidate
          x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf
        <x-track-dialog-marker for="program-dialog" />
        <input type="hidden" name="_dialog_program" :value="programId()">

        <x-track-dialog id="program-dialog" size="form" :title="$title">
            <template x-if="program">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <div class="track-form-grid">
                <x-track-input
                    name="code"
                    :label="__('programs.fields.code')"
                    :hint="__('programs.hints.code')"
                    :required="true"
                    :error="$fieldError('code')"
                    dir="ltr"
                    maxlength="20"
                    x-bind:value="field('code')"
                    x-bind:readonly="codeLocked()"
                />

                <x-track-select
                    name="unit"
                    :label="__('programs.fields.unit')"
                    :options="$unitOptions"
                    :required="true"
                    :error="$fieldError('unit')"
                    x-model="unit"
                />

                <x-track-input
                    name="name_ar"
                    :label="__('programs.fields.name_ar')"
                    :required="true"
                    :error="$fieldError('name_ar')"
                    maxlength="255"
                    x-bind:value="field('name_ar')"
                />

                <x-track-input
                    name="name_en"
                    :label="__('programs.fields.name_en')"
                    :required="true"
                    :error="$fieldError('name_en')"
                    dir="ltr"
                    maxlength="255"
                    x-bind:value="field('name_en')"
                />

                <div class="track-form-grid__full">
                    <x-track-switch name="is_active" id="program-is-active" :label="__('programs.fields.is_active')"
                        :checked="true" x-bind:checked="active" />
                </div>

                {{-- FR-092 — said only when editing, which is where a delete button would be sought. --}}
                <template x-if="program">
                    <div class="track-form-grid__full">
                        <x-track-alert tone="info">{{ __('programs.notice') }}</x-track-alert>
                    </div>
                </template>
            </div>

            <x-slot:footer>
                <x-track-button variant="secondary" type="button"
                                x-on:click="$dispatch('track-dialog-close', { id: 'program-dialog' })">
                    {{ __('common.actions.cancel') }}
                </x-track-button>

                <x-track-button type="submit" x-bind:class="{ 'track-btn--loading': submitting }"
                                x-bind:disabled="submitting">
                    {{ __('common.actions.save') }}
                </x-track-button>
            </x-slot:footer>
        </x-track-dialog>
    </form>
</div>
