<?php

declare(strict_types=1);

use App\Modules\Authentication\Http\Controllers\LoginController;
use App\Modules\Authentication\Http\Controllers\MeController;
use App\Modules\Authentication\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication — /api/v1/auth
|--------------------------------------------------------------------------
|
| T116, T118, T119. Loaded by ModuleServiceProvider, which applies the api/v1 prefix, the
| api.v1. name prefix, and the web + auth:sanctum + throttle:api stack from bootstrap/app.php.
|
| `login` is the one route here that must be reachable unauthenticated, so it withdraws from
| auth:sanctum explicitly. It keeps its own throttle — 5 per minute per email AND IP, checked
| inside the controller before the credential check, which is tighter than the api limiter's 120.
|
| Absent by design (FR-001): /register, /password/forgot, /password/reset, and any 2FA path.
| Their absence is a requirement, so AbsentRoutesTest asserts each returns 404 rather than
| trusting that nobody adds them.
|
*/

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('login', [LoginController::class, 'store'])
        ->withoutMiddleware(['auth:sanctum'])
        ->name('login');

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('me', MeController::class)->name('me');

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});
