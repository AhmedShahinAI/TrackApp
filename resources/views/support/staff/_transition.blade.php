{{--
    T420 — the state control, and the rejection confirmation.

    Only the states the machine permits from here are offered (TransitionSupportRequest::TRANSITIONS),
    and `closed` only to someone holding support.close. The server checks all of it again; offering
    what would be refused is the thing being avoided, not the protection.

    Rejection is not one of the plain options. It ends the request unresolved and puts the reason in
    front of the student, so it goes through an `x-track-dialog` danger confirmation that restates the
    impact and requires the reason (FR-149, FR-167). Sent back with an error, the dialog reopens with
    what was typed (x-track-dialog-marker).
--}}

@php
    $plain = array_values(array_filter($targets, fn ($to) => $to !== App\Modules\Support\Enums\SupportRequestStatus::Rejected));
    $canReject = in_array(App\Modules\Support\Enums\SupportRequestStatus::Rejected, $targets, true);
@endphp

<h2 class="track-support__heading">{{ __('support.staff.transition.label') }}</h2>

@if ($plain === [] && ! $canReject)
    <p class="track-support__empty">{{ __('support.staff.transition.none') }}</p>
@endif

@if ($plain !== [])
    <form method="POST" action="{{ route('api.v1.support-requests.status', $request) }}" class="track-form">
        @csrf
        @method('PATCH')
        <x-track-select
            name="status"
            :label="__('support.staff.transition.to')"
            :options="array_map(fn ($to) => ['value' => $to->value, 'label' => __($to->labelKey())], $plain)"
            required
        />
        <div>
            <x-track-button type="submit" variant="secondary" size="sm">{{ __('support.staff.transition.submit') }}</x-track-button>
        </div>
    </form>
@endif

@if ($canReject)
    <div class="track-support__reject">
        <x-track-button variant="danger" size="sm" icon="ban"
            x-data
            x-on:click="$dispatch('track-dialog-open', { id: 'support-reject' })">
            {{ __('support.staff.reject.open') }}
        </x-track-button>
    </div>

    <x-track-dialog id="support-reject" :title="__('support.staff.reject.title')">
        <x-track-alert tone="danger">{{ __('support.staff.reject.impact') }}</x-track-alert>

        <form id="support-reject-form" method="POST" action="{{ route('api.v1.support-requests.status', $request) }}" class="track-form">
            @csrf
            @method('PATCH')
            <x-track-dialog-marker for="support-reject" />
            <input type="hidden" name="status" value="rejected">

            <x-track-field
                :label="__('support.staff.reject.reason')"
                for="support-reject-reason"
                :hint="__('support.staff.reject.reason_hint')"
                :error="$errors->first('reason')"
                required
            >
                <textarea
                    class="track-textarea"
                    id="support-reject-reason"
                    name="reason"
                    rows="3"
                    maxlength="500"
                    required
                    @error('reason') aria-invalid="true" @enderror
                >{{ old('_dialog') === 'support-reject' ? old('reason') : '' }}</textarea>
            </x-track-field>
        </form>

        <x-slot:footer>
            <x-track-button variant="secondary" x-on:click="close()">{{ __('common.actions.cancel') }}</x-track-button>
            <x-track-button type="submit" form="support-reject-form" variant="danger">{{ __('support.staff.reject.confirm') }}</x-track-button>
        </x-slot:footer>
    </x-track-dialog>
@endif
