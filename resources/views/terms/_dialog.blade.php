{{--
    T196 — the three term dialogs: create, change status, set current.

    They live in one partial because the two confirmations are only meaningful next to the term they
    act on, and both need the list the page already has.
--}}

@php
    use App\Modules\AcademicTerms\Actions\ChangeTermStatus;

    $locale = app()->getLocale();

    $semesterOptions = [
        'first' => __('terms.semesters.first'),
        'second' => __('terms.semesters.second'),
        'summer' => __('terms.semesters.summer'),
    ];
@endphp

@can('term.create')
    <x-track-dialog id="term-dialog" :title="__('terms.actions.create')">
        <form id="term-dialog-form" method="POST" action="{{ route('api.v1.terms.store') }}" class="track-form">
            @csrf
            <x-track-dialog-marker for="term-dialog" />

            <x-track-input name="name_ar" :label="__('terms.fields.name_ar')" :value="old('name_ar')"
                :error="$errors->first('name_ar')" required />

            <x-track-input name="name_en" :label="__('terms.fields.name_en')" :value="old('name_en')"
                :error="$errors->first('name_en')" dir="ltr" required />

            {{-- dir=ltr: an academic year is 1446/1447, and the pair renders reversed when the field
                 inherits RTL — a registrar reading it back types a different year. --}}
            <x-track-input name="academic_year" :label="__('terms.fields.academic_year')"
                :value="old('academic_year')" :error="$errors->first('academic_year')"
                dir="ltr" placeholder="1446/1447" required />

            <x-track-select name="semester" :label="__('terms.fields.semester')"
                :options="$semesterOptions" :selected="old('semester')"
                :error="$errors->first('semester')" required />

            <x-track-input type="date" name="start_date" :label="__('terms.fields.start_date')"
                :value="old('start_date')" :error="$errors->first('start_date')" required />

            <x-track-input type="date" name="end_date" :label="__('terms.fields.end_date')"
                :value="old('end_date')" :error="$errors->first('end_date')" required />

            {{-- No status field. A term is always created as a draft (contracts/academic.md) so the
                 first transition out of draft is an auditable act with a named author. --}}

            <x-slot:footer>
                <x-track-button type="submit" form="term-dialog-form" variant="primary">{{ __('common.actions.save') }}</x-track-button>
            </x-slot:footer>
        </form>
    </x-track-dialog>
@endcan

@can('term.change_status')
    @foreach ($terms as $term)
        @foreach (ChangeTermStatus::TRANSITIONS[$term->status] ?? [] as $to)
            @php
                $dialogId = 'term-'.$term->id.'-to-'.$to;
                $reasonId = 'term-'.$term->id.'-'.$to.'-reason';
                // Only the dialog the refusal came back for shows it; the others share the field name.
                $reasonError = old('_dialog') === $dialogId ? $errors->first('reason') : null;
            @endphp

            <x-track-dialog
                :id="$dialogId"
                :title="__('terms.actions.change_status')"
            >
                {{-- Names both states. "Change status" alone does not distinguish closing a term
                     from reopening one, and those have opposite consequences for every section. --}}
                <p>{{ __('terms.confirm.transition', [
                    'term' => $term->{'name_'.$locale} ?? $term->name_ar,
                    'from' => __('status.'.$term->status),
                    'to' => __('status.'.$to),
                ]) }}</p>

                <form id="term-{{ $term->id }}-to-{{ $to }}-form" method="POST" action="{{ route('api.v1.terms.status', $term) }}" class="track-form">
                    @csrf
                    @method('PATCH')
                    <x-track-dialog-marker :for="$dialogId" />
                    <input type="hidden" name="status" value="{{ $to }}">

                    {{-- ChangeTermStatus::REASON_REQUIRED_FROM. Any exit from `closed` needs a
                         reason, archiving included: both rewrite what a closed term means. The
                         server's rule (422 beside this field) is the authority; `required` and
                         `minlength` only save the round trip — minlength counts the spaces the
                         server trims, which is why the error must be able to come back here. --}}
                    @if (in_array($term->status, ChangeTermStatus::REASON_REQUIRED_FROM, true))
                        <x-track-field :label="__('common.fields.reason')" :for="$reasonId" required
                            :hint="__('terms.hints.reason')" :error="$reasonError">
                            <textarea class="track-textarea" id="{{ $reasonId }}" name="reason"
                                rows="3" minlength="3" maxlength="500" required
                                @if ($reasonError) aria-invalid="true" @endif
                            >{{ $reasonError !== null ? old('reason') : '' }}</textarea>
                        </x-track-field>
                    @endif

                    <x-slot:footer>
                        <x-track-button type="submit" :form="'term-'.$term->id.'-to-'.$to.'-form'" :variant="$to === 'archived' ? 'danger' : 'primary'">
                            {{ __('common.actions.confirm') }}
                        </x-track-button>
                    </x-slot:footer>
                </form>
            </x-track-dialog>
        @endforeach
    @endforeach
@endcan

@can('term.set_current')
    @foreach ($terms as $term)
        @continue($term->is_current)

        <x-track-dialog :id="'term-'.$term->id.'-current'" :title="__('terms.confirm.set_current_title')">
            {{-- States what gets cleared. SetCurrentTerm holds the single-current invariant inside
                 one locked transaction; the person clicking cannot otherwise see what they displace. --}}
            <p>{{ __('terms.confirm.set_current_body') }}</p>

            <form id="term-{{ $term->id }}-current-form" method="POST" action="{{ route('api.v1.terms.set-current', $term) }}">
                @csrf
                @method('PATCH')
                <x-track-dialog-marker :for="'term-'.$term->id.'-current'" />

                <x-slot:footer>
                    <x-track-button type="submit" :form="'term-'.$term->id.'-current-form'" variant="primary">
                        {{ __('terms.actions.set_current') }}
                    </x-track-button>
                </x-slot:footer>
            </form>
        </x-track-dialog>
    @endforeach
@endcan
