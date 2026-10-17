{{--
    T497 — the section approval decision, to v2's ApprovalDialog: approve, return for edits, reject.

    Two dialogs, as the support channel's rejection is built. Approve and return share one: a return
    needs the reason, an approval does not. Reject is its own danger confirmation, which restates that
    rejection is final and frees the section's number (FR-176, FR-180), and it requires the reason too.
    Each reopens with what was typed when the server sends it back with an error (x-track-dialog-marker).

    Only what the state machine permits from here is offered (SectionStatus::decisions()). The server
    checks it again: the policy for who (403), the action for what (409).

    Expects: $section.
--}}

@php
    $from = App\Modules\Sections\Enums\SectionStatus::from($section->status);
    $canApproveOrReturn = $from->canBecome(App\Modules\Sections\Enums\SectionStatus::Active);
    $canReject = $from->canBecome(App\Modules\Sections\Enums\SectionStatus::Rejected);
    $action = route('api.v1.sections.approval', $section);
@endphp

@if ($canApproveOrReturn)
    <x-track-dialog id="section-decide" :title="__('sections.approval.dialog.title')" size="form">
        <form id="section-decide-form" method="POST" action="{{ $action }}" class="track-form">
            @csrf
            @method('PATCH')
            <x-track-dialog-marker for="section-decide" />

            <x-track-select
                name="decision"
                :label="__('sections.approval.actions.decide')"
                :options="[
                    ['value' => 'approve', 'label' => __('sections.approval.actions.approve')],
                    ['value' => 'return', 'label' => __('sections.approval.actions.return')],
                ]"
                :selected="old('_dialog') === 'section-decide' ? old('decision') : 'approve'"
                :error="$errors->first('decision')"
                required
            />

            <x-track-field
                :label="__('sections.approval.fields.reason')"
                for="section-decide-reason"
                :hint="__('sections.approval.dialog.reason_hint')"
                :error="old('_dialog') === 'section-decide' ? $errors->first('reason') : null"
            >
                <textarea
                    class="track-textarea"
                    id="section-decide-reason"
                    name="reason"
                    rows="3"
                    maxlength="500"
                    @if (old('_dialog') === 'section-decide' && $errors->has('reason')) aria-invalid="true" @endif
                >{{ old('_dialog') === 'section-decide' ? old('reason') : '' }}</textarea>
            </x-track-field>
        </form>

        <x-slot:footer>
            <x-track-button variant="secondary" x-on:click="close()">{{ __('common.actions.cancel') }}</x-track-button>
            <x-track-button type="submit" form="section-decide-form" variant="primary" icon="clipboard-check">{{ __('common.actions.save') }}</x-track-button>
        </x-slot:footer>
    </x-track-dialog>
@endif

@if ($canReject)
    <x-track-dialog id="section-reject" :title="__('sections.approval.dialog.reject_title')">
        <x-track-alert tone="danger">{{ __('sections.approval.dialog.reject_body') }}</x-track-alert>

        <form id="section-reject-form" method="POST" action="{{ $action }}" class="track-form">
            @csrf
            @method('PATCH')
            <x-track-dialog-marker for="section-reject" />
            <input type="hidden" name="decision" value="reject">

            <x-track-field
                :label="__('sections.approval.fields.reason')"
                for="section-reject-reason"
                :hint="__('sections.approval.dialog.reason_hint')"
                :error="old('_dialog') === 'section-reject' ? $errors->first('reason') : null"
                required
            >
                <textarea
                    class="track-textarea"
                    id="section-reject-reason"
                    name="reason"
                    rows="3"
                    minlength="10"
                    maxlength="500"
                    required
                    @if (old('_dialog') === 'section-reject' && $errors->has('reason')) aria-invalid="true" @endif
                >{{ old('_dialog') === 'section-reject' ? old('reason') : '' }}</textarea>
            </x-track-field>
        </form>

        <x-slot:footer>
            <x-track-button variant="secondary" x-on:click="close()">{{ __('common.actions.cancel') }}</x-track-button>
            <x-track-button type="submit" form="section-reject-form" variant="danger" icon="ban">{{ __('sections.approval.actions.reject') }}</x-track-button>
        </x-slot:footer>
    </x-track-dialog>
@endif
