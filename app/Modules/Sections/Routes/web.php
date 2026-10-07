<?php

declare(strict_types=1);

use App\Modules\Sections\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sections — screens
|--------------------------------------------------------------------------
|
| The same controller as the API: one scoped query behind both, so a Blade route cannot drift from
| the JSON one. T135 asserts the 404 through both paths precisely because they are easy to let
| diverge.
|
*/

Route::middleware(['auth', 'active', 'session.auth'])->group(function (): void {
    Route::get('/sections', [SectionController::class, 'index'])
        ->middleware('can:section.view')
        ->name('sections.index');

    Route::get('/sections/{section}', [SectionController::class, 'show'])
        ->middleware('can:section.view')
        ->name('sections.show');
});
