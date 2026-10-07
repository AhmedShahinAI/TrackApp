<?php

declare(strict_types=1);

use App\Modules\Sections\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sections — /api/v1/sections
|--------------------------------------------------------------------------
|
| The read surface US2 needs to prove its scope. `export` is declared before `{section}` because
| Laravel matches in declaration order, and the other way round "export" binds as a section id —
| which resolves to nothing, 404s, and looks exactly like a scope miss.
|
*/

Route::prefix('sections')->name('sections.')->group(function (): void {
    Route::get('/', [SectionController::class, 'index'])
        ->middleware('can:section.view')
        ->name('index');

    Route::get('export', [SectionController::class, 'export'])
        ->middleware('can:section.view')
        ->name('export');

    Route::get('{section}', [SectionController::class, 'show'])
        ->middleware('can:section.view')
        ->name('show');
});
