<?php

declare(strict_types=1);

namespace App\Modules\Sections\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * T493 — `PATCH /sections/{id}/approval`. The policy (who may decide) runs on the route. This is the
 * request's own shape: a decision, and for a return or a reject a reason of at least 10 characters
 * (FR-179, FR-180). The reason is trimmed first, so ten spaces are not a reason.
 */
final class DecideSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => is_string($this->input('reason')) ? trim($this->input('reason')) : $this->input('reason')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'return', 'reject'])],
            'reason' => ['nullable', 'required_unless:decision,approve', 'string', 'min:10', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required_unless' => __('sections.approval.errors.reason_required'),
            'reason.min' => __('sections.approval.errors.reason_short', ['min' => 10]),
        ];
    }
}
