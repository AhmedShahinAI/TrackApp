<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Modules\Support\Http\Controllers\SupportQueueController;
use App\Modules\Support\Http\Controllers\SupportResponseController;
use App\Modules\Support\Http\Controllers\SupportStateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T411–T417 — the five staff endpoints (contracts/support-staff.md), under /api/v1
|--------------------------------------------------------------------------
|
| `{supportRequest}` is the reference, never the id (SupportRequest::getRouteKeyName): the id is
| sequential and the reference is not. No DELETE on a request or a response, no PUT/PATCH on a response,
| no bulk action — each is "absent by design" in the contract.
|
*/

Route::prefix('support-requests')->name('support-requests.')->group(function (): void {
    // The queue's refusal is a 404 (SupportRequestPolicy::viewAny, SC-038).
    Route::get('/', [SupportQueueController::class, 'index'])
        ->middleware('can:viewAny,'.SupportRequest::class)
        ->name('index');

    Route::get('{supportRequest}', [SupportQueueController::class, 'show'])
        ->middleware('can:support.view')
        ->name('show');

    Route::post('{supportRequest}/responses', [SupportResponseController::class, 'store'])
        ->middleware('can:support.respond')
        ->name('responses.store');

    // `closed` additionally needs support.close, checked by TransitionSupportRequest (403).
    Route::patch('{supportRequest}/status', [SupportStateController::class, 'status'])
        ->middleware('can:support.respond')
        ->name('status');

    Route::patch('{supportRequest}/assignee', [SupportStateController::class, 'assignee'])
        ->middleware('can:support.assign')
        ->name('assignee');
});
