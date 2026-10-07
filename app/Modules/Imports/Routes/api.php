<?php

declare(strict_types=1);

use App\Modules\Imports\Http\Controllers\ImportJobController;
use Illuminate\Support\Facades\Route;

Route::get('imports/{import}', [ImportJobController::class, 'show'])
    ->middleware('can:import.view')
    ->name('imports.show');
