<?php

declare(strict_types=1);

use App\Modules\AcademicTerms\Http\Controllers\TermController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'session.auth', 'password.own'])->group(function (): void {
    Route::get('/terms', [TermController::class, 'index'])
        ->middleware('can:term.view')
        ->name('terms.index');
});
