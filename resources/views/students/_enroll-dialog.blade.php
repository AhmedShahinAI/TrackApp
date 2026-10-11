{{--
    T203 — enrolling a student into a section, from the section page.

    ## The university id is the person

    `inputmode="numeric"` and `dir="ltr"`, with the hint stating that an existing id links the
    existing record rather than creating a second one. EnrollStudent normalises Arabic-Indic digits
    before the lookup, so ٤٤١٠٠١٢٣٤ and 441001234 reach the same student — accepting both *without*
    normalising was found to create exactly the duplicate FR-043 forbids.

    ## The name mismatch is reported, never applied

    When the stored name differs from the typed one, the stored name wins and the response carries a
    warning. Shown here as an alert rather than a field error, because nothing the registrar typed
    is invalid — the record simply disagrees, and resolving that is a deliberate act on the student
    page, not a side effect of an enrollment.
--}}

<x-track-dialog id="enroll-dialog" :title="__('students.actions.enroll')" size="form">
    <form id="enroll-dialog-form" method="POST" action="{{ route('api.v1.sections.enrollments.store', $section) }}" class="track-form">
        @csrf
        <x-track-dialog-marker for="enroll-dialog" />

        @if ($errors->has('university_id'))
            <x-track-alert tone="danger">{{ $errors->first('university_id') }}</x-track-alert>
        @endif

        @foreach (session('enroll_warnings', []) as $warning)
            <x-track-alert tone="warning">{{ $warning }}</x-track-alert>
        @endforeach

        <x-track-input
            name="university_id"
            :label="__('students.fields.university_id')"
            :value="old('university_id')"
            :error="$errors->first('university_id')"
            :hint="__('students.hints.university_id')"
            dir="ltr"
            inputmode="numeric"
            autocomplete="off"
            required
        />

        <x-track-input name="name_ar" :label="__('students.fields.name_ar')" :value="old('name_ar')"
            :error="$errors->first('name_ar')" required />

        <x-track-input name="name_en" :label="__('students.fields.name_en')" :value="old('name_en')"
            :error="$errors->first('name_en')" dir="ltr" />

        <x-slot:footer>
            <x-track-button type="submit" form="enroll-dialog-form" variant="primary">
                {{ __('students.actions.enroll') }}
            </x-track-button>
        </x-slot:footer>
    </form>
</x-track-dialog>
