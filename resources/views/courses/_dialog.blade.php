{{--
    T198 — create a course, with the duplicate-code error inline on the field that caused it.

    Uniqueness is `(organization_id, code)` in the schema; the validation rule is the readable half.
    The message lands on `code` rather than at the top of the form because the fix is to change that
    one value, and a form-level banner does not say which of three fields to edit.
--}}

<x-track-dialog id="course-dialog" :title="__('courses.actions.create')" size="form">
    <form id="course-dialog-form" method="POST" action="{{ route('api.v1.courses.store') }}" class="track-form">
        @csrf
        <x-track-dialog-marker for="course-dialog" />

        <x-track-input
            name="code"
            :label="__('courses.fields.code')"
            :value="old('code')"
            :error="$errors->first('code')"
            :hint="__('courses.hints.code')"
            dir="ltr"
            maxlength="20"
            required
        />

        <x-track-input name="name_ar" :label="__('courses.fields.name_ar')" :value="old('name_ar')"
            :error="$errors->first('name_ar')" required />

        <x-track-input name="name_en" :label="__('courses.fields.name_en')" :value="old('name_en')"
            :error="$errors->first('name_en')" dir="ltr" required />

        <x-slot:footer>
            <x-track-button type="submit" form="course-dialog-form" variant="primary">{{ __('common.actions.save') }}</x-track-button>
        </x-slot:footer>
    </form>
</x-track-dialog>
