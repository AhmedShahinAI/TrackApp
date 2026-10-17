<?php

declare(strict_types=1);

use App\Modules\Sections\Http\Controllers\SectionApprovalController;
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

Route::middleware(['auth', 'active', 'session.auth', 'password.own'])->group(function (): void {
    Route::get('/sections', [SectionController::class, 'index'])
        ->middleware('can:section.view')
        ->name('sections.index');

    // T496 — the approval queue screen. Before `/sections/{section}`, which would bind "approvals".
    Route::get('/sections/approvals', [SectionApprovalController::class, 'index'])
        ->middleware('can:section.approve')
        ->name('sections.approvals');

    Route::get('/sections/{section}', [SectionController::class, 'show'])
        ->middleware('can:section.view')
        ->name('sections.show');
});
