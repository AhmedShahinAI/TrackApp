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

    {{-- T169 — details and history as two panels. The history is the same scoped query the audit
         screen uses, so a reader sees here exactly what they would see there, filtered to this
         record. --}}
    {{-- One Alpine scope around the strip AND the panels.

         The first version rendered both panels as siblings of <x-track-tabs>, outside its x-data,
         with nothing to hide either — so both were visible at once, Activity stacked above Details,
         and the tab strip changed only its own highlight. trackTabs dispatches `track-tab-changed`
         on select precisely so a caller can hold the panel state; this listens for it. --}}
    <div x-data="{ tab: 'details' }" x-on:track-tab-changed="tab = $event.detail.tab">
        <x-track-tabs
            id="section-tabs"
            :panels="true"
            :tabs="[
                'details' => __('sections.tabs.details'),
                'activity' => __('sections.tabs.activity'),
            ]"
        />

        <div
            id="section-tabs-panel-activity"
            role="tabpanel"
            aria-labelledby="section-tabs-tab-activity"
            x-show="tab === 'activity'"
            x-cloak
        >
            <x-track-card :padding="0">
                <x-track-activity-tab entity-type="Section" :entity-id="$section->id" />
            </x-track-card>
        </div>

    <x-track-card id="section-tabs-panel-details" role="tabpanel" aria-labelledby="section-tabs-tab-details" x-show="tab === 'details'">
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
    </div>
</x-app-layout>
