<?php

declare(strict_types=1);

use App\Modules\Students\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'session.auth', 'password.own'])->group(function (): void {
    Route::get('/students', [StudentController::class, 'index'])
        ->middleware('can:student.view')
        ->name('students.index');

    Route::get('/students/{student}', [StudentController::class, 'show'])
        ->middleware('can:student.view')
        ->name('students.show');
});
