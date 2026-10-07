<?php

declare(strict_types=1);

namespace App\Modules\Sections\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The read surface for sections, as far as US2 needs it.
 *
 * ## Why this exists in the scope phase
 *
 * US2's Independent Test is "every access path shows only their own branch, and the other branch's
 * records return 404 rather than 403". A scope with no endpoint to read through is a scope nobody
 * has tested. T135 names the four paths explicitly — API route, Blade route, list, export — so all
 * four live here, built to contracts/academic.md so US4 extends them rather than replacing them.
 *
 * What is **not** here: create, update, the instructor reassignment, the approval workflow. Those
 * are US4 and Amendment 4, and writing them now would be scope creep dressed as momentum.
 *
 * ## There is not one `where` in this file
 *
 * That is the point of the global scope, and the reason this class can be read in a minute. The
 * branch and section constraints are applied by BranchScope and SectionScope on every query below,
 * including the count behind `meta.total` and the rows behind the export stream.
 */
final class SectionController extends Controller
{
    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse|View
    {
        $sections = $this->filtered($request)
            ->with(['term', 'branch', 'course', 'program', 'instructor'])
            ->withCount(['enrollments as active_enrollments' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('section_number')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $sections->getCollection()->map($this->present(...))->all(),
                'meta' => [
                    'current_page' => $sections->currentPage(),
                    'last_page' => $sections->lastPage(),
                    'total' => $sections->total(),
                ],
            ]);
        }

        return view('sections.index', ['sections' => $sections]);
    }

    public function show(Request $request, Section $section): JsonResponse|View
    {
        $section->load(['term', 'branch', 'course', 'program', 'instructor']);

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->present($section)]);
        }

        return view('sections.show', ['section' => $section]);
    }

    /**
     * FR-023 — the export is the same query as the list.
     *
     * Streamed rather than assembled in memory: a Super Admin's export is every section in the
     * college, and `chunk()` over a stream keeps the memory cost flat whether that is forty rows or
     * forty thousand.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with(['branch', 'course', 'instructor']);

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            // BOM first: Excel reads a UTF-8 CSV as Windows-1252 without it, and every Arabic name
            // in the file arrives as mojibake.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['section_number', 'course', 'branch', 'instructor', 'status']);

            $query->orderBy('id')->chunk(500, function ($chunk) use ($handle): void {
                foreach ($chunk as $section) {
                    fputcsv($handle, [
                        $section->section_number,
                        $section->course?->code,
                        $section->branch?->campus_name_ar,
                        $section->instructor?->name_ar,
                        $section->status,
                    ]);
                }
            });

            fclose($handle);
        }, 'sections.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Section>
     */
    private function filtered(Request $request)
    {
        return Section::query()
            ->when($request->filled('term_id'), fn ($q) => $q->where('term_id', $request->integer('term_id')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->when($request->filled('instructor_id'), fn ($q) => $q->where('instructor_id', $request->integer('instructor_id')))
            ->when($request->filled('q'), fn ($q) => $q->where('section_number', 'like', '%'.$request->string('q')->toString().'%'));
    }

    /**
     * Clamped. An unbounded `per_page` is a full-table load one query string away, and a negative
     * one reaches the database as `LIMIT -1`.
     */
    private function perPage(Request $request): int
    {
        return max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Section $section): array
    {
        return [
            'id' => $section->getKey(),
            'section_number' => $section->section_number,
            'term' => $section->term?->only(['id', 'code']),
            'branch' => $section->branch === null ? null : [
                'id' => $section->branch->id,
                'campus_name_ar' => $section->branch->campus_name_ar,
                'campus_name_en' => $section->branch->campus_name_en,

                // FR-050: a section's gender is its branch's, never its own column.
                'gender' => $section->branch->gender,
            ],
            'course' => $section->course?->only(['id', 'code', 'name_ar']),
            'program' => $section->program?->only(['id', 'name_ar']),
            'degree' => $section->degree,
            'activity' => $section->activity,
            'instructor' => $section->instructor === null ? null : [
                'id' => $section->instructor->id,
                'name_ar' => $section->instructor->name_ar,
                'name_en' => $section->instructor->name_en,
            ],
            'active_enrollments' => (int) ($section->active_enrollments ?? 0),
            'status' => $section->status,

            // FR-026 — per caller, not per row. See Section::sharedAccess().
            'shared_access' => $section->shared_access,
        ];
    }
}
