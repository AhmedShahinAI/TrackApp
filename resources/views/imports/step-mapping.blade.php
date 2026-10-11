{{--
    T242 — step 2, column mapping (FR-056, FR-058).

    Each target is paired with a select of the file's own header row, pre-filled from the suggestion
    computed at upload. Required targets carry the asterisk and the field-level error names them, and
    Continue stays disabled in the browser until every required target has a column — the server
    refuses the same thing, so a disabled button is a courtesy, not the control.

    The create-or-skip pair for unknown courses and unregistered instructors lives here, as T242
    places it, and is restated on the preview so the operator confirms the commit knowing what it will
    do with those rows. Both default to off: an import that invents courses and accounts unasked is
    the worse surprise.

    Confirming the mapping also starts the check, in one click, as the reference does.
--}}

@php
    $headers = $job->file_metadata['headers'] ?? [];
    $suggested = $job->file_metadata['suggested_mapping'] ?? [];
    $mapping = $job->mapping ?? $suggested;
    $required = array_keys(array_filter($targets));
@endphp

@if ($job->status === 'failed')
    <x-track-alert tone="danger" :title="__('imports.report.validation_failed')" class="mb-4">
        {{ $job->failure_reason }}
    </x-track-alert>
@endif

@if ($duplicateOf)
    <x-track-alert tone="warning" icon="copy" class="mb-4">{{ __('imports.upload.previously_imported') }}</x-track-alert>
@endif

@if (! $canRun)
    <x-track-alert tone="info">{{ __('imports.checking.nothing_saved') }}</x-track-alert>
@else
    <form
        method="POST"
        action="{{ route('api.v1.imports.mapping.update', $job) }}"
        class="track-form"
        x-data="trackMappingForm" data-required="{{ json_encode(array_values($required)) }}"
        x-on:submit="submitting = true"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="start_validation" value="1">

        <x-track-alert tone="info">{{ __('imports.mapping.help') }}</x-track-alert>

        @if ($errors->has('mapping'))
            <x-track-alert tone="danger">{{ $errors->first('mapping') }}</x-track-alert>
        @endif

        <div class="track-mapping">
            @foreach ($targets as $target => $isRequired)
                <x-track-select
                    :name="'mapping['.$target.']'"
                    :id="'map-'.$target"
                    :label="__('imports.targets.'.$target)"
                    :options="$headers"
                    :selected="old('mapping.'.$target, $mapping[$target] ?? null)"
                    :placeholder="__('imports.mapping.unmapped')"
                    :required="$isRequired"
                    :error="$errors->first('mapping.'.$target)"
                    :data-target="$target"
                    x-on:change="choose('{{ $target }}', $event.target.value)"
                />
            @endforeach
        </div>

        {{-- US8 — the course mode's choices about programmes the file names (CourseMode). --}}
        @if ($job->mode === 'course')
        <fieldset class="track-mapping__options">
            <legend>{{ __('imports.mapping.options') }}</legend>

            <x-track-switch
                name="options[create_missing_programs]"
                id="opt-create-programs"
                :label="__('imports.mapping.create_programs')"
                :checked="$job->option('create_missing_programs')"
            />

            <x-track-select
                name="options[new_program_unit]"
                id="opt-new-program-unit"
                :label="__('imports.mapping.new_program_unit')"
                :options="['admin' => __('programs.units.admin'), 'tech' => __('programs.units.tech')]"
                :selected="old('options.new_program_unit', $job->options['new_program_unit'] ?? 'admin')"
                :error="$errors->first('options.new_program_unit')"
            />

            {{-- Spec Edge Cases: a deactivated programme is associated only on confirmation. --}}
            <x-track-switch
                name="options[associate_inactive_programs]"
                id="opt-associate-inactive"
                :label="__('imports.mapping.associate_inactive')"
                :checked="$job->option('associate_inactive_programs')"
            />

            <p class="track-mapping__hint">{{ __('imports.mapping.programs_hint') }}</p>
        </fieldset>
        @endif

        {{-- A section list creates no course and no account, so it has no create-or-skip choice; a
             course or staff file has its own choices (above) or none. --}}
        @if ($job->mode === 'central')
        <fieldset class="track-mapping__options">
            <legend>{{ __('imports.mapping.options') }}</legend>

            <x-track-switch
                name="options[create_missing_courses]"
                id="opt-create-courses"
                :label="__('imports.mapping.create_courses')"
                :checked="$job->option('create_missing_courses')"
            />

            <x-track-switch
                name="options[create_missing_instructors]"
                id="opt-create-instructors"
                :label="__('imports.mapping.create_instructors')"
                :checked="$job->option('create_missing_instructors')"
            />

            {{-- A created course's sections must belong to a programme (FR-090), and the central
                 file names none. Without this choice, creating courses could only ever produce
                 sections the importer cannot place. --}}
            <x-track-select
                name="options[new_course_program_id]"
                id="opt-new-course-program"
                :label="__('imports.mapping.new_course_program')"
                :hint="__('imports.mapping.new_course_program_hint')"
                :options="$programs"
                :selected="old('options.new_course_program_id', $job->options['new_course_program_id'] ?? null)"
                :placeholder="__('common.labels.none')"
                :error="$errors->first('options.new_course_program_id')"
            />

            <p class="track-mapping__hint">{{ __('imports.mapping.skip_hint') }}</p>
        </fieldset>
        @endif

        <x-track-alert tone="danger" x-show="incomplete()" x-cloak>
            {{ __('imports.mapping.need_all') }}
        </x-track-alert>

        <div class="track-import__actions">
            <x-track-button variant="ghost" :href="route('imports.index')">{{ __('imports.actions.back_to_list') }}</x-track-button>
            <x-track-button
                type="submit"
                variant="primary"
                icon="scan-search"
                x-bind:disabled="incomplete() || submitting"
            >{{ __('imports.actions.start_check') }}</x-track-button>
        </div>
    </form>
@endif
