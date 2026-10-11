<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers;

use App\Modules\Imports\Http\Controllers\Concerns\ServesBulkImport;
use App\Modules\Imports\Modes\CourseMode;
use Illuminate\Routing\Controller;

/**
 * T345 — POST /imports/courses, behind `import.course.run` (FR-103, FR-107).
 *
 * Only a holder of the course-import permission reaches it; an instructor holds none of the bulk
 * permissions, so they are refused and the attempt recorded (`access.denied`, spec scenario 6).
 */
final class CourseImportController extends Controller
{
    use ServesBulkImport;

    protected function mode(): string
    {
        return CourseMode::NAME;
    }

    protected function modeClass(): string
    {
        return CourseMode::class;
    }
}
