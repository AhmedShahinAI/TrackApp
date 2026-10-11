<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Modules\Imports\Http\Controllers\CourseImportController;
use App\Modules\Imports\Http\Controllers\ImportJobController;
use App\Modules\Imports\Http\Controllers\RegistrarImportController;
use App\Modules\Imports\Http\Controllers\SectionImportController;
use App\Modules\Imports\Http\Controllers\UserImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Imports — screens
|--------------------------------------------------------------------------
|
| Three pages: the list, a new central import, and one job's wizard — plus, for US6, a section's
| own upload page. Every action on them posts to the api.v1.* routes, which answer a browser with a
| redirect back here.
|
| `/imports/new` is declared before `/imports/{import}` so "new" is never read as a job id.
|
*/

Route::middleware(['auth', 'active', 'session.auth'])->group(function (): void {
    Route::get('/imports', [ImportJobController::class, 'index'])
        ->middleware('can:viewAny,'.ImportJob::class)
        ->name('imports.index');

    Route::get('/imports/new', [ImportJobController::class, 'create'])
        ->middleware('can:create,'.ImportJob::class)
        ->name('imports.create');

    // T334 — step 1 of a registrar import. Before /imports/{import} for the same reason as above.
    Route::get('/imports/new/registrar', [RegistrarImportController::class, 'create'])
        ->middleware('can:import.registrar.run')
        ->name('imports.create.registrar');

    // T348 — the course and staff uploads' first step and templates.
    Route::get('/imports/new/courses', [CourseImportController::class, 'create'])
        ->middleware('can:import.course.run')
        ->name('imports.create.courses');

    Route::get('/imports/new/courses/template', [CourseImportController::class, 'template'])
        ->middleware('can:import.course.run')
        ->name('imports.courses.template');

    Route::get('/imports/new/users', [UserImportController::class, 'create'])
        ->middleware('can:import.user.run')
        ->name('imports.create.users');

    Route::get('/imports/new/users/template', [UserImportController::class, 'template'])
        ->middleware('can:import.user.run')
        ->name('imports.users.template');

    Route::get('/imports/{import}', [ImportJobController::class, 'show'])
        ->middleware('can:import.view')
        ->name('imports.show');

    // US6 — a section's own import page and its template. The section is bound through its scope, so
    // a foreign one is a 404; importRoster then narrows "can see" to "teaches or holds a grant".
    Route::get('/sections/{section}/import', [SectionImportController::class, 'create'])
        ->middleware('can:importRoster,section')
        ->name('sections.import');

    Route::get('/sections/{section}/import/template', [SectionImportController::class, 'template'])
        ->middleware('can:importRoster,section')
        ->name('sections.import.template');
});
