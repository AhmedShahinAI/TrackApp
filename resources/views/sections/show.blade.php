{{--
    One section.

    Reached only through the scoped route binding, so by the time this renders the caller is known
    to be allowed to see it — there is no visibility check in the template, and there must not be
    one, or the rule would live in two places and drift.

    The roster, the enrollment actions, and the instructor history belong to US4 and US6. This page
    is what US2 needs: proof that a direct address is scoped exactly like a list.
--}}

<x-app-layout :title="$section->section_number">
    <x-track-page-header
        :title="__('sections.detail.title', ['number' => $section->section_number])"
        :subtitle="$section->course?->name_ar"
    >
        @if ($section->shared_access)
            <x-track-status-badge status="shared" />
        @endif
    </x-track-page-header>

    <x-track-card>
        <dl class="track-detail-list">
            <div><dt>{{ __('sections.fields.course') }}</dt><dd>{{ $section->course?->code }}</dd></div>
            <div><dt>{{ __('sections.fields.branch') }}</dt><dd>{{ $section->branch?->campus_name_ar }}</dd></div>
            <div><dt>{{ __('sections.fields.gender') }}</dt><dd>{{ __('sections.gender.'.$section->gender) }}</dd></div>
            <div><dt>{{ __('sections.fields.term') }}</dt><dd>{{ $section->term?->code }}</dd></div>
            <div>
                <dt>{{ __('sections.fields.instructor') }}</dt>
                <dd>{{ $section->instructor?->name_ar ?? __('sections.unassigned') }}</dd>
            </div>
        </dl>
    </x-track-card>
</x-app-layout>
