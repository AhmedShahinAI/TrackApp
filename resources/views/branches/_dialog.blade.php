{{--
    Create and edit a branch. Three fields and a switch — FR-038's claim that adding a campus needs
    no code change is only true if the form is this short.

    The switch is `is_active`, not a delete control. x-track-switch emits a hidden `0` before the
    checkbox so an unchecked box arrives as false rather than as a missing key — without it,
    deactivating a branch would silently submit nothing and the branch would stay active.
--}}

@php
    $genderOptions = [
        'male' => __('branches.gender.male'),
        'female' => __('branches.gender.female'),
    ];
@endphp

<x-track-dialog id="branch-dialog" :title="__('branches.actions.create')" size="form">
    <form id="branch-dialog-form" method="POST" action="{{ route('api.v1.branches.store') }}" class="track-form">
        @csrf
        <x-track-dialog-marker for="branch-dialog" />

        <x-track-input name="campus_name_ar" :label="__('branches.fields.campus_name_ar')"
            :value="old('campus_name_ar')" :error="$errors->first('campus_name_ar')" required />

        <x-track-input name="campus_name_en" :label="__('branches.fields.campus_name_en')"
            :value="old('campus_name_en')" :error="$errors->first('campus_name_en')" dir="ltr" required />

        <x-track-select name="gender" :label="__('branches.fields.gender')"
            :options="$genderOptions" :selected="old('gender')"
            :error="$errors->first('gender')" required />

        <x-track-switch name="is_active" :label="__('branches.fields.is_active')"
            :checked="old('is_active', true)" />

        <x-slot:footer>
            <x-track-button type="submit" form="branch-dialog-form" variant="primary">{{ __('common.actions.save') }}</x-track-button>
        </x-slot:footer>
    </form>
</x-track-dialog>
