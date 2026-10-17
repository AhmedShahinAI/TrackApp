<?php

declare(strict_types=1);

use App\Modules\UserManagement\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accounts — server-rendered screens
|--------------------------------------------------------------------------
|
| T131, T132, T133. `active` sits beside `auth` on every one: a deactivated account must not
| finish the page it was on (FR-018), and the middleware is what makes that true on any session
| driver.
|
*/

Route::middleware(['auth', 'active', 'session.auth', 'password.own'])->group(function (): void {
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('can:user.view')
        ->name('users.index');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('can:user.create')
        ->name('users.store');

    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('can:user.edit')
        ->name('users.update');

    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])
        ->middleware('can:user.deactivate')
        ->name('users.deactivate');

    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('can:user.reset_password')
        ->name('users.reset-password');
});
