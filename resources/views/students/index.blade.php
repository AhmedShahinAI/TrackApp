{{--
    T202 — the students list, to the StudentsScreen reference.

    A student has no branch and no section of their own; they are visible only through an enrollment
    the caller can see (Student::constrainToBranches). So the absence of a branch filter here is not
    an omission — there is nothing to filter, and the list is already the caller's scope.

    Each student appears once however many in-scope sections they are in (FR-043). That is held by a
    `whereExists` in the model rather than a `distinct()` here, so a future caller cannot forget it.
--}}

@php
    $rows = $students->map(fn ($student) => [
        'id' => $student->id,
        'university_id' => $student->university_id,
        'name_ar' => $student->name_ar,
        'name_en' => $student->name_en,
        'enrollments' => $student->enrollments_count ?? 0,
    ])->all();

    $studentColumns = [
        ['key' => 'university_id', 'label' => __('students.fields.university_id'), 'numeric' => true],
        ['key' => 'name_ar', 'label' => __('students.fields.name_ar')],
        ['key' => 'name_en', 'label' => __('students.fields.name_en')],
        ['key' => 'enrollments', 'label' => __('students.fields.enrollments'), 'numeric' => true],
    ];

    /*
     * The university id is forced LTR with tabular figures. Two separate problems:
     *
     *   - A 9-digit id inside an RTL cell keeps its digits in logical order but the surrounding
     *     neutral characters reorder, so a copied id can come back with its parts swapped.
     *   - Proportional figures make columns of ids impossible to scan for the one that differs by
     *     a single digit, which is the comparison a registrar actually performs here.
     */
    $renderers = [
        'university_id' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span dir="ltr" class="track-code">'.e($row['university_id']).'</span>'
        ),
    ];
@endphp

<x-app-layout :title="__('students.title')">
    <x-track-page-header :title="__('students.title')" :subtitle="__('students.subtitle')" />

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('students.title')"
            :empty="__('students.empty.message')"
            empty-icon="users"
            :searchable="true"
            :row-url="fn (array $row) => route('students.show', $row['id'])"
            :columns="$studentColumns"
            :rows="$rows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($students->hasPages())
        <div class="track-pagination">{{ $students->links() }}</div>
    @endif
</x-app-layout>
