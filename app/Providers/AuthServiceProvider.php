<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\UserManagement\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * T125 — policy registration.
 *
 * Explicit rather than relying on Laravel's convention-based discovery, because the policies live
 * under app/Modules/<Module>/Policies/ and the convention looks in app/Policies/. A policy that is
 * silently not discovered fails open on any `authorize()` call that has no other gate.
 */
final class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
