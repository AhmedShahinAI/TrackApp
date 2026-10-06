<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Http\Controllers;

use App\Modules\Authentication\Actions\ChangePassword;
use App\Modules\Shared\Rules\PasswordPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * T119 — PUT /auth/password (FR-013, FR-014).
 *
 * Each refusal carries its own field error, which is the opposite of the sign-in screen's single
 * generic message — and deliberately so. On sign-in, naming the wrong field helps an attacker. Here
 * the person is already authenticated, so withholding which of the three fields is wrong only
 * wastes their time.
 */
final class PasswordController
{
    public function edit(): View
    {
        return view('profile.password');
    }

    public function update(Request $request, ChangePassword $changePassword): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', new PasswordPolicy],
        ], [
            'password.confirmed' => __('auth.errors.mismatch'),
        ]);

        if (! Hash::check((string) $validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('auth.errors.wrong_current'),
            ]);
        }

        // FR-014. Checked against the stored hash rather than by comparing the two plaintexts,
        // because the current password is only known to this request as the string the user typed —
        // and that string is what Hash::check just confirmed, so this is the authoritative test.
        if (Hash::check((string) $validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('auth.errors.same_as_current'),
            ]);
        }

        $changedAt = $changePassword->execute($user, (string) $validated['password']);

        if ($request->expectsJson()) {
            return response()->json(['data' => ['changed_at' => $changedAt->toIso8601String()]]);
        }

        return redirect()
            ->route('profile.password.edit')
            ->with('status', __('auth.password_change.changed').' — '.__('auth.password_change.changed_message'));
    }
}
