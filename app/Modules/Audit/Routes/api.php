<?php

declare(strict_types=1);

use App\Modules\Audit\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
| `export` before `{id}` — see the note in the sections route file for why the order is not
| cosmetic. There is no `{id}` route here at all: a single audit entry has no screen and no
| endpoint, because an entry is only meaningful in sequence.
*/

Route::prefix('audit-logs')->name('audit-logs.')->group(function (): void {
    Route::get('/', [AuditLogController::class, 'index'])
        ->middleware('can:audit.view')
        ->name('index');

    Route::get('export', [AuditLogController::class, 'export'])
        ->middleware('can:audit.export')
        ->name('export');
});
