<?php

declare(strict_types=1);

use App\Modules\AcademicStructure\Http\Controllers\BranchController;
use App\Modules\AcademicStructure\Http\Controllers\CourseController;
use App\Modules\AcademicStructure\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Branches, courses and programmes — /api/v1
|--------------------------------------------------------------------------
|
| No DELETE on any of them, and that is a requirement rather than an omission: a branch holds sections
| which hold the enrollment history the platform exists to keep, so `is_active = false` is the only
| removal path (FR-049). NoHardDeleteTest fails the build if one appears.
|
*/

Route::prefix('branches')->name('branches.')->group(function (): void {
    Route::get('/', [BranchController::class, 'index'])
        ->middleware('can:branch.view')
        ->name('index');

    Route::post('/', [BranchController::class, 'store'])
        ->middleware('can:branch.create')
        ->name('store');

    Route::put('{branch}', [BranchController::class, 'update'])
        ->middleware('can:branch.edit')
        ->name('update');
});

Route::prefix('courses')->name('courses.')->group(function (): void {
    Route::get('/', [CourseController::class, 'index'])
        ->middleware('can:course.view')
        ->name('index');

    Route::post('/', [CourseController::class, 'store'])
        ->middleware('can:course.create')
        ->name('store');
});

/*
 * T314 — programmes. No DELETE (FR-092): a programme holding sections keeps them, and deactivation
 * through PUT is the only removal path. NoHardDeleteTest and CannotDeleteProgramWithSectionsTest
 * both fail if one appears.
 */
Route::prefix('programs')->name('programs.')->group(function (): void {
    Route::get('/', [ProgramController::class, 'index'])
        ->middleware('can:program.view')
        ->name('index');

    Route::post('/', [ProgramController::class, 'store'])
        ->middleware('can:program.create')
        ->name('store');

    Route::get('{program}', [ProgramController::class, 'show'])
        ->middleware('can:program.view')
        ->name('show');

    Route::put('{program}', [ProgramController::class, 'update'])
        ->middleware('can:program.edit')
        ->name('update');

    Route::put('{program}/courses', [ProgramController::class, 'updateCourses'])
        ->middleware('can:program.edit')
        ->name('courses.update');
});
