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
