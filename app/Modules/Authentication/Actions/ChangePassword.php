<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Actions;

use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Support\Facades\Auth;

/**
 * T119 — a person changes their own password (FR-013, FR-014, FR-015).
 *
 * The current session survives; every other session of theirs ends. That asymmetry is the whole
 * requirement: someone changing their password because they suspect it is known must not be signed
 * out of the browser they are sitting at, and must be signed out everywhere else.
 *
 * `logoutOtherDevices` is how Laravel does this without needing a session index per user: it rotates
 * the password hash stored in the session and the AuthenticateSession middleware then rejects any
 * session carrying the old one. Driver-independent, which the Redis deployment needs.
 *
 * @extends TransactionalAction<\Illuminate\Support\Carbon>
 */
final class ChangePassword extends TransactionalAction
{
    protected function handle(mixed ...$arguments): \Illuminate\Support\Carbon
    {
        /** @var User $user */
        $user = $arguments[0];
        $password = (string) $arguments[1];

        // The `hashed` cast hashes on assignment with Argon2id, so no plaintext reaches storage
        // (FR-007). saveQuietly: `password` is not in the audited allowlist, and the entry below is
        // the deliberate one.
        $user->forceFill(['password' => $password])->saveQuietly();

        /*
         * Must run after the new password is persisted. logoutOtherDevices re-hashes and re-stamps
         * the current session with the new hash; doing it first would stamp the old one and sign the
         * user out of their own session on the next request.
         */
        // Named guard, for the same reason LoginController names it: the default may be Sanctum's.
        Auth::guard('web')->logoutOtherDevices($password);

        $changedAt = now();

        // Neither payload carries the password (FR-007). `sessions_revoked` is the auditable fact.
        app(Auditor::class)->record(
            'user.password_changed',
            $user,
            [],
            ['sessions_revoked' => 'others', 'changed_at' => $changedAt->toIso8601String()],
        );

        return $changedAt;
    }
}
