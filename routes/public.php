<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| The public surface — every route a visitor reaches without signing in (T355)
|--------------------------------------------------------------------------
|
| Amendment 2 (student support channel) is the platform's first unauthenticated feature, and the
| constitution's "Public endpoints" subsection requires that surface to be small, reviewable and
| deliberate. So it lives here, in one file, rather than scattered across module route files where an
| unauthenticated route would look like any other:
|
|   - loaded on the `public` middleware group (bootstrap/app.php): `web` — CSRF through a guest
|     session cookie, the CSP, the locale — plus a per-IP throttle floor; never `auth:sanctum`;
|   - each route's controller lives in a module's Http/Controllers/Public, and each route is
|     documented in contracts/support-public.md with its own two-axis limits;
|   - anything that needs a signed-in user belongs in a module's Routes/web.php or Routes/api.php,
|     never here.
|
| tests/Architecture/PublicSurfaceTest.php holds the platform to this file: an unauthenticated route
| declared anywhere else fails it, as does a route here that resolves a user.
|
| A route added to this file is a route anyone on the internet can call. It is reviewed as one.
|
| ## User Story 9 (Phase 20, T394)
|
| Only the submission is padded to `support_min_response_ms` (ConstantResponseTime, research.md
| R-10), and only it carries the submission limits — **outside** the padding, so a refusal returns at
| once and a refused flood cannot hold the PHP workers (Phase 19 review).
|
| The status lookup (Phase 22) is padded too, under its own tighter limit. The GETs are neither
| (Phase 20 review). None of them takes an identifier, so there is no
| timing to hide, and padding them only parks a PHP worker in a sleep: at the group's 60 a minute per
| address, about 125 addresses each under their limit would fill a 50-worker pool with sleeping
| challenge fetches. They sit under the group's per-IP floor, which is all a page view needs — and the
| form and the challenge stay reachable to a student who has used their submissions.
|
*/

use App\Modules\Support\Http\Controllers\Public\ChallengeController;
use App\Modules\Support\Http\Controllers\Public\SupportRequestController;
use App\Modules\Support\Http\Controllers\Public\SupportStatusController;
use App\Modules\Support\Http\Middleware\ConstantResponseTime;
use App\Modules\Support\Http\Middleware\SupportStatusThrottle;
use App\Modules\Support\Http\Middleware\SupportSubmissionThrottle;
use Illuminate\Support\Facades\Route;

Route::prefix('support')->name('support.')->group(function (): void {
    Route::get('/', [SupportRequestController::class, 'create'])->name('create');
    Route::get('/challenge', ChallengeController::class)->name('challenge');
    Route::get('/submitted', [SupportRequestController::class, 'submitted'])->name('submitted');

    Route::post('/', [SupportRequestController::class, 'store'])
        ->middleware([SupportSubmissionThrottle::class, ConstantResponseTime::class])
        ->name('store');

    // Phase 22 (US11, T430). The form is a page view; the lookup takes an identifier, so it is padded,
    // under its own tighter per-address limit, outside the padding. Never audited (see the controller).
    Route::get('/status', [SupportStatusController::class, 'create'])->name('status.form');

    Route::post('/status', [SupportStatusController::class, 'lookup'])
        ->middleware([SupportStatusThrottle::class, ConstantResponseTime::class])
        ->name('status.lookup');
});
