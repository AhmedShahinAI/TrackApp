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

    Route::post('/', [SectionController::class, 'store'])
        ->middleware('can:section.create')
        ->name('store');

    Route::put('{section}', [SectionController::class, 'update'])
        ->middleware('can:section.edit')
        ->name('update');

    /*
     * Its own endpoint and its own permission. Reassigning a section moves who may open it from now
     * on; it is not an edit to a field, and conflating the two would make "may fix a typo in a
     * section number" and "may hand a section to someone else" the same authority.
     */
    Route::patch('{section}/instructor', [SectionController::class, 'changeInstructor'])
        ->middleware('can:section.change_instructor')
        ->name('instructor');
});
