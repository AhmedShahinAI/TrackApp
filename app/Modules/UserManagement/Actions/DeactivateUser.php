<?php

declare(strict_types=1);

namespace App\Modules\UserManagement\Actions;

use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Support\Facades\DB;

/**
 * T123 — deactivate an account (FR-011, FR-018, FR-049).
 *
 * Three things happen together, which is why this is a transactional action rather than a controller
 * method: the flag flips, every one of that user's sessions is destroyed, and the entry is written.
 *
 * What does NOT happen is the point of FR-018 and FR-049: the row is not deleted, `deleted_at` is
 * not set, and nothing that references the user is touched. Their name stays on every section they
 * created and every audit entry they caused. Deactivation ends access; it does not rewrite history.
 *
 * @extends TransactionalAction<User>
 */
final class DeactivateUser extends TransactionalAction
{
    protected function handle(mixed ...$arguments): User
    {
        /** @var User $user */
        $user = $arguments[0];
        $reason = isset($arguments[1]) ? (string) $arguments[1] : null;

        $user->forceFill(['is_active' => false])->save();

        $this->destroySessionsFor($user);

        /*
         * Audited explicitly rather than through the trait's generic `.updated` key, because
         * contracts/audit.md names `user.deactivated` and a reason may accompany it. The trait's
         * `updated` observer also fires on the save above and records `is_active`, so this entry
         * carries the reason and the deliberate key.
         */
        app(Auditor::class)->record(
            'user.deactivated',
            $user,
            ['is_active' => true],
            array_filter(['is_active' => false, 'reason' => $reason], static fn ($v) => $v !== null),
        );

        return $user;
    }

    /**
     * Purge this user's session rows.
     *
     * Only possible under the database session driver, where the sessions table carries `user_id`;
     * Redis and file sessions hold no index from user to session. So this is the cheap half of the
     * guarantee, not the whole of it — EnsureUserIsActive refuses every subsequent request from a
     * deactivated account on any driver, which is what makes FR-018 hold regardless of deployment.
     */
    private function destroySessionsFor(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
