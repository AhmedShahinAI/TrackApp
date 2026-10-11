<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers;

use App\Modules\Imports\Http\Controllers\Concerns\ServesBulkImport;
use App\Modules\Imports\Modes\UserMode;
use Illuminate\Routing\Controller;

/**
 * T347 — POST /imports/users, behind `import.user.run` (FR-104, FR-107).
 *
 * Every account the job commits is created unusable until a Super Admin resets its password
 * (FR-105) — see UserMode and Resolver::commitUser.
 */
final class UserImportController extends Controller
{
    use ServesBulkImport;

    protected function mode(): string
    {
        return UserMode::NAME;
    }

    protected function modeClass(): string
    {
        return UserMode::class;
    }
}
