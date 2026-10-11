<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Shared\Health\QueueHeartbeat;
use App\Modules\Shared\Monitoring\Alerts;
use App\Modules\Shared\Navigation\Navigation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        $this->registerDesignSystemComponents();
        $this->usePaginationView();
        $this->recordQueueHeartbeat();
        $this->allowServeCommandOverrides();
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

        /*
         * Amendment 2 (T355, review) — the floor under the whole public surface: every route in
         * routes/public.php is on the `public` group, which carries this. Per IP, because there is
         * no user to key on.
         *
         * A ceiling against page-level flooding only. The contractual limits on submission — per IP
         * and per hashed University ID, from settings (R-09, constitution "Public endpoints") — are
         * the support endpoints' own and come with them in Phase 21; this one exists so that no
         * public route, however added, is ever unthrottled.
         */
        RateLimiter::for('public', fn (Request $request): Limit => Limit::perMinute(60)
            ->by('public|'.($request->ip() ?? 'unknown')));
    }

    /**
     * T276 — every worker loop says a worker is alive; /health reads it (QueueHeartbeat).
     */
    private function recordQueueHeartbeat(): void
    {
        Event::listen(Looping::class, static fn () => QueueHeartbeat::beat());

        // PRD 8.8 — a failed job alerts at once, not at the next five-minute check.
        Event::listen(JobFailed::class, static fn (JobFailed $event) => Alerts::raise(
            'job_failed:'.$event->job->resolveName(),
            'Queued job failed: '.$event->job->resolveName(),
            ['connection' => $event->connectionName, 'exception' => $event->exception->getMessage()],
        ));
    }

    /**
     * US7 — every list paginates through resources/views/pagination/track.blade.php.
     *
     * The framework's own view is styled for Tailwind and worded from a lang/pagination.php this app
     * does not have, so Arabic lists read "« Previous" (found by the T265 axe sweep).
     */
    private function usePaginationView(): void
    {
        Paginator::defaultView('pagination.track');
        Paginator::defaultSimpleView('pagination.track');
    }

    /**
     * T067-T093 — make the design system's components available as `x-track-*`.
     *
     * They live in resources/views/components/track/, which Blade resolves with dot notation:
     * `<x-track.button>`. design-system.md §5 and every task specify `<x-track-button>`, and that
     * naming is what the whole component map is written against — so each file is aliased rather
     * than the tag style being changed in 24 tasks and a design document.
     *
     * A loop rather than 27 explicit calls: a component added later is available the moment its
     * file exists, which is one fewer thing to forget.
     */
    private function registerDesignSystemComponents(): void
    {
        $directory = resource_path('views/components/track');

        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory.'/*.blade.php') ?: [] as $file) {
            $name = basename($file, '.blade.php');

            Blade::component('components.track.'.$name, 'track-'.$name);
        }

        /*
         * The application shell, as <x-app-layout>. T095 puts it at views/layouts/app.blade.php,
         * which Blade would only resolve as a component from views/components/layouts/. Aliasing it
         * keeps the task's path and still gives a slot-based layout, which is what the file is
         * written as.
         */
        Blade::component('layouts.app', 'app-layout');

        /*
         * T153 — the sidebar, on every screen that uses the shell.
         *
         * A view composer rather than a variable each controller passes: the sidebar is a property
         * of being signed in, not of any one screen, and threading $navItems through twelve
         * controllers would mean the first one that forgot rendered a shell with no navigation.
         */
        View::composer('layouts.app', function ($view): void {
            $view->with('navItems', Navigation::forUser(request()->user()));
        });
    }

    /**
     * Let `php artisan serve` forward the environment it was given to the server it starts.
     *
     * ServeCommand forwards only a whitelist to its child process - APP_ENV, PATH, a few Herd and
     * Xdebug variables - and nothing else. Everything the child needs it reads from .env instead.
     *
     * That breaks the visual-regression suite, which boots the application through `artisan serve`
     * (playwright.config.ts) and has to point it at a database and session store it can reach. With
     * the whitelist as shipped, the suite's SESSION_DRIVER=file is silently discarded, the child
     * reads SESSION_DRIVER=redis from .env, and every page under test returns Laravel's error page.
     * axe then reports accessibility violations in Laravel's error page, which is how this was
     * found and is a genuinely confusing way to spend an afternoon.
     *
     * Only connection and driver names are added. Nothing here forwards a credential.
     */
    private function allowServeCommandOverrides(): void
    {
        if (! class_exists(ServeCommand::class)) {
            return;
        }

        ServeCommand::$passthroughVariables = array_values(array_unique(array_merge(
            ServeCommand::$passthroughVariables,
            [
                'APP_DEBUG',
                'APP_URL',
                'DB_CONNECTION',
                'DB_DATABASE',
                'SESSION_DRIVER',
                'CACHE_STORE',
                'QUEUE_CONNECTION',

                // So the visual suite can hash cheaply (playwright.config.ts explains why).
                'HASH_DRIVER',
                'BCRYPT_ROUNDS',
            ],
        )));
    }
}
