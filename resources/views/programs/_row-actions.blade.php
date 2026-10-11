{{--
    T317 — the edit action on a programme row. The values travel in data attributes, escaped by
    Blade, and the handler reads `$el.dataset` — the CSP build of Alpine cannot parse a JSON literal
    (see users._row-actions).
--}}

@props(['program'])

<div class="track-row-actions">
    @can('program.edit')
        <x-track-icon-button
            :label="__('programs.actions.edit')"
            icon="pencil"
            size="sm"
            data-program-id="{{ $program->id }}"
            data-program-code="{{ $program->code }}"
            data-program-name-ar="{{ $program->name_ar }}"
            data-program-name-en="{{ $program->name_en }}"
            data-program-unit="{{ $program->unit }}"
            data-program-active="{{ $program->is_active ? '1' : '0' }}"
            data-program-locked="{{ ($program->sections_count ?? 0) > 0 ? '1' : '0' }}"
            x-on:click="$dispatch('track-dialog-open', {
                id: 'program-dialog',
                program: {
                    id: $el.dataset.programId,
                    code: $el.dataset.programCode,
                    name_ar: $el.dataset.programNameAr,
                    name_en: $el.dataset.programNameEn,
                    unit: $el.dataset.programUnit,
                    is_active: $el.dataset.programActive,
                    locked: $el.dataset.programLocked,
                },
            })"
        />
    @endcan
</div>
