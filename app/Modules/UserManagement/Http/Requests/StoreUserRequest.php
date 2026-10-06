<?php

declare(strict_types=1);

namespace App\Modules\UserManagement\Http\Requests;

use App\Modules\Shared\Rules\PasswordPolicy;
use App\Modules\UserManagement\Actions\CreateUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * T121 — creating an account (FR-003, FR-004, FR-005, FR-006).
 *
 * The email rule is `unique:users` with **no** soft-delete exclusion. That is the requirement, not
 * an omission: FR-005 refuses an address held by any account "whether active or deactivated",
 * because reusing a deactivated person's address would silently transfer their audit trail to
 * whoever got the address next. Laravel's `unique` ignores global scopes, so a soft-deleted row
 * still blocks — and UserManagement/CreateUserTest asserts exactly that.
 */
final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', new PasswordPolicy],
            'role' => ['required', 'string', Rule::in(['SUPER_ADMIN', 'BRANCH_COORDINATOR', 'INSTRUCTOR'])],

            // FR-004: at least one branch for a coordinator; optional and establishable later for
            // an instructor. required_if rather than a conditional rules() branch, so the message
            // names the actual reason.
            'branch_ids' => ['array', 'required_if:role,BRANCH_COORDINATOR', 'min:1'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],

            'section_ids' => ['array'],
            'section_ids.*' => ['integer', 'exists:sections,id'],

            'locale' => ['nullable', 'string', Rule::in(['ar', 'en'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('users.errors.email_taken'),
            'branch_ids.required_if' => __('users.errors.branches_required'),
            'branch_ids.min' => __('users.errors.branches_required'),
        ];
    }

    /**
     * Normalise before validating, so the uniqueness check compares what will be stored. Without
     * this, `A@MU.EDU.SA` passes `unique` against a stored `a@mu.edu.sa` on any case-sensitive
     * collation — which is what the test suite runs on.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => CreateUser::normaliseEmail((string) $this->input('email'))]);
        }
    }
}
