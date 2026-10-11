<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers;

use App\Models\ImportJob;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Imports\Exports\ImportErrorReportExport;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * T236 — GET /imports/{id}/errors/download (FR-071).
 *
 * 404 when there is nothing to report, as the contract says: an empty spreadsheet would read as "the
 * report failed to generate" to the person opening it. The audit entry is written before the stream
 * starts, because a streamed body runs after the controller has returned and an entry recorded there
 * would happen outside the request's own error handling.
 */
final class ImportErrorReportController extends Controller
{
    public function download(ImportJob $import): StreamedResponse
    {
        if (! ImportErrorReportExport::hasErrors($import)) {
            throw new NotFoundHttpException;
        }

        app(Auditor::class)->record('import.error_report_downloaded', $import, [], [
            'file' => $import->original_filename,
            'mode' => $import->mode,
            'errors' => $import->errors,
        ]);

        return (new ImportErrorReportExport($import))->toResponse();
    }
}
