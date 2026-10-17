<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Modules\Support\Http\Controllers\SupportQueueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T418 + T419 — the staff screens of the support channel (Amendment 2, US10)
|--------------------------------------------------------------------------
|
| Signed in, active, the queue's permission. The public half of this module is in routes/public.php
| and nowhere else (PublicSurfaceTest). Bound by reference, through the branch scope: a request outside
| the caller's sight is a 404.
|
*/

// throttle:api, the per-user limit the /api/v1 stack already carries (Phase 23 review): every refused
// address writes an access.denied entry, and an unthrottled screen let one account flood a five-year log.
Route::middleware(['auth', 'active', 'session.auth', 'password.own', 'throttle:api'])->group(function (): void {
    // The queue's refusal is a 404 (SupportRequestPolicy::viewAny, SC-038).
    Route::get('/support-requests', [SupportQueueController::class, 'index'])
        ->middleware('can:viewAny,'.SupportRequest::class)
        ->name('support-requests.index');

    Route::get('/support-requests/{supportRequest}', [SupportQueueController::class, 'show'])
        ->middleware('can:support.view')
        ->name('support-requests.show');
});
