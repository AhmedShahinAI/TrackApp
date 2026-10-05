<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * F-04 fix — define the named rate limiters the routes reference.
     *
     * `throttle:api` was applied in bootstrap/app.php and ModuleServiceProvider without the limiter
     * existing. The fault was masked because `auth:sanctum` redirects a guest before the throttle
     * middleware runs, so nothing failed until authentication worked. Laravel 11 removed the
     * framework's default `api` limiter, so nothing was defining it.
     */
    private function configureRateLimiting(): void
    {
        // Constitution security floor: "API at 120 requests per minute per user".
        //
        // Keyed on the authenticated user id, with the IP as a fallback for the brief window before
        // authentication resolves. Keying on IP alone would make one campus NAT share a single
        // bucket — a building full of instructors would throttle each other.
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(120)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown'));

        // Constitution security floor: "login 5 attempts/minute per email and IP".
        //
        // Both axes in one key, which is what the floor specifies: it stops a single address
        // grinding one account, and it stops one account being ground from one address. A
        // distributed attack on one account is handled by the account lockout in FR-010, not here.
        RateLimiter::for('login', function (Request $request): Limit {
            $email = (string) $request->input('email', '');

            return Limit::perMinute(5)
                ->by(mb_strtolower(trim($email)).'|'.($request->ip() ?? 'unknown'));
        });
    }
}
