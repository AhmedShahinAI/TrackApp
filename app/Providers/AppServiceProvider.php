<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Settings\Settings;
use App\Modules\Shared\Health\QueueHeartbeat;
use App\Modules\Shared\Monitoring\Alerts;
use App\Modules\Shared\Navigation\Navigation;
use App\Modules\Support\Http\SupportLimitReached;
use App\Modules\Support\Services\SupportLimits;
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
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

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
        $this->refuseUnsafeProductionConfiguration();
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

        // The sign-in limits (constitution floor: 5 a minute per email and IP, plus the per-account
        // limit of SEC-041) live in LoginController, not here. A named `login` limiter was defined
        // here once and never attached to a route, which read as protection that did not exist; it
        // was removed (SEC-043) so there is one place to look.

        /*
         * Amendment 2 (T355, Phase 19 review) — the floor under the whole public surface: every
         * route in routes/public.php is on the `public` group, which carries this. Per IP, because
         * there is no user to key on, and read from the `public_throttle` setting (constitution IV).
         *
         * A ceiling against page-level flooding only. The contractual limits on submission — per IP
         * and per hashed University ID (R-09) — are SupportLimits'; this one exists so that no
         * public route, however added, is ever unthrottled.
         *
         * Its refusal is SupportLimitReached, the one refusal of the public surface (FR-130), not
         * Laravel's: that would be a second page shape with X-RateLimit-Limit and -Remaining headers,
         * telling the caller which limiter fired and how close it is to the next. The headers
         * Laravel offers the callback are dropped for the same reason; only Retry-After is sent.
         */
        RateLimiter::for('public', function (Request $request): Limit {
            $limit = Settings::limit(Settings::PUBLIC_THROTTLE);

            return Limit::perMinutes($limit['decay_minutes'], $limit['max_attempts'])
                ->by('public|'.SupportLimits::addressBucket($request->ip()))
                // The whole window, not the seconds left on this caller's counter (Phase 20 review):
                // a countdown is a readout of the counter. The floor is per address and says nothing
                // about any identifier, so it may differ from the submission limits' 3600 — those two
                // must not differ from each other, and do not (SupportLimits::retryAfterSeconds).
                ->response(fn (Request $request): Response => (new SupportLimitReached(
                    $limit['decay_minutes'] * 60,
                ))->render($request));
        });
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
    /**
     * SEC-073 (security plan G-12) — a production or staging host will not run with a development
     * configuration.
     *
     * .env.example is the developer file (debug on, debug logging), and copying it to a server is the
     * easy mistake: stack traces to every visitor, every query binding in the logs, the session cookie
     * sent over plain HTTP. Each of these is one line, so the cheapest control is to refuse to start.
     * The failure is loud on purpose — a deploy that boots with debug on is worse than one that does
     * not boot. .env.production.example carries the values that pass.
     */
    private function refuseUnsafeProductionConfiguration(): void
    {
        if (! $this->app->environment(['production', 'staging'])) {
            return;
        }

        $problems = array_keys(array_filter([
            'APP_DEBUG must be false' => (bool) config('app.debug'),
            'SESSION_SECURE_COOKIE must be true' => ! (bool) config('session.secure'),
            'LOG_LEVEL must not be debug' => config('logging.channels.json.level') === 'debug',
        ]));

        if ($problems !== []) {
            throw new RuntimeException('Unsafe configuration for '.$this->app->environment().': '.implode('; ', $problems).'. See .env.production.example.');
        }
    }

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
