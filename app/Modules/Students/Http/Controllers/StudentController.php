<?php

declare(strict_types=1);

namespace App\Modules\Students\Http\Controllers;

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use App\Modules\Shared\Rules\UniversityId;
use App\Modules\Students\Actions\EnrollStudent;
use App\Modules\Students\Actions\WithdrawEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

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
    use RespondsToBoth;

    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse|View
    {
        $students = Student::query()
            ->when($request->filled('university_id'), fn ($q) => $q->where(
                'university_id', 'like', $request->string('university_id')->toString().'%',
            ))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $q->where(fn ($w) => $w->where('name_ar', 'like', $term)->orWhere('name_en', 'like', $term));
            })
            /*
             * The enrollment count is scoped the same way the list is. Student::enrollments() runs
             * through SectionEnrollment, which carries the section scope, so a coordinator sees the
             * number of enrollments *they can see* — not the student's university-wide total. A
             * count that disagreed with the rows on the detail page would read as a bug and would
             * also leak the existence of sections outside the caller's scope.
             */
            ->withCount(['enrollments' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name_ar')
            ->paginate(max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE)))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $students->getCollection()->map($this->present(...))->all(),
                'meta' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'total' => $students->total(),
                ],
            ]);
        }

        return view('students.index', ['students' => $students]);
    }

    /**
     * 404 when the student exists but has no enrollment in the caller's scope — the binding does it,
     * so this method never has to know (FR-024).
     */
    public function show(Request $request, Student $student): JsonResponse|View
    {
        $student->load(['enrollments.section.course', 'enrollments.section.branch']);

        if (! $request->expectsJson()) {
            return view('students.show', ['student' => $student]);
        }

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
     * T193 — POST /sections/{section}/enrollments.
     */
    public function enroll(Request $request, Section $section): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'university_id' => ['required', new UniversityId],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $result = app(EnrollStudent::class)->execute($section, $validated);

        return $this->respond($request, [
            'data' => $this->present($result['student']) + [
                'enrollment_id' => $result['enrollment']->getKey(),
                'status' => $result['enrollment']->status,
            ],

            // Reported, not applied: a stored name that disagrees with the typed one is the
            // registrar's to resolve, not this endpoint's to overwrite.
            'warnings' => $result['warnings'],
        ], 201, [
            // The browser half of the same report. Without it the enroll dialog's warning alert is
            // markup that nothing ever populates, and a name mismatch is silently swallowed.
            'enroll_warnings' => $result['warnings'],
        ]);
    }

    /**
     * T194 — PATCH /enrollments/{enrollment}/withdraw. The reason is mandatory (FR-047).
     */
    public function withdraw(Request $request, SectionEnrollment $enrollment): JsonResponse|RedirectResponse
    {
        /*
         * Trimmed before `min:3`, so "   " is a 422 naming the field rather than a 409 from the
         * action. Both refuse it, but only one tells the registrar what to do about it, and a
         * reason of three spaces is a missing reason, not a conflicting state.
         */
        $request->merge(['reason' => trim((string) $request->input('reason'))]);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => __('students.errors.reason_required'),
        ]);

        $updated = app(WithdrawEnrollment::class)->execute($enrollment, $validated['reason']);

        return $this->respond($request, [
            'data' => [
                'id' => $updated->getKey(),
                'status' => $updated->status,
                'withdrawn_at' => $updated->withdrawn_at?->toIso8601String(),
                'withdraw_reason' => $updated->withdraw_reason,
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
