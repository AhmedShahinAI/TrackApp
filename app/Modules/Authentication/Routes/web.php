<?php

declare(strict_types=1);

use App\Modules\Authentication\Http\Controllers\LoginController;
use App\Modules\Authentication\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication — server-rendered screens
|--------------------------------------------------------------------------
|
| T127, T129. The platform authenticates with the session guard, so these are ordinary web routes
| with CSRF; the api/v1 pair exists for the same actions because the contract specifies both.
|
| Absent by design (FR-001): there is no /register, /password/forgot, /password/reset, or 2FA
| route here or anywhere. AbsentRoutesTest asserts each 404s.
|
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active', 'session.auth', 'password.own'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/profile/password', [PasswordController::class, 'edit'])->name('profile.password.edit');
    Route::put('/profile/password', [PasswordController::class, 'update'])->name('profile.password.update');
});
