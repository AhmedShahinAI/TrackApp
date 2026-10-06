<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ModuleServiceProvider;

return [
    AppServiceProvider::class,
    // T125 — policies live under app/Modules/*/Policies, which Laravel's discovery does not scan.
    AuthServiceProvider::class,
    // T011 — registers each domain module's routes, views, translations and migrations.
    ModuleServiceProvider::class,
];
