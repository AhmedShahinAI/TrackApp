{{--
    T420 — the response composer. A response cannot be edited once sent (it is append-only; the
    student may already have read it), and the hint says so before the button is pressed.

    On a closed request the composer is not offered: RespondToSupportRequest would refuse it with 409
    anyway (FR-150), and a form that can only fail is worse than a sentence saying why.
--}}

@if ($closed)
    <p class="track-support__empty">{{ __('support.staff.respond.closed') }}</p>
@else
    <form method="POST" action="{{ route('api.v1.support-requests.responses.store', $request) }}" class="track-form track-support__composer">
        @csrf
        <x-track-field
            :label="__('support.staff.respond.label')"
            for="support-response-body"
            :hint="__('support.staff.respond.hint')"
            :error="$errors->first('body')"
            required
        >
            <textarea
                class="track-textarea"
                id="support-response-body"
                name="body"
                rows="4"
                maxlength="{{ App\Modules\Settings\Settings::int(App\Modules\Settings\Settings::SUPPORT_DESCRIPTION_MAX) }}"
                required
                @error('body') aria-invalid="true" @enderror
            >{{ old('body') }}</textarea>
        </x-track-field>

        <div>
            <x-track-button type="submit" variant="primary" icon="send">{{ __('support.staff.respond.submit') }}</x-track-button>
        </div>
    </form>
@endif
