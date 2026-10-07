<?php

declare(strict_types=1);

namespace App\Modules\Students\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Students, read-only, as far as US2 needs them.
 *
 * A student is the hardest of the five scoped models to get right, because they have no branch and
 * no section of their own — they are visible only through an enrollment. Student::constrainToBranches()
 * carries that logic; this controller is deliberately ignorant of it.
 *
 * Per contracts/academic.md, a student enrolled in several in-scope sections appears **once**
 * (FR-043). That is a property of the `whereExists` in the model rather than a `distinct()` here,
 * which matters because `distinct()` would have to be remembered by every future caller.
 */
final class StudentController extends Controller
{
    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse
    {
        $students = Student::query()
            ->when($request->filled('university_id'), fn ($q) => $q->where(
                'university_id', 'like', $request->string('university_id')->toString().'%',
            ))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $q->where(fn ($w) => $w->where('name_ar', 'like', $term)->orWhere('name_en', 'like', $term));
            })
            ->orderBy('name_ar')
            ->paginate(max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE)))
            ->withQueryString();

        return response()->json([
            'data' => $students->getCollection()->map($this->present(...))->all(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    /**
     * 404 when the student exists but has no enrollment in the caller's scope — the binding does it,
     * so this method never has to know (FR-024).
     */
    public function show(Student $student): JsonResponse
    {
        $student->load(['enrollments.section.course', 'enrollments.section.branch']);

        return response()->json([
            'data' => $this->present($student) + [
                'enrollments' => $student->enrollments->map(fn ($enrollment) => [
                    'id' => $enrollment->getKey(),
                    'section_id' => $enrollment->section_id,
                    'section_number' => $enrollment->section?->section_number,
                    'course' => $enrollment->section?->course?->code,
                    'branch' => $enrollment->section?->branch?->campus_name_ar,
                    'status' => $enrollment->status,
                    'enrolled_at' => $enrollment->enrolled_at?->toIso8601String(),
                ])->all(),

                // The initiative history the contract promises, empty in this feature by design.
                'initiatives' => [],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Student $student): array
    {
        return [
            'id' => $student->getKey(),
            'university_id' => $student->university_id,
            'name_ar' => $student->name_ar,
            'name_en' => $student->name_en,
        ];
    }
}
