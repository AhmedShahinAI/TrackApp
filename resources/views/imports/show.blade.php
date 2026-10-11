{{--
    The import wizard page — a new central import (no $job yet) or one job at the step its status
    implies (ImportJobController::stepFor). The frame and the six steps are in _wizard and step-*.
--}}

<x-app-layout :title="__('imports.modes.'.($job?->mode ?? ($mode ?? 'central')))">
    @include('imports._wizard')
</x-app-layout>
