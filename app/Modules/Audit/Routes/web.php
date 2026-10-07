<?php

declare(strict_types=1);

use App\Modules\Audit\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Audit — the screen
|--------------------------------------------------------------------------
|
| The same controller and the same scoped, filtered query as /api/v1/audit-logs. One query behind
| both, so the screen cannot drift from the endpoint — which is the property FR-023 asks for and the
| one that quietly fails when a Blade route grows its own copy of the filters.
|
| Read-only. There is no POST, PUT, PATCH or DELETE here or anywhere else that touches the table
| (contracts/audit.md, "Absent by design"), and ImmutabilityTest fails the build if one appears.
|
*/

Route::middleware(['auth', 'active', 'session.auth'])->group(function (): void {
    Route::get('/audit', [AuditLogController::class, 'index'])
        ->middleware('can:audit.view')
        ->name('audit.index');
});
