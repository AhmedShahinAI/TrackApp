{{--
    T201 — creating and editing a section, to the SectionDialog reference.

    ## The duplicate-key error lands on section_number

    `(term_id, branch_id, course_id, approval_key)` is unique in the schema, so the collision is
    really about four fields at once. The message still belongs on `section_number`, because that is
    the one the registrar can change: the term, branch and course are the section they meant to
    create, and a form-level banner would leave them guessing which of the four to edit.

    Rejected sections are excluded from the rule exactly as they are from the index — `approval_key`
    is NULL for them and NULLs do not collide — so a number freed by a rejection is reusable.

    ## No gender field

    A section's gender is derived from its branch (FR-040). A select here would let the two disagree,
    and then there are two answers to one question.

    ## Degree and activity are settings, not enums

    Settings::values() supplies both, so a college that adds "intermediate diploma" changes a row
    and nothing in this form learns a new word (constitution IV, FR-108/FR-109).
--}}

@php
    use App\Modules\Settings\Settings;

    /*
     * Settings::options() returns rows of ['value', 'ar', 'en'], which x-track-select cannot read
     * directly — it looks for 'label' and would fall back to printing the machine value, so a
     * registrar would be choosing between "bachelor" and "diploma" in English on an Arabic form.
     * Mapped to value => localised label here, once.
     */
    $localise = fn (array $rows) => collect($rows)
        ->mapWithKeys(fn (array $row) => [$row['value'] => $row[app()->getLocale()] ?? $row['value']])
        ->all();

    $degreeOptions = $localise(Settings::options(Settings::SECTION_DEGREES));
    $activityOptions = $localise(Settings::options(Settings::SECTION_ACTIVITIES));
@endphp

<x-track-dialog id="section-dialog" :title="__('sections.actions.create')" size="form">
    <form id="section-dialog-form" method="POST" action="{{ route('api.v1.sections.store') }}" class="track-form">
        @csrf
        <x-track-dialog-marker for="section-dialog" />

        <x-track-select name="term_id" :label="__('sections.fields.term')" :options="$termOptions"
            :selected="old('term_id', $currentTermId ?? null)" :error="$errors->first('term_id')" required />

        <x-track-select name="branch_id" :label="__('sections.fields.branch')" :options="$branchOptions"
            :selected="old('branch_id')" :error="$errors->first('branch_id')" required />

        <x-track-select name="course_id" :label="__('sections.fields.course')" :options="$courseOptions"
            :selected="old('course_id')" :error="$errors->first('course_id')" required />

        {{-- Required since Amendment 1 (FR-090): a section with no programme cannot be reported on.
             A placeholder, not the first programme preselected: a default here would file every
             hurried section under whichever programme sorts first, and nothing would flag it. --}}
        <x-track-select name="program_id" :label="__('sections.fields.program')" :options="$programOptions"
            icon="graduation-cap" :placeholder="__('sections.placeholders.program')"
            :selected="old('program_id')" :error="$errors->first('program_id')" required />

        <x-track-input name="section_number" :label="__('sections.fields.number')"
            :value="old('section_number')" :error="$errors->first('section_number')"
            dir="ltr" maxlength="20" required />

        <x-track-select name="instructor_id" :label="__('sections.fields.instructor')"
            :options="$instructorOptions" :selected="old('instructor_id')"
            :placeholder="__('sections.unassigned')" :error="$errors->first('instructor_id')" />

        @if ($degreeOptions !== [])
            <x-track-select name="degree" :label="__('sections.fields.degree')" :options="$degreeOptions"
                :selected="old('degree')" :placeholder="__('common.labels.none')"
                :error="$errors->first('degree')" />
        @endif

        @if ($activityOptions !== [])
            <x-track-select name="activity" :label="__('sections.fields.activity')" :options="$activityOptions"
                :selected="old('activity')" :placeholder="__('common.labels.none')"
                :error="$errors->first('activity')" />
        @endif

        <x-slot:footer>
            <x-track-button type="submit" form="section-dialog-form" variant="primary">{{ __('common.actions.save') }}</x-track-button>
        </x-slot:footer>
    </form>
</x-track-dialog>
