<?php

declare(strict_types=1);

use App\Modules\Authorization\Http\Controllers\SectionAccessGrantController;
use App\Modules\Authorization\Http\Controllers\UserBranchController;
use App\Modules\Authorization\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authorization — assignment and grants
|--------------------------------------------------------------------------
|
| T149, T151. Each route names its permission in `can:` middleware, which is what T053's
| EveryRouteAuthorizedTest requires: the authorization of the platform is readable from
| `route:list` alone, without opening a controller.
|
| These three are the write side of scope. Everything else in US2 is a read constrained by a global
| scope; these are the endpoints that change what a person's scope *is*, which is why all three are
| audited with their before and after sets.
|
*/

Route::put('users/{user}/roles', [UserRoleController::class, 'update'])
    ->middleware('can:user.assign_role')
    ->name('users.roles.update');

Route::put('users/{user}/branches', [UserBranchController::class, 'update'])
    ->middleware('can:user.assign_branch')
    ->name('users.branches.update');

Route::post('sections/{section}/access-grants', [SectionAccessGrantController::class, 'store'])
    ->middleware('can:section.access_override')
    ->name('sections.access-grants.store');
