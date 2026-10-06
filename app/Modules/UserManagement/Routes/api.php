<?php

declare(strict_types=1);

use App\Modules\UserManagement\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accounts — /api/v1/users
|--------------------------------------------------------------------------
|
| T122, T123, T124. Every route carries a `can:` middleware naming its permission, which is what
| EveryRouteAuthorizedTest (T053) requires and what makes the authorization readable from the
| route table rather than only from the controller.
|
| There is no DELETE. Removal is deactivation (constitution V, FR-049), and NoHardDeleteTest
| fails the build if a DELETE route for `users` ever appears.
|
*/

Route::prefix('users')->name('users.')->group(function (): void {
    Route::get('/', [UserController::class, 'index'])
        ->middleware('can:user.view')
        ->name('index');

    Route::post('/', [UserController::class, 'store'])
        ->middleware('can:user.create')
        ->name('store');

    Route::put('{user}', [UserController::class, 'update'])
        ->middleware('can:user.edit')
        ->name('update');

    Route::patch('{user}/deactivate', [UserController::class, 'deactivate'])
        ->middleware('can:user.deactivate')
        ->name('deactivate');

    Route::post('{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('can:user.reset_password')
        ->name('reset-password');
});
