<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * T011 — registers each domain module's routes, views and translations.
 *
 * The module list is explicit rather than discovered by scanning the filesystem. A scan would make
 * the set of loaded modules depend on what happens to be on disk, which is exactly the kind of
 * implicit behaviour that makes a missing module fail silently in production and pass locally.
 * Adding a module is one line here, and that line is reviewable.
 *
 * Views and translations are namespaced per module, so `view('sections::index')` and
 * `__('sections::status.pending')` resolve to that module's own files. Shared Blade components stay
 * global under resources/views/components, because the design system belongs to no single module
 * (plan.md "Frontend structure decision").
 */
final class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Every domain module, in the order the PRD builds them.
     *
     * @var list<string>
     */
    private const MODULES = [
        'Authentication',
        'UserManagement',
        'Authorization',
        'Audit',
        'AcademicTerms',
        'AcademicStructure',
        'Sections',
        'Students',
        'Imports',
        'Settings',

        // Amendment 2 (T354) — the student support channel. Its PUBLIC routes are not loaded from
        // here but from routes/public.php (T355), so the platform's whole unauthenticated surface
        // stays readable in one short file. Its staff side, when it exists, goes in Routes/web.php
        // and Routes/api.php like any module's — and, like every module's web.php, wraps its own
        // routes in the `auth` group: `web` alone authenticates nobody. PublicSurfaceTest fails a
        // route here that forgets.
        'Support',

        'Shared',
    ];

    public function register(): void
    {
        foreach (self::MODULES as $module) {
            $config = $this->modulePath($module, 'config');

            if (is_dir($config)) {
                foreach (glob($config.'/*.php') ?: [] as $file) {
                    $this->mergeConfigFrom($file, $this->namespaceFor($module).'.'.basename($file, '.php'));
                }
            }
        }
    }

    public function boot(): void
    {
        foreach (self::MODULES as $module) {
            $namespace = $this->namespaceFor($module);

            $views = $this->modulePath($module, 'Resources/views');
            if (is_dir($views)) {
                $this->loadViewsFrom($views, $namespace);
            }

            $lang = $this->modulePath($module, 'Resources/lang');
            if (is_dir($lang)) {
                $this->loadTranslationsFrom($lang, $namespace);
            }

            $migrations = $this->modulePath($module, 'Database/migrations');
            if (is_dir($migrations)) {
                $this->loadMigrationsFrom($migrations);
            }

            $this->bootModuleRoutes($module);
        }
    }

    /**
     * Load a module's route files onto the right middleware stack.
     *
     * `web.php` and `api.php` are kept separate per module because the stacks differ: the API stack
     * carries Sanctum and the per-user throttle, the web stack carries the session and CSRF. A
     * single file would have to guess.
     */
    private function bootModuleRoutes(string $module): void
    {
        $web = $this->modulePath($module, 'Routes/web.php');
        if (is_file($web)) {
            Route::middleware('web')->group($web);
        }

        $api = $this->modulePath($module, 'Routes/api.php');
        if (is_file($api)) {
            Route::middleware(['web', 'auth:sanctum', 'throttle:api'])
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group($api);
        }
    }

    private function modulePath(string $module, string $suffix): string
    {
        return app_path('Modules/'.$module.'/'.$suffix);
    }

    /**
     * Authentication => authentication, UserManagement => user-management.
     */
    private function namespaceFor(string $module): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $module));
    }
}
