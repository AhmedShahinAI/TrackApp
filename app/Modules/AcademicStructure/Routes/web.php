<?php

declare(strict_types=1);

use App\Modules\AcademicStructure\Http\Controllers\BranchController;
use App\Modules\AcademicStructure\Http\Controllers\CourseController;
use App\Modules\AcademicStructure\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'session.auth'])->group(function (): void {
    Route::get('/branches', [BranchController::class, 'index'])
        ->middleware('can:branch.view')
        ->name('branches.index');

    Route::get('/courses', [CourseController::class, 'index'])
        ->middleware('can:course.view')
        ->name('courses.index');

    Route::get('/programs', [ProgramController::class, 'index'])
        ->middleware('can:program.view')
        ->name('programs.index');
});
