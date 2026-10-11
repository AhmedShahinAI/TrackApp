<?php

declare(strict_types=1);

namespace App\Modules\AcademicStructure\Http\Controllers;

use App\Models\Course;
use App\Models\Organization;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * T188 — courses, per contracts/academic.md.
 *
 * ## The duplicate refusal names the conflict
 *
 * `(organization_id, code)` is unique in the schema, and the form request checks the same thing so
 * the caller gets a field-level message instead of a 500 from the engine. The index is the
 * authority and the rule is the courtesy — the reverse, trusting the rule alone, loses to any race
 * between two registrars adding MATH101 at the same moment.
 */
final class CourseController extends Controller
{
    use RespondsToBoth;

    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse|View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $courses = Course::query()
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';

                // Code and both names: a registrar looking for a course knows one of the three.
                $q->where(fn ($w) => $w
                    ->where('code', 'like', $term)
                    ->orWhere('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term));
            })
            ->withCount('sections')
            ->orderBy('code')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $courses->getCollection()->map($this->present(...))->all(),
                'meta' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'total' => $courses->total(),
                ],
            ]);
        }

        return view('courses.index', ['courses' => $courses]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $organizationId = $this->organizationId();

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:20',

                // Scoped to the college, exactly like the index: a second institution added later
                // may use the same codes, and a global unique would refuse them for no reason.
                Rule::unique('courses', 'code')->where('organization_id', $organizationId),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ], [
            'code.unique' => __('courses.errors.code_taken'),
        ]);

        $course = Course::query()->create($validated + ['organization_id' => $organizationId]);

        return $this->respond($request, ['data' => $this->present($course->loadCount('sections'))], 201);
    }

    /**
     * The acting user's organization, not "the first college in the table".
     *
     * This read `Organization::where('type', 'college')->value('id')`, which is correct only while
     * exactly one organization exists. The moment a second is onboarded, every branch and course
     * created by anyone lands in whichever row the database returned first — silently, and in the
     * wrong tenant.
     *
     * The user carries `organization_id` already, so there is no reason to guess.
     */
    private function organizationId(): int
    {
        return (int) request()->user()?->organization_id;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Course $course): array
    {
        return [
            'id' => $course->getKey(),
            'code' => $course->code,
            'name_ar' => $course->name_ar,
            'name_en' => $course->name_en,
            'sections_count' => (int) ($course->sections_count ?? 0),
        ];
    }
}
