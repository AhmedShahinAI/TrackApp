<?php

declare(strict_types=1);

namespace App\Modules\UserManagement\Http\Requests;

use App\Modules\UserManagement\Actions\CreateUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * T121 — editing an account.
 *
 * `password` is absent on purpose. The contract puts password changes on two other endpoints — the
 * owner's own change, and the Super Admin's reset — each of which invalidates sessions. Allowing it
 * here would be a third path that does not, and FR-015 would hold only on two of three.
 */
final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \App\Models\User|null $target */
        $target = $this->route('user');

        return [
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],

            // Ignoring the row under edit, so saving a form without changing the email is not a
            // duplicate of itself. Soft-deleted rows still block (FR-005).
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($target?->getKey()),
            ],

            'locale' => ['sometimes', 'nullable', 'string', Rule::in(['ar', 'en'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['email.unique' => __('users.errors.email_taken')];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => CreateUser::normaliseEmail((string) $this->input('email'))]);
        }
    }
}
