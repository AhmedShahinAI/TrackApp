<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\RouteServiceProvider;

return [
    AppServiceProvider::class,
    // T125 — policies live under app/Modules/*/Policies, which Laravel's discovery does not scan.
    AuthServiceProvider::class,
    // T011 — registers each domain module's routes, views, translations and migrations.
    ModuleServiceProvider::class,
    // T147 — route-model binding resolves through the scoped query, so an invisible id 404s.
    RouteServiceProvider::class,
];
