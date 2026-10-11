{{--
    T334 — the registrar import's upload and its confirmation (FR-096 to FR-101).

    Two states of one step, because the contract puts "what the file was understood to mean" between
    the upload and the check:

      - before a file: the drop zone, with the term and the instructor the export cannot carry (FR-101);
      - after it: a confirmation panel — the metadata block as the file wrote it beside what each
        value resolved to (branch, course, section) — then one button that confirms and starts the
        check.

    A campus or course that cannot be resolved never reaches the panel: the upload refuses it with a
    422 on `file` (ResolveRegistrarSection), shown here as a danger alert. A course name shared by
    several courses does reach it, as a choice the operator makes or leaves — leaving it skips the
    rows rather than guessing (FR-100).

    No React reference exists for this step (design-system.md §9 row 26): it is built from the same
    x-track-* parts as the central upload so it reads as the same wizard.
--}}

@if ($job === null)
    <form
        method="POST"
        action="{{ route('api.v1.imports.registrar.store') }}"
        enctype="multipart/form-data"
        class="track-form track-import__upload"
        x-on:submit="submitting = true"
    >
        @csrf

        <x-track-alert tone="info">{{ __('imports.registrar.upload_help') }}</x-track-alert>

        <div class="track-form-grid">
            <x-track-select
                name="term_id"
                :label="__('imports.fields.term')"
                :options="$terms"
                :selected="old('term_id', array_key_first($terms))"
                :required="true"
                :error="$errors->first('term_id')"
            />

            <x-track-select
                name="instructor_id"
                :label="__('imports.registrar.fields.instructor')"
                :hint="__('imports.registrar.instructor_hint')"
                :options="$instructors"
                :selected="old('instructor_id')"
                :placeholder="__('imports.registrar.choose_instructor')"
                :required="true"
                :error="$errors->first('instructor_id')"
            />
        </div>

        @include('imports._dropzone')

        <div class="track-import__actions">
            <x-track-button variant="ghost" :href="route('imports.index')">{{ __('imports.actions.back_to_list') }}</x-track-button>
            <x-track-button
                type="submit"
                variant="primary"
                :icon-end="app()->isLocale('ar') ? 'arrow-left' : 'arrow-right'"
                x-bind:disabled="fileName === '' || clientError !== '' || submitting"
            >{{ __('imports.actions.upload') }}</x-track-button>
        </div>
    </form>
@else
    @php
        $metadata = $job->file_metadata['registrar'] ?? [];
        $headers = $job->file_metadata['headers'] ?? [];
        $mapping = $job->mapping ?? ($job->file_metadata['suggested_mapping'] ?? []);
        $panel = $registrar ?? [];
    @endphp

    @if ($job->status === 'failed')
        <x-track-alert tone="danger" :title="__('imports.report.validation_failed')" class="mb-4">
            {{ $job->failure_reason }}
        </x-track-alert>
    @endif

    @if ($duplicateOf)
        <x-track-alert tone="warning" icon="copy" class="mb-4">{{ __('imports.upload.previously_imported') }}</x-track-alert>
    @endif

    <x-track-card :title="__('imports.registrar.confirm_title')" class="track-registrar">
        <p class="track-registrar__lead">{{ __('imports.registrar.confirm_lead') }}</p>

        {{-- What the file said, beside what it was understood to mean. --}}
        <dl class="track-registrar__facts">
            @foreach (['campus' => 'branch', 'degree' => 'degree', 'course' => 'course', 'activity' => 'activity', 'section' => 'section'] as $field => $resolvedKey)
                <div class="track-registrar__fact">
                    <dt>{{ __('imports.registrar.fields.'.$field) }}</dt>
                    <dd>
                        <bdi class="track-registrar__given">{{ $metadata[$field] ?? '—' }}</bdi>
                        <span class="track-registrar__arrow" aria-hidden="true">{{ app()->isLocale('ar') ? '←' : '→' }}</span>
                        <bdi class="track-registrar__resolved">{{ $panel[$resolvedKey] ?? '—' }}</bdi>
                    </dd>
                </div>
            @endforeach

            <div class="track-registrar__fact">
                <dt>{{ __('imports.fields.term') }}</dt>
                <dd><bdi class="track-registrar__resolved">{{ $panel['term'] ?? '—' }}</bdi></dd>
            </div>

            <div class="track-registrar__fact">
                <dt>{{ __('imports.registrar.fields.instructor') }}</dt>
                <dd><bdi class="track-registrar__resolved">{{ $panel['instructor'] ?? '—' }}</bdi></dd>
            </div>
        </dl>
    </x-track-card>

    @if (! $canRun)
        <x-track-alert tone="info">{{ __('imports.checking.nothing_saved') }}</x-track-alert>
    @else
        <form
            method="POST"
            action="{{ route('api.v1.imports.mapping.update', $job) }}"
            class="track-form"
            x-on:submit="submitting = true"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="start_validation" value="1">

            {{-- FR-100 — several courses share this name. The operator picks one, or leaves it and the
                 rows are skipped and listed in the report. --}}
            @if (($panel['course_candidates'] ?? []) !== [])
                <x-track-alert tone="warning">{{ __('imports.registrar.course_ambiguous', ['value' => $metadata['course'] ?? '']) }}</x-track-alert>

                <x-track-select
                    name="options[course_id]"
                    id="opt-registrar-course"
                    :label="__('imports.registrar.fields.course')"
                    :options="$panel['course_candidates']"
                    :selected="old('options.course_id', $job->options['course_id'] ?? null)"
                    :placeholder="__('imports.registrar.skip_rows')"
                    :error="$errors->first('options.course_id')"
                />
            @endif

            {{-- FR-090 — a section this import creates needs a programme; offered only when there is
                 no existing section to join. --}}
            @if (($panel['section_exists'] ?? false) === false)
                <x-track-select
                    name="options[program_id]"
                    id="opt-registrar-program"
                    :label="__('imports.registrar.fields.program')"
                    :hint="__('imports.registrar.program_hint')"
                    :options="$panel['programs'] ?? []"
                    :selected="old('options.program_id', $job->options['program_id'] ?? null)"
                    :placeholder="__('imports.registrar.program_from_course')"
                    :error="$errors->first('options.program_id')"
                />
            @endif

            {{-- The four data columns, proposed from the header. Folded away: on the registration
                 unit's own file the proposal is already right, and confirming is the whole step. --}}
            <details class="track-registrar__columns" @if ($errors->has('mapping') || $errors->has('mapping.*')) open @endif>
                <summary>{{ __('imports.registrar.adjust_columns') }}</summary>

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
                        />
                    @endforeach
                </div>
            </details>

            <div class="track-import__actions">
                <x-track-button variant="ghost" :href="route('imports.index')">{{ __('imports.actions.back_to_list') }}</x-track-button>
                <x-track-button
                    type="submit"
                    variant="primary"
                    icon="scan-search"
                    x-bind:disabled="submitting"
                >{{ __('imports.registrar.confirm_and_check') }}</x-track-button>
            </div>
        </form>
    @endif
@endif
