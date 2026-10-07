<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers;

use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * One import job, read-only.
 *
 * The whole import pipeline — upload, mapping, validation, preview, commit, resume — is US5. This
 * endpoint exists now because T135 requires an out-of-scope import job to answer 404, and an
 * assertion about an endpoint that does not exist proves nothing.
 */
final class ImportJobController extends Controller
{
    public function show(ImportJob $import): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $import->getKey(),
                'mode' => $import->mode,
                'section_id' => $import->section_id,
                'original_filename' => $import->original_filename,
                'status' => $import->status,
                'total_rows' => (int) $import->total_rows,
                'imported' => (int) $import->imported,
                'errors' => (int) $import->errors,
                'created_at' => $import->created_at?->toIso8601String(),
            ],
        ]);
    }
}
