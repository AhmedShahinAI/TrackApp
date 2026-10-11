{{--
    T240 — the shared wizard frame: breadcrumbs, the six-step stepper, and the card the step renders
    in, driven by resources/js/components/import-wizard.js.

    ## The step is the server's, not the browser's

    Which step is showing follows from the job's status, so reloading the page, opening the link from
    the imports list, or coming back tomorrow lands on the same step. The reference keeps the step in
    component state, which is right for a prototype and wrong for an import that runs in the
    background for minutes: the operator is expected to leave (FR-063) and must be able to come back.

    The six labels are the approved ones, in the approved order:
    رفع الملف · مطابقة الأعمدة · الفحص · المعاينة · الاستيراد · التقرير.
--}}

@use('App\Modules\Shared\Support\Format')

@php
    $steps = [
        __('imports.steps.upload'),
        __('imports.steps.map'),
        __('imports.steps.check'),
        __('imports.steps.preview'),
        __('imports.steps.import'),
        __('imports.steps.report'),
    ];

    // ?remap=1 reopens the mapping on a job that may still be re-mapped — the preview's Back.
    if ($job !== null && request()->boolean('remap') && $job->canBeMapped() && ($canRun ?? false)) {
        $step = 2;
    }

    $partial = [1 => 'upload', 2 => 'mapping', 3 => 'validating', 4 => 'preview', 5 => 'committing', 6 => 'report'][$step];

    /*
     * US6 — the section mode. The section comes from the job once there is one, and from the page
     * before the upload (sections/import). Everything section-specific below keys off this one value.
     */
    $section = $job?->section ?? ($section ?? null);
    $wizardMode = $job?->mode ?? ($mode ?? ($section !== null ? 'section' : 'central'));
    $isSectionMode = $wizardMode === 'section';

    /*
     * T334 — the registrar mode's upload and mapping are one confirmation step: the block above the
     * header is what the operator checks, and the four columns are already proposed from it.
     */
    if ($wizardMode === 'registrar' && $step <= 2) {
        $partial = 'upload-registrar';
    }

    // T348 — the course and staff uploads' own first step; their mapping is the shared one.
    if (in_array($wizardMode, ['course', 'user'], true) && $step === 1) {
        $partial = $wizardMode === 'course' ? 'upload-courses' : 'upload-users';
    }

    $locale = app()->getLocale();
    $sectionLabel = $section === null ? null
        : trim(($section->course?->code ?? '').' / '.$section->section_number
            .' · '.($section->branch?->{'campus_name_'.$locale} ?? $section->branch?->campus_name_ar ?? ''), ' ·');

    $wizard = $job === null ? ['status' => null] : [
        'status' => $job->status,
        'progress' => $job->progressPercent(),
        'processed' => $job->processed,
        'total' => $job->total_rows,
        'waiting' => $job->isWaitingForWorker(),
        'chunk' => $job->chunkProgress(),
    ];

    $wizard += [
        'maxBytes' => $maxFileMb * 1024 * 1024,
        'messages' => [
            'badType' => __('imports.upload.bad_type'),
            'tooLarge' => __('imports.upload.too_large', ['mb' => $maxFileMb]),
            'counter' => __('imports.checking.counter'),
            'chunk' => __('imports.committing.chunk'),
        ],
    ];
@endphp

@if ($isSectionMode && $section !== null)
    {{-- From the section's page and back to it: that is where the instructor came from and where the
         roster they just loaded will be read. --}}
    <x-track-breadcrumbs :items="[
        ['label' => __('sections.title'), 'url' => route('sections.index')],
        ['label' => $section->section_number, 'url' => route('sections.show', $section)],
        ['label' => $job?->original_filename ?? __('imports.modes.section')],
    ]" />
@else
    <x-track-breadcrumbs :items="[
        ['label' => __('imports.title'), 'url' => route('imports.index')],
        ['label' => $job?->original_filename ?? __('imports.modes.'.$wizardMode)],
    ]" />
@endif

<x-track-page-header
    :title="__('imports.modes.'.$wizardMode)"
    :subtitle="$isSectionMode ? $sectionLabel : ($job?->term ? Format::term($job->term) : __('imports.subtitle'))"
/>

<div class="track-import" x-data="trackImportWizard" data-wizard="{{ json_encode($wizard) }}">
    <x-track-card>
        <x-track-stepper :steps="$steps" :current="$step" :label="__('imports.steps.label')" />
    </x-track-card>

    <x-track-card>
        @if ($errors->has('conflict'))
            <x-track-alert tone="danger" class="mb-4">{{ $errors->first('conflict') }}</x-track-alert>
        @endif

        @include('imports.step-'.$partial)
    </x-track-card>
</div>
