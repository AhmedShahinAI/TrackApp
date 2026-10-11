<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Section;
use App\Models\User;
use App\Modules\Imports\Actions\UploadImport;
use App\Modules\Settings\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T254 — an instructor's student list, loaded into a section they teach (FR-053, FR-054).
 *
 * ## Two gates, in this order
 *
 * The section arrives through the **scoped** route binding, so a section the caller cannot see is a
 * 404 before any code here runs — an instructor cannot learn that another instructor's section
 * exists, and AuditDeniedAccess records the attempt as `access.denied`. Then `can:importRoster`
 * (SectionPolicy) narrows "can see" to "may import into": a coordinator sees their branch's sections
 * but teaches none of them.
 *
 * After upload the job is an ordinary import in `section` mode, and the rest of the six steps are the
 * same endpoints and the same wizard as the central import (contracts/imports.md).
 */
final class SectionImportController extends Controller
{
    /** The two columns the section mode reads, as the template's header row (FR-053). */
    private const TEMPLATE_HEADERS = ['university_id', 'student_name'];

    /**
     * GET /sections/{section}/import — step 1, with the section fixed (T257).
     */
    public function create(Section $section): View
    {
        $section->loadMissing(['course', 'branch', 'term']);

        return view('sections.import', [
            'section' => $section,
            'maxFileMb' => Settings::int(Settings::IMPORT_MAX_FILE_MB),
            'maxRows' => Settings::int(Settings::IMPORT_MAX_ROWS),
        ]);
    }

    /**
     * POST /api/v1/sections/{section}/imports — the upload.
     */
    public function store(Request $request, Section $section, UploadImport $upload): JsonResponse|RedirectResponse
    {
        $request->validate([
            // FileValidator checks name, MIME and content, and names the accepted types itself.
            'file' => ['required', 'file'],
        ], [
            'file.required' => __('imports.upload.required'),
        ]);

        /*
         * The same rule the central upload's term_id validation applies: an archived or deleted term
         * takes no new roster. The section mode takes its term from the section rather than from the
         * request, so the check is made here instead of in a validation rule.
         */
        $term = AcademicTerm::query()->withTrashed()->find($section->term_id);

        if ($term === null || $term->trashed() || $term->status === 'archived') {
            throw new ConflictHttpException(__('imports.conflicts.term_archived'));
        }

        /** @var User $actor */
        $actor = $request->user();

        $job = $upload->execute($request->file('file'), $actor, null, $section);

        if (! $request->expectsJson()) {
            return redirect()->route('imports.show', $job);
        }

        return response()->json(['data' => [
            'id' => $job->id,
            'mode' => $job->mode,
            'section_id' => $job->section_id,
            'status' => $job->status,
            'original_filename' => $job->original_filename,
            'total_rows' => $job->total_rows,
            'duplicate_of' => $job->duplicateOf(),
            'suggested_mapping' => $job->file_metadata['suggested_mapping'] ?? [],
        ]], 201);
    }

    /**
     * GET /sections/{section}/import/template — the two-column template, headed in the reader's
     * language so the mapping step suggests both columns without a correction.
     *
     * xlsx rather than CSV: an Arabic header in a CSV opens as mojibake in Excel unless the file
     * carries a BOM, and the template exists precisely to be opened in Excel.
     */
    public function template(Section $section): StreamedResponse
    {
        $headers = array_map(static fn (string $target): string => __('imports.targets.'.$target), self::TEMPLATE_HEADERS);

        // The section number is free text up to 20 characters, and a slash or backslash in a download
        // name makes Symfony refuse the Content-Disposition header — a 500 instead of a template.
        $filename = 'section-'.preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $section->section_number).'-template.xlsx';

        return response()->streamDownload(function () use ($headers): void {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(new Row(array_map(static fn (string $h): StringCell => new StringCell($h), $headers)));
            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
