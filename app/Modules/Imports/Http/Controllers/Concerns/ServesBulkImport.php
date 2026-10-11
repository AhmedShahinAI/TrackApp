<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers\Concerns;

use App\Models\ImportJob;
use App\Models\User;
use App\Modules\Imports\Actions\UploadImport;
use App\Modules\Settings\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * US8 — what the course and staff uploads share: step 1's page, the upload, and the template.
 *
 * The two modes differ in their column contract (the mode class) and their permission (the route's
 * `can:`), and nothing else here; after upload each job is an ordinary import through the same five
 * remaining steps and the same guarantees (FR-106). A using controller names its mode and its
 * template file.
 */
trait ServesBulkImport
{
    /** The mode's NAME, e.g. `course`. */
    abstract protected function mode(): string;

    /** @return class-string the mode class, for its TARGETS */
    abstract protected function modeClass(): string;

    /**
     * Step 1 — the upload zone and the template, with no term: these files are the college's.
     */
    public function create(): View
    {
        return view('imports.show', [
            'job' => null,
            'step' => 1,
            'mode' => $this->mode(),
            'maxFileMb' => Settings::int(Settings::IMPORT_MAX_FILE_MB),
            'maxRows' => Settings::int(Settings::IMPORT_MAX_ROWS),
        ]);
    }

    public function store(Request $request, UploadImport $upload): JsonResponse|RedirectResponse
    {
        $request->validate([
            // FileValidator checks name, MIME and content, and names the accepted types itself.
            'file' => ['required', 'file'],
        ], [
            'file.required' => __('imports.upload.required'),
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $job = $upload->execute($request->file('file'), $actor, null, null, ['mode' => $this->mode()]);

        if (! $request->expectsJson()) {
            return redirect()->route('imports.show', $job);
        }

        return response()->json(['data' => self::presentUpload($job)], 201);
    }

    /**
     * The template, headed in the reader's language so the mapping step suggests every column
     * without a correction. xlsx rather than CSV: an Arabic header in a CSV opens as mojibake in
     * Excel unless the file carries a BOM.
     */
    public function template(): StreamedResponse
    {
        $targets = array_keys(($this->modeClass())::TARGETS);
        $headers = array_map(static fn (string $target): string => __('imports.targets.'.$target), $targets);

        return response()->streamDownload(function () use ($headers): void {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(new Row(array_map(static fn (string $h): StringCell => new StringCell($h), $headers)));
            $writer->close();
        }, 'track-'.$this->mode().'-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * contracts/imports.md: "as the common shape".
     *
     * @return array<string, mixed>
     */
    private static function presentUpload(ImportJob $job): array
    {
        return [
            'id' => $job->id,
            'mode' => $job->mode,
            'status' => $job->status,
            'original_filename' => $job->original_filename,
            'total_rows' => $job->total_rows,
            'duplicate_of' => $job->duplicateOf(),
            'suggested_mapping' => $job->file_metadata['suggested_mapping'] ?? [],
        ];
    }
}
