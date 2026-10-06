<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Http\Controllers;

use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * T116 — sign in and sign out.
 *
 * The ordering in store() is the security-relevant part, and it is not the obvious one:
 *
 *   1. throttle check          — before the credential check, so a locked-out attacker learns
 *                                nothing new from attempt six
 *   2. find the user           — by normalised email
 *   3. verify the password     — ALWAYS, even when no user was found, against a dummy hash, so the
 *                                response time does not reveal whether the address exists
 *   4. check is_active         — only after the password is known to be correct, so "this account
 *                                is deactivated" is never a probe for valid addresses
 *   5. regenerate the session  — before the response, against fixation
 *
 * Step 3 is the one people leave out. Without it, a wrong address returns in ~1 ms and a wrong
 * password in ~32 ms (Argon2id), and the difference is a reliable account-enumeration oracle.
 *
 * Step 4's order matters for the same reason: FR-011 requires the deactivated message, and
 * returning it before verifying the password would let anyone enumerate deactivated accounts.
 */
final class LoginController
{
    /**
     * A hash to verify against when no user matched, so the work done is the same either way.
     * Generated once per process from a value nothing can authenticate with.
     */
    private static ?string $decoyHash = null;

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->intended(self::landingRoute($request->user()));
        }

        return view('auth.login');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim((string) $credentials['email']));
        $key = $this->throttleKey($email, $request);

        // FR-010 — checked first, so the sixth attempt costs an attacker nothing to learn.
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            app(Auditor::class)->record('auth.login_blocked', null, [], ['email' => $email]);

            return $this->fail(
                $request,
                __('auth.locked', ['seconds' => $seconds]),
                Response::HTTP_TOO_MANY_REQUESTS,
                ['retry_after' => $seconds],
            );
        }

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        /*
         * Always do the hash work, whether or not a user matched.
         *
         * Written as an explicit branch rather than `$user?->password ?? self::decoyHash()`:
         * `password` is non-nullable, so the nullsafe and the ?? were doing two different jobs in
         * one expression and PHPStan was right to call it out. The point of the line is that both
         * paths pay for one Argon2id verification, and that reads better said plainly.
         */
        $hashToVerify = $user === null ? self::decoyHash() : $user->password;

        $passwordMatches = Hash::check((string) $credentials['password'], $hashToVerify);

        if ($user === null || ! $passwordMatches) {
            RateLimiter::hit($key, 60);

            app(Auditor::class)->record('auth.login_failed', $user, [], ['email' => $email]);

            // FR-009 — one message, never naming the field.
            return $this->fail($request, __('auth.failed'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // FR-011 — only reachable with a correct password, so it cannot be used to enumerate.
        if (! $user->is_active) {
            app(Auditor::class)->record('auth.login_blocked', $user, [], ['reason' => 'inactive']);

            return $this->fail($request, __('auth.deactivated'), Response::HTTP_FORBIDDEN);
        }

        RateLimiter::clear($key);

        /*
         * FR-012 — captured BEFORE the write. Once first_login_at is set, the fact that this was
         * the first sign-in is unrecoverable, and the banner has to be decided from the state as it
         * was when the request arrived.
         */
        $isFirstLogin = $user->first_login_at === null;

        /*
         * The guard is named, never inferred. This platform authenticates with the session guard,
         * and `auth:sanctum` calls Auth::shouldUse('sanctum') — so the default guard on any request
         * that has passed through the API stack is Sanctum's RequestGuard, which has no login().
         * Relying on the default produced exactly that 500, and FirstLoginTest caught it.
         */
        Auth::guard('web')->login($user, remember: false);

        // Against session fixation: the id the client arrived with never becomes an authenticated id.
        $request->session()->regenerate();

        DB::transaction(function () use ($user, $isFirstLogin): void {
            $user->forceFill(array_filter([
                'first_login_at' => $isFirstLogin ? now() : null,
                'last_login_at' => now(),
            ], static fn ($v) => $v !== null))->saveQuietly();

            app(Auditor::class)->record('auth.login', $user, [], ['first_login' => $isFirstLogin]);
        });

        if ($isFirstLogin) {
            // Read once by the layout and discarded, so it cannot reappear on a later sign-in.
            $request->session()->flash('first_login', true);
        }

        $landing = self::landingRoute($user);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name_ar' => $user->name_ar,
                    'name_en' => $user->name_en,
                    'email' => $user->email,
                    'locale' => $user->locale,
                    'roles' => $user->getRoleNames()->all(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->all(),
                    'scope' => [
                        'branch_ids' => $user->coordinatorBranchIds(),
                        'section_ids' => $user->accessibleSectionIds(),
                    ],
                    'first_login' => $isFirstLogin,
                    'landing_route' => $landing,
                ],
            ]);
        }

        return redirect()->intended($landing);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            app(Auditor::class)->record('auth.logout', $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['data' => []]);
        }

        return redirect()->route('login');
    }

    /**
     * FR-017 — the starting page belonging to the role.
     *
     * Keyed on permission rather than on the role name, so a fourth role added as a seeder row
     * (FR-020) lands somewhere sensible without this method changing.
     */
    public static function landingRoute(User $user): string
    {
        return $user->can('dashboard.view') ? '/home' : '/profile';
    }

    /**
     * FR-010 — five attempts per email AND IP. Both axes in one key: it stops one address grinding
     * one account, and one account being ground from one address. A distributed attack on a single
     * account is the account lockout's job, not the limiter's.
     */
    private function throttleKey(string $email, Request $request): string
    {
        return 'login|'.$email.'|'.($request->ip() ?? 'unknown');
    }

    /**
     * One refusal path for all three failures.
     *
     * The web branch always redirects back with the message flashed, whatever the logical status.
     * Throwing a ValidationException with a custom status does NOT flash errors — Laravel only
     * redirects-with-errors for 422 — so a 429 produced a bare response and the screen had no
     * countdown to show, which is what LoginThrottleTest caught. The API branch keeps the real
     * status code, because a client needs it.
     *
     * `retry_after` is flashed as well as returned: login.blade.php reads it from the session to
     * tick the countdown down, which is the "visible countdown" FR-010 asks for.
     *
     * @param  array<string, mixed>  $extra
     */
    private function fail(Request $request, string $message, int $status, array $extra = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message] + $extra, $status);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $message])
            ->with($extra);
    }

    private static function decoyHash(): string
    {
        return self::$decoyHash ??= Hash::make('no-account-matches-this-'.bin2hex(random_bytes(8)));
    }
}
