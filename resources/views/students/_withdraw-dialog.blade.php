{{--
    T203 — the withdraw dialog, FR-047 and FR-082.

    ## One dialog, not one per row

    This rendered a complete dialog — form, textarea, buttons — for every active enrollment on the
    page. A 400-student roster therefore shipped 400 modals, all hidden, all parsed, all in the DOM.
    The dialog is now a single element whose target is set by the row that opened it, so the page
    weighs the same whether the section holds four students or four hundred.

    `enrollment` and `student` come in on the open event; the form's action is bound from the id.

    ## The reason is the point, so it is required in three places

    `required` and `minlength` on the textarea, `required|min:3` on a trimmed value in the
    controller, and a 409 in WithdrawEnrollment itself. That is not belt-and-braces for its own
    sake: a withdrawal with no reason is a number that dropped and nobody a year later can say why,
    and the one layer where it is optional is the layer through which every unexplained withdrawal
    will arrive.

    ## Danger tone, but the body says what is NOT destroyed

    Someone who believes this erases the student will either avoid a legitimate withdrawal or use it
    carelessly. Both are worse than reading one accurate sentence.
--}}

@can('enrollment.withdraw')
    @php
        // Sent back with errors: reopen on the same enrollment. The action is rebuilt from the id
        // rather than echoed from the post, so a forged field cannot point the form anywhere else.
        $returned = old('_dialog') === 'withdraw-dialog' && ctype_digit((string) old('_dialog_enrollment'));
        $returnedEnrollment = $returned ? (int) old('_dialog_enrollment') : null;
    @endphp

    {{-- trackWithdrawDialog (resources/js/components/forms.js): the open event is re-dispatched
         from the row's button, so the dialog records that button as its trigger and Escape hands
         focus back to it. Sent back with errors, it starts on the same enrollment, the action
         rebuilt from the id on the server. --}}
    <div
        x-data="trackWithdrawDialog"
        data-enrollment="{{ $returnedEnrollment ?? '' }}"
        data-student="{{ $returned ? (string) old('_dialog_subject', '') : '' }}"
        data-action="{{ $returned ? route('api.v1.enrollments.withdraw', $returnedEnrollment) : '' }}"
        x-on:track-withdraw-open.window="open($event)"
    >
        <x-track-dialog id="withdraw-dialog" :title="__('students.confirm.withdraw_title')">
            <p>{{ __('students.confirm.withdraw_body') }}</p>

            {{-- Names the person. One shared dialog has to say who it is about, or the confirmation
                 confirms nothing — which is the one risk of collapsing N dialogs into one. --}}
            <p class="track-dialog__subject" x-text="student"></p>

            <form id="withdraw-dialog-form" method="POST" :action="action" class="track-form">
                @csrf
                @method('PATCH')
                <x-track-dialog-marker for="withdraw-dialog" />
                <input type="hidden" name="_dialog_enrollment" :value="enrollment">
                <input type="hidden" name="_dialog_subject" :value="student">

                <x-track-field
                    :label="__('students.fields.withdraw_reason')"
                    for="withdraw-reason"
                    :hint="__('students.hints.withdraw_reason')"
                    :error="$errors->first('reason')"
                    required
                >
                    <textarea
                        class="track-textarea"
                        id="withdraw-reason"
                        name="reason"
                        rows="3"
                        minlength="3"
                        maxlength="500"
                        required
                        @error('reason') aria-invalid="true" @enderror
                    >{{ $returned ? old('reason') : '' }}</textarea>
                </x-track-field>

                <x-slot:footer>
                    <x-track-button type="submit" form="withdraw-dialog-form" variant="danger">
                        {{ __('students.actions.withdraw') }}
                    </x-track-button>
                </x-slot:footer>
            </form>
        </x-track-dialog>
    </div>
@endcan
