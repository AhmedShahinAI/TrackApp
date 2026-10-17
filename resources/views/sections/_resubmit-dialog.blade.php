{{--
    T498 — FR-179: a returned section is editable by its creator, who corrects it and sends it back.

    The fields a return usually asks about, prefilled with the section's own values: course, programme,
    number, degree, activity. The term and branch stay. They are the section the creator meant to make,
    and changing either is a new section, not a correction. The request goes through the same form
    request as create (StoreSectionRequest), so the same uniqueness and setting rules hold, and the
    server rechecks who may resubmit (SectionPolicy::resubmit).

    Expects: $section, $courseOptions, $programOptions.
--}}

@php
    use App\Modules\Settings\Settings;

    $localise = fn (array $rows) => collect($rows)
        ->mapWithKeys(fn (array $row) => [$row['value'] => $row[app()->getLocale()] ?? $row['value']])
        ->all();
    $reopened = old('_dialog') === 'section-resubmit';
    $value = fn (string $field) => $reopened ? old($field) : $section->getAttribute($field);
@endphp

<x-track-dialog id="section-resubmit" :title="__('sections.approval.actions.resubmit')" size="form">
    @if ($section->review_reason)
        <x-track-alert tone="warning">{{ __('sections.approval.returned_notice', ['reason' => $section->review_reason]) }}</x-track-alert>
    @endif

    <form id="section-resubmit-form" method="POST" action="{{ route('api.v1.sections.resubmit', $section) }}" class="track-form">
        @csrf
        @method('PATCH')
        <x-track-dialog-marker for="section-resubmit" />

        <x-track-select name="course_id" :label="__('sections.fields.course')" :options="$courseOptions"
            :selected="(string) $value('course_id')" :error="$reopened ? $errors->first('course_id') : null" required />

        <x-track-select name="program_id" :label="__('sections.fields.program')" :options="$programOptions"
            :selected="(string) $value('program_id')" :error="$reopened ? $errors->first('program_id') : null" required />

        <x-track-input name="section_number" :label="__('sections.fields.number')" dir="ltr"
            :value="(string) $value('section_number')" :error="$reopened ? $errors->first('section_number') : null" required />

        <x-track-select name="degree" :label="__('sections.fields.degree')" :options="$localise(Settings::options(Settings::SECTION_DEGREES))"
            :selected="(string) $value('degree')" :error="$reopened ? $errors->first('degree') : null" />

        <x-track-select name="activity" :label="__('sections.fields.activity')" :options="$localise(Settings::options(Settings::SECTION_ACTIVITIES))"
            :selected="(string) $value('activity')" :error="$reopened ? $errors->first('activity') : null" />
    </form>

    <x-slot:footer>
        <x-track-button variant="secondary" x-on:click="close()">{{ __('common.actions.cancel') }}</x-track-button>
        <x-track-button type="submit" form="section-resubmit-form" variant="primary" icon="send">{{ __('sections.approval.actions.resubmit') }}</x-track-button>
    </x-slot:footer>
</x-track-dialog>
