<?php

declare(strict_types=1);

namespace App\Modules\UserManagement\Actions;

use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Support\Facades\DB;

/**
 * T124 — a Super Admin resets someone else's password (FR-015).
 *
 * **All** of the target's sessions end, including any they are using right now. That differs from a
 * self-initiated change, which spares the current session (FR-015, ChangePassword): a reset means
 * the old password is no longer trusted, so neither is any session established with it.
 *
 * No email is sent. There is no SMTP dependency in this feature by design — the Super Admin passes
 * the password on out of band, which the confirmation dialog says explicitly.
 *
 * @extends TransactionalAction<User>
 */
final class ResetPassword extends TransactionalAction
{
    protected function handle(mixed ...$arguments): User
    {
        /** @var User $user */
        $user = $arguments[0];
        $password = (string) $arguments[1];

        // The `hashed` cast applies on assignment, so the plaintext never reaches the database.
        // saveQuietly, because the trait's `updated` observer would otherwise write a second entry
        // for a column that is not in the audited allowlist anyway.
        // SEC-047 (D-3): the reset password is the admin's, so its owner must replace it first.
        $user->forceFill(['password' => $password, 'password_change_required' => true])->saveQuietly();

        $this->destroyAllSessionsFor($user);

        /*
         * Neither payload carries the password or its hash (FR-007). The entry records that a reset
         * happened and that sessions were revoked — which is the auditable fact — and nothing that
         * would let a reader reconstruct the credential.
         */
        app(Auditor::class)->record(
            'user.password_reset',
            $user,
            [],
            ['sessions_revoked' => true],
        );

        return $user;
    }

    private function destroyAllSessionsFor(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
