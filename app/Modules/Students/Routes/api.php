<?php

declare(strict_types=1);

use App\Modules\Students\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('students')->name('students.')->group(function (): void {
    Route::get('/', [StudentController::class, 'index'])
        ->middleware('can:student.view')
        ->name('index');

    Route::get('{student}', [StudentController::class, 'show'])
        ->middleware('can:student.view')
        ->name('show');
});

/*
 * Enrolling hangs off the section, because that is what a person is enrolled *in*; withdrawing
 * hangs off the enrollment, because that is the thing being ended. Both carry their own permission.
 */
Route::post('sections/{section}/enrollments', [StudentController::class, 'enroll'])
    ->middleware('can:enrollment.create')
    ->name('sections.enrollments.store');

Route::patch('enrollments/{enrollment}/withdraw', [StudentController::class, 'withdraw'])
    ->middleware('can:enrollment.withdraw')
    ->name('enrollments.withdraw');
