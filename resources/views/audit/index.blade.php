{{--
    T168 — the audit log, built to the AuditScreen + AuditTable reference.

    Four parts, in the reference's order: a page header carrying the export action, a filter card,
    the table, and a standing notice that entries cannot be changed.

    The notice is not decoration and it is not dismissible. The log's immutability is a property
    people have to be able to rely on — that is the whole reason FR-031 enforces it in three layers
    — and a guarantee nobody is told about is a guarantee nobody acts on. Someone looking at an
    entry that is wrong needs to know the answer is a correcting entry, not an edit, before they go
    looking for an edit button that will never exist.

    The before/after pair is a native <details> disclosure in its own column, not the reference's
    dialog and not an expanding row. A dialog takes the entry out of its context, which is the one
    thing a reader compares it against; an expanding row would need the data-table component to
    learn about row state, which is a change to a shared component for one screen's benefit.
    <details> costs no JavaScript, is keyboard-operable and screen-reader-announced by the browser,
    and degrades to visible text if styling fails.
--}}

@php
    $locale = app()->getLocale();

    $rows = $entries->map(fn ($entry) => [
        'id' => $entry->id,
        'when' => $entry->created_at?->format('d/m/Y H:i') ?? '—',
        'actor' => $entry->user?->{'name_'.$locale} ?? $entry->user?->name_ar ?? '—',
        'action' => $entry->action,
        'entity' => $entry->entity_type === null
            ? '—'
            : __('audit.entities.'.$entry->entity_type).($entry->entity_id ? ' #'.$entry->entity_id : ''),
        'ip' => $entry->ip_address,
        'old' => $entry->old_values,
        'new' => $entry->new_values,
    ])->all();

    // Declared here, not inside the attribute: a nested array literal in a double-quoted Blade
    // attribute closes it at the first inner quote.
    $diff = function (array $row): string {
        $pairs = '';

        foreach ([['old', 'before'], ['new', 'after']] as [$side, $label]) {
            $values = is_array($row[$side]) ? $row[$side] : [];

            if ($values === []) {
                continue;
            }

            $lines = '';

            foreach ($values as $field => $value) {
                /*
                 * Lang::has, not `?: $field`. `__()` returns the key itself when there is no
                 * translation, and a key is truthy — so the fallback never ran and the diff printed
                 * `audit.fields.campus_name_ar` at the reader. The column name is the honest
                 * fallback: it is what the database calls the field, and it is readable.
                 */
                $label = \Illuminate\Support\Facades\Lang::has('audit.fields.'.$field)
                    ? __('audit.fields.'.$field)
                    : $field;

                $lines .= '<div><dt>'.e($label).'</dt>'
                    .'<dd>'.e(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)).'</dd></div>';
            }

            $pairs .= '<div class="track-audit__side track-audit__side--'.$side.'">'
                .'<h3>'.e(__('audit.'.$label)).'</h3><dl>'.$lines.'</dl></div>';
        }

        if ($pairs === '') {
            return '';
        }

        return '<details class="track-audit__diff"><summary>'.e(__('audit.details')).'</summary>'
            .'<div class="track-audit__diff-body">'.$pairs.'</div></details>';
    };

    $renderers = [
        'action' => fn (array $row) => new Illuminate\Support\HtmlString(
            '<span class="track-audit__action">'
            .e(App\Modules\Audit\Services\AuditLabels::for((string) $row['action']))
            .'</span>'
        ),
        'diff' => fn (array $row) => new Illuminate\Support\HtmlString($diff($row)),
    ];

    $actorOptions = ['' => __('common.labels.all')]
        + $actors->mapWithKeys(fn ($u) => [$u->id => $u->{'name_'.$locale} ?? $u->name_ar])->all();

    $actionOptions = ['' => __('common.labels.all')]
        + collect(App\Modules\Audit\Services\AuditLabels::contractActions())->mapWithKeys(
            fn (string $key) => [$key => App\Modules\Audit\Services\AuditLabels::for($key)]
        )->all();
@endphp

<x-app-layout :title="__('nav.audit')">
    <x-track-page-header :title="__('nav.audit')" :subtitle="__('audit.subtitle')">
        <x-slot:actions>
            @can('audit.export')
                <a
                    href="{{ route('api.v1.audit-logs.export', request()->query()) }}"
                    class="track-btn track-btn--secondary"
                >
                    <span class="track-btn__icon"><x-track-icon name="download" :size="18" /></span>
                    <span class="track-btn__label">{{ __('common.actions.export') }}</span>
                </a>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    {{-- FR-031, stated where it is relevant rather than in documentation nobody reads. --}}
    <x-track-alert tone="info" class="mb-4">
        {{ __('audit.immutable_notice') }}
    </x-track-alert>

    <x-track-card class="mb-4">
        <form method="GET" action="{{ route('audit.index') }}" class="track-audit__filters">
            <x-track-select
                name="user_id"
                size="sm"
                :label="__('audit.fields.user')"
                :options="$actorOptions"
                :selected="request('user_id')"
            />

            <x-track-select
                name="action"
                size="sm"
                :label="__('audit.fields.action')"
                :options="$actionOptions"
                :selected="request('action')"
            />

            <x-track-select
                name="entity_type"
                size="sm"
                :label="__('audit.fields.entity_type')"
                :options="$entityOptions"
                :selected="request('entity_type')"
            />

            <x-track-input
                name="date_from"
                type="date"
                size="sm"
                :label="__('audit.fields.date_from')"
                :value="request('date_from')"
            />

            <x-track-input
                name="date_to"
                type="date"
                size="sm"
                :label="__('audit.fields.date_to')"
                :value="request('date_to')"
            />

            <div class="track-audit__filter-actions">
                <x-track-button type="submit" variant="secondary" size="sm" icon="filter">
                    {{ __('common.actions.filter') }}
                </x-track-button>
            </div>
        </form>
    </x-track-card>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('nav.audit')"
            :empty="__('audit.empty')"
            empty-icon="shield-check"
            :columns="[
                ['key' => 'when', 'label' => __('audit.fields.created_at'), 'numeric' => true],
                ['key' => 'actor', 'label' => __('audit.fields.user')],
                ['key' => 'action', 'label' => __('audit.fields.action')],
                ['key' => 'entity', 'label' => __('audit.fields.entity_type'), 'numeric' => true],
                ['key' => 'ip', 'label' => __('audit.fields.ip_address'), 'numeric' => true],
                ['key' => 'diff', 'label' => __('audit.details'), 'sortable' => false],
            ]"
            :rows="$rows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($entries->hasPages())
        <div style="margin-top: var(--space-4)">{{ $entries->links() }}</div>
    @endif
</x-app-layout>
