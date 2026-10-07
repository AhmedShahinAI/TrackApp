<?php

declare(strict_types=1);

use App\Modules\Audit\Http\Controllers\AuditLogController;
use App\Modules\Audit\Http\Controllers\EntityActivityController;
use Illuminate\Support\Facades\Route;

/*
| `export` before `{id}` — see the note in the sections route file for why the order is not
| cosmetic. There is no `{id}` route here at all: a single audit entry has no screen and no
| endpoint, because an entry is only meaningful in sequence.
*/

/*
 * T166 — one entity's history. `{entity}` is a word, not a bound model, so the controller resolves
 * it and produces the 404s itself; `audit.view` is checked there too, alongside the entity's own
 * permission, because which permission applies depends on which word arrived.
 *
 * The `can:` middleware names audit.view so the route table still shows an authorization gate —
 * EveryRouteAuthorizedTest requires one, and a route whose only check is in a controller body is a
 * route whose authorization nobody can audit from the outside.
 */
Route::get('{entity}/{id}/activity', [EntityActivityController::class, 'index'])
    ->whereIn('entity', ['terms', 'branches', 'sections', 'students'])
    ->whereNumber('id')
    ->middleware('can:audit.view')
    ->name('entity-activity');

Route::prefix('audit-logs')->name('audit-logs.')->group(function (): void {
    Route::get('/', [AuditLogController::class, 'index'])
        ->middleware('can:audit.view')
        ->name('index');

    Route::get('export', [AuditLogController::class, 'export'])
        ->middleware('can:audit.export')
        ->name('export');
});
