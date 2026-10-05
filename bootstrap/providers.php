<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;

return [
    AppServiceProvider::class,
    // T011 — registers each domain module's routes, views, translations and migrations.
    ModuleServiceProvider::class,
];
