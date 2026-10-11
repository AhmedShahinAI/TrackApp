{{--
    T198 — courses, to the CoursesScreen reference.

    The code column is forced LTR. `CS-101` inside an RTL row renders as `101-CS`, which is a
    different course code to anyone reading it off the screen, and the registrar who types it back
    gets "no such course". The bidi isolation is a correctness fix, not a styling preference.
--}}

@php
    $rows = $courses->map(fn ($course) => [
        'id' => $course->id,
        'code' => $course->code,
        'name_ar' => $course->name_ar,
        'name_en' => $course->name_en,
        'sections' => $course->sections_count ?? 0,
    ])->all();

    $courseColumns = [
        ['key' => 'code', 'label' => __('courses.fields.code')],
        ['key' => 'name_ar', 'label' => __('courses.fields.name_ar')],
        ['key' => 'name_en', 'label' => __('courses.fields.name_en')],
        ['key' => 'sections', 'label' => __('courses.fields.sections'), 'numeric' => true],
    ];

    $renderers = [
        'code' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr" class="track-code">'.e($row['code']).'</span>'
        ),
    ];
@endphp

<x-app-layout :title="__('courses.title')">
    <x-track-page-header :title="__('courses.title')" :subtitle="__('courses.subtitle')">
        <x-slot:actions>
            @can('course.create')
                <span x-data>
                    <x-track-button
                        variant="primary"
                        icon="plus"
                        x-on:click="$dispatch('track-dialog-open', { id: 'course-dialog' })"
                    >{{ __('courses.actions.create') }}</x-track-button>
                </span>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('courses.title')"
            :empty="__('courses.empty.message')"
            empty-icon="book-open"
            :searchable="true"
            :columns="$courseColumns"
            :rows="$rows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($courses->hasPages())
        <div class="track-pagination">{{ $courses->links() }}</div>
    @endif

    @can('course.create')
        @include('courses._dialog')
    @endcan
</x-app-layout>
