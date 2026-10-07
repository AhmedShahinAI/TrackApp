{{--
    T169 — one record's history, as a panel on its detail page.

    Reused on term, branch, section and student pages. Takes the entity's type and id and renders
    the entries already scoped — the caller does no filtering, because a detail page that filtered
    its own activity would be a second place for the scope rule to live.

    ## Why it renders server-side rather than fetching

    The data is already on the page's own request, scoped by the same global scope that let the
    reader open the record at all. Fetching `/api/v1/{entity}/{id}/activity` from the browser would
    be a second authorization path for the same bytes, and the one most likely to be forgotten when
    a new entity gains a detail page.

    ## Empty is a real state, not a failure

    A record nobody has touched since it was created still has its creation entry, so an empty panel
    usually means the entries fell outside the reader's scope — their own actions and their own
    branches. Saying "no activity you can see" rather than "no activity" is the difference between
    an accurate screen and one that quietly claims nothing happened.
--}}

@props([
    'entityType',
    'entityId',
    'limit' => 20,
])

@php
    $locale = app()->getLocale();

    /*
     * Through the model, so BranchScope and SectionScope apply. The entries a coordinator sees here
     * are the ones about their own branches plus their own actions — exactly the rule
     * contracts/audit.md states for the log as a whole, inherited rather than restated.
     */
    $entries = App\Models\AuditLog::query()
        ->where('entity_type', $entityType)
        ->where('entity_id', $entityId)
        ->with('user:id,name_ar,name_en')
        ->orderByDesc('created_at')
        ->limit($limit)
        ->get();

    $rows = $entries->map(fn ($entry) => [
        'when' => $entry->created_at?->format('d/m/Y H:i') ?? '—',
        'actor' => $entry->user?->{'name_'.$locale} ?? $entry->user?->name_ar ?? '—',
        'action' => App\Modules\Audit\Services\AuditLabels::for((string) $entry->action),
    ])->all();
@endphp

<div {{ $attributes->class('track-activity') }}>
    @if ($rows === [])
        <x-track-empty-state
            icon="shield-check"
            :title="__('audit.activity.empty_title')"
            :message="__('audit.activity.empty_message')"
        />
    @else
        <x-track-data-table
            :caption="__('audit.activity.caption')"
            :dense="true"
            :columns="[
                ['key' => 'when', 'label' => __('audit.fields.created_at'), 'numeric' => true],
                ['key' => 'action', 'label' => __('audit.fields.action')],
                ['key' => 'actor', 'label' => __('audit.fields.user')],
            ]"
            :rows="$rows"
        />

        @can('audit.view')
            <div class="track-activity__more">
                <a href="{{ route('audit.index', ['entity_type' => $entityType, 'entity_id' => $entityId]) }}">
                    {{ __('audit.activity.see_all') }}
                </a>
            </div>
        @endcan
    @endif
</div>
