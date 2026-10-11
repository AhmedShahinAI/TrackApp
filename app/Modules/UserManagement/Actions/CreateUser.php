<?php

declare(strict_types=1);

namespace App\Modules\UserManagement\Actions;

use App\Models\User;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Support\Facades\Hash;

/**
 * T120 — create an account (FR-003, FR-004, FR-007).
 *
 * Extends TransactionalAction so the user row, the role assignment, the branch pivot, and the audit
 * entry all commit together or not at all. A half-created coordinator — a row with no branches — is
 * a user who can sign in and see nothing, with no audit entry explaining how they got there.
 *
 * @extends TransactionalAction<User>
 */
final class CreateUser extends TransactionalAction
{
    /**
     * The first argument is the validated attribute array:
     *
     *   array{name_ar: string, name_en: string, email: string, password: string, role: string,
     *         branch_ids?: list<int>, section_ids?: list<int>, locale?: string, organization_id?: int}
     *
     * Described in prose rather than as @param, because the signature is variadic — TransactionalAction
     * passes through whatever execute() was given, and a @param naming $attributes referenced a
     * parameter that does not exist.
     */
    protected function handle(mixed ...$arguments): User
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $arguments[0];

        $user = new User;

        /*
         * forceFill, because `password` is deliberately absent from the fillable list — it is set
         * only here and by ResetPassword, never by a mass-assigned update. Hashing goes through the
         * `hashed` cast, which is Argon2id per config/hashing.php.
         */
        $user->forceFill([
            // The caller's organization when the caller names one — a bulk staff import does
            // (Resolver::commitUser). "The first college" is right only while there is one.
            'organization_id' => $attributes['organization_id'] ?? $this->organizationId(),
            'name_ar' => $attributes['name_ar'],
            'name_en' => $attributes['name_en'],
            'email' => self::normaliseEmail((string) $attributes['email']),
            'password' => $attributes['password'],
            'locale' => $attributes['locale'] ?? Settings::string(Settings::DEFAULT_LOCALE),
            'is_active' => true,

            // Null on creation (FR-003). The first successful sign-in sets it, which is what makes
            // the recommendation appear exactly once (FR-012).
            'first_login_at' => null,
        ])->save();

        $user->syncRoles([$attributes['role']]);

        // FR-004: required for a coordinator, optional-and-later for an instructor.
        if (($attributes['branch_ids'] ?? []) !== []) {
            $user->coordinatorBranches()->sync($attributes['branch_ids']);
        }

        /*
         * The audit entry comes from the Auditable trait's `created` observer, which runs inside
         * this transaction. User::auditedAttributes() excludes `password`, so the allowlist — not a
         * denylist, and not this action remembering — is what keeps the hash out of the payload
         * (FR-007). ResetPasswordTest and CreateUserTest both assert it.
         */

        return $user->fresh() ?? $user;
    }

    /**
     * Lower-cased and trimmed, so FR-005's "already held by any account" cannot be defeated by
     * capitalisation. MySQL's utf8mb4_unicode_ci would compare case-insensitively anyway; SQLite
     * would not, and the test suite runs on SQLite.
     */
    public static function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * When the caller names no organization: the acting user's. "The first college" remains only
     * as the last resort for a context with nobody signed in (a seeder, a console command) — it
     * was the default for every caller, which put an account created by one college's Super Admin,
     * by hand or by the roster import, in whichever college the table returned first.
     */
    private function organizationId(): int
    {
        $acting = \Illuminate\Support\Facades\Auth::user()?->organization_id;

        if ($acting !== null) {
            return (int) $acting;
        }

        /** @var int $id */
        $id = \App\Models\Organization::query()->where('type', 'college')->value('id');

        return $id;
    }
}
