{{--
    T201 — changing a section's instructor, as a confirmation rather than a plain select.

    The body sentence is the whole reason this is a dialog. Reassignment moves *access* from here
    on; it does not move authorship of anything already recorded. The person who created an
    enrollment, withdrew a student, or wrote a withdrawal reason keeps their name on that entry
    after the handover — ChangeInstructorTest asserts exactly that by reading every record attached
    to the section before and after.

    Without the sentence, the obvious reading of "change instructor" is that the section, and
    everything in it, now belongs to someone else. A head of department acting on that belief would
    either avoid a legitimate reassignment or expect a history rewrite that will not happen.
--}}

@php
    // $instructors comes from the controller. The list is a permission query, and a permission query
    // belongs nowhere near a template — this partial renders once per section page and would issue
    // it on every one of them.
    $current = $section->instructor;
@endphp

<x-track-dialog id="assign-dialog" :title="__('sections.actions.change_instructor')">
    <p>{{ __('sections.confirm.change_instructor_body') }}</p>

    @if ($current)
        <x-track-alert tone="info">
            {{ __('sections.confirm.current_instructor', [
                'name' => $current->{'name_'.app()->getLocale()} ?? $current->name_ar,
            ]) }}
        </x-track-alert>
    @endif

    <form id="assign-dialog-form" method="POST" action="{{ route('api.v1.sections.instructor', $section) }}" class="track-form">
        @csrf
        <x-track-dialog-marker for="assign-dialog" />
        @method('PATCH')

        <x-track-select
            name="instructor_id"
            :label="__('sections.fields.instructor')"
            :options="$instructors"
            :selected="$current?->getKey()"
            :placeholder="__('sections.unassigned')"
            :error="$errors->first('instructor_id')"
        />

        <x-slot:footer>
            <x-track-button type="submit" form="assign-dialog-form" variant="primary">
                {{ __('common.actions.confirm') }}
            </x-track-button>
        </x-slot:footer>
    </form>
</x-track-dialog>
