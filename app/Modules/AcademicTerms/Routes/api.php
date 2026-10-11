<?php

declare(strict_types=1);

use App\Modules\AcademicTerms\Http\Controllers\TermController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Academic terms — /api/v1/terms
|--------------------------------------------------------------------------
|
| Status change and set-current are their own endpoints with their own permissions, not fields on
| the edit. Folding them in would make "may correct a typo in a term name" and "may close the term"
| the same authority, and the audit log would lose the distinction too.
|
*/

Route::prefix('terms')->name('terms.')->group(function (): void {
    Route::get('/', [TermController::class, 'index'])
        ->middleware('can:term.view')
        ->name('index');

    Route::post('/', [TermController::class, 'store'])
        ->middleware('can:term.create')
        ->name('store');

    Route::put('{term}', [TermController::class, 'update'])
        ->middleware('can:term.edit')
        ->name('update');

    Route::patch('{term}/status', [TermController::class, 'changeStatus'])
        ->middleware('can:term.change_status')
        ->name('status');

    Route::patch('{term}/set-current', [TermController::class, 'setCurrent'])
        ->middleware('can:term.set_current')
        ->name('set-current');
});
