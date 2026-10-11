<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Modules\Imports\Http\Controllers\CourseImportController;
use App\Modules\Imports\Http\Controllers\ImportErrorReportController;
use App\Modules\Imports\Http\Controllers\ImportJobController;
use App\Modules\Imports\Http\Controllers\ImportMappingController;
use App\Modules\Imports\Http\Controllers\RegistrarImportController;
use App\Modules\Imports\Http\Controllers\SectionImportController;
use App\Modules\Imports\Http\Controllers\UserImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Imports — the six-step wizard (contracts/imports.md)
|--------------------------------------------------------------------------
|
| Reading a job is `import.view`, and which jobs exist for the caller is the global scope's answer
| (a foreign job is a 404 from the binding). Every step that moves a job forward is `run`, which
| ImportJobPolicy defines as the mode's run permission plus ownership.
|
| The section-mode upload, POST /sections/{id}/imports (US6, T254), is registered at the end.
|
*/

Route::prefix('imports')->name('imports.')->group(function (): void {
    Route::get('/', [ImportJobController::class, 'index'])
        ->middleware('can:viewAny,'.ImportJob::class)
        ->name('index');

    Route::post('/', [ImportJobController::class, 'store'])
        ->middleware('can:create,'.ImportJob::class)
        ->name('store');

    // T331 — the registration unit's export (FR-096). Before {import}, so "registrar" is never
    // read as an import id.
    Route::post('registrar', [RegistrarImportController::class, 'store'])
        ->middleware('can:import.registrar.run')
        ->name('registrar.store');

    // T345, T347 — the course catalogue and staff accounts (FR-103, FR-104, FR-107).
    Route::post('courses', [CourseImportController::class, 'store'])
        ->middleware('can:import.course.run')
        ->name('courses.store');

    Route::post('users', [UserImportController::class, 'store'])
        ->middleware('can:import.user.run')
        ->name('users.store');

    Route::get('{import}', [ImportJobController::class, 'show'])
        ->middleware('can:import.view')
        ->name('show');

    Route::put('{import}/mapping', [ImportMappingController::class, 'update'])
        ->middleware('can:run,import')
        ->name('mapping.update');

    Route::post('{import}/validate', [ImportJobController::class, 'startValidation'])
        ->middleware('can:run,import')
        ->name('validate');

    Route::get('{import}/preview', [ImportJobController::class, 'preview'])
        ->middleware('can:run,import')
        ->name('preview');

    Route::post('{import}/commit', [ImportJobController::class, 'commit'])
        ->middleware('can:run,import')
        ->name('commit');

    Route::post('{import}/resume', [ImportJobController::class, 'resume'])
        ->middleware('can:run,import')
        ->name('resume');

    Route::get('{import}/errors/download', [ImportErrorReportController::class, 'download'])
        ->middleware('can:downloadErrors,import')
        ->name('errors.download');
});

/*
 * T254 — the instructor's upload into one section. The scoped binding makes a foreign section a 404
 * (audited as access.denied); importRoster allows only a section the caller teaches or holds a grant
 * for. From here on the job uses the routes above like any other.
 */
Route::post('sections/{section}/imports', [SectionImportController::class, 'store'])
    ->middleware('can:importRoster,section')
    ->name('sections.imports.store');
