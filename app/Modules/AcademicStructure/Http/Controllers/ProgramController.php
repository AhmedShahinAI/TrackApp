<?php

declare(strict_types=1);

namespace App\Modules\AcademicStructure\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * T314 — academic programmes, per contracts/academic.md (FR-089 to FR-095).
 *
 * ## Deactivation is the only removal
 *
 * There is no DELETE route and no delete permission. A programme holds sections through a
 * restricting foreign key, and every section must keep the programme it was reported under (FR-090,
 * FR-092). `is_active = false` takes it out of the pickers and leaves every section that names it
 * readable; the dialog says so where someone would otherwise look for a delete button.
 *
 * ## The organization boundary
 *
 * Program carries no branch scope (T315), so the tenant boundary is drawn here: every read and write
 * is limited to the caller's organization, and another organization's programme is a 404, never a
 * 403 — a 403 would confirm the id exists.
 *
 * ## The code
 *
 * Program::code stores it trimmed and uppercase, and the database refuses anything else. The same
 * normalisation runs before validation, so the duplicate check compares what will actually be
 * stored: `hr` is refused as a duplicate of `HR`, not accepted and then rejected by the unique key
 * as a 500. The check includes soft-deleted programmes, as the key does (FR-089).
 */
final class ProgramController extends Controller
{
    use RespondsToBoth;

    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse|View
    {
        $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'unit' => ['nullable', Rule::in(['admin', 'tech'])],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $programs = $this->owned($request)
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('unit'), fn ($q) => $q->where('unit', $request->string('unit')->toString()))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';

                $q->where(fn ($w) => $w
                    ->where('code', 'like', $term)
                    ->orWhere('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term));
            })
            // Two COUNT subqueries, not two relations loaded: a programme like ISLM101's holds
            // every section of a general-requirement course, and only the number is shown.
            ->withCount(['courses', 'sections'])
            ->orderBy('code')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $programs->getCollection()->map($this->present(...))->all(),
                'meta' => [
                    'current_page' => $programs->currentPage(),
                    'last_page' => $programs->lastPage(),
                    'total' => $programs->total(),
                ],
            ]);
        }

        return view('programs.index', ['programs' => $programs]);
    }

    public function show(Request $request, Program $program): JsonResponse
    {
        $this->ensureOwned($request, $program);

        $program->load(['courses' => fn ($q) => $q->orderBy('code')])->loadCount(['courses', 'sections']);

        return response()->json(['data' => $this->present($program) + [
            'courses' => $program->courses->map(fn (Course $course): array => $course->only(['id', 'code', 'name_ar', 'name_en']))->all(),
        ]]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->normaliseCode($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', $this->uniqueCode($request)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'unit' => ['required', Rule::in(['admin', 'tech'])],
            'is_active' => ['sometimes', 'boolean'],
            'course_ids' => ['sometimes', 'array'],
            'course_ids.*' => ['integer', 'distinct', $this->ownCourse($request)],
        ], $this->messages($request));

        $program = $this->refusingDuplicateCode($request, fn (): Program => DB::transaction(function () use ($request, $validated): Program {
            $program = Program::query()->create([
                'organization_id' => $request->user()->organization_id,
                'code' => $validated['code'],
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'],
                'unit' => $validated['unit'],
                'is_active' => array_key_exists('is_active', $validated) ? $request->boolean('is_active') : true,
            ]);

            // Part of creating it, so not a separate `program.courses_changed`: the created entry
            // is the one event a reader looks for, and nothing changed from a previous set.
            if (($validated['course_ids'] ?? []) !== []) {
                $program->courses()->sync($validated['course_ids']);
            }

            return $program;
        }));

        return $this->respond($request, ['data' => $this->present($program->loadCount(['courses', 'sections']))], 201);
    }

    public function update(Request $request, Program $program): JsonResponse|RedirectResponse
    {
        $this->ensureOwned($request, $program);
        $this->normaliseCode($request);

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', $this->uniqueCode($request)->ignore($program->getKey())],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'unit' => ['sometimes', 'required', Rule::in(['admin', 'tech'])],
            'is_active' => ['sometimes', 'boolean'],
        ], $this->messages($request));

        /*
         * $request->boolean(), not $validated['is_active'] === false — the `boolean` rule accepts a
         * form-encoded "0" but hands it back as a string (see BranchController::update).
         */
        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        /*
         * A programme's code is fixed once it holds sections. Reports, exports and the registrar's
         * files name a programme by its code, so renaming HR to HRM under 300 sections would leave
         * every earlier report naming a programme that no longer exists, and every file still
         * saying HR unmatched. Unscoped: the question is whether ANY section holds it, not whether
         * the caller can see one. A programme with no sections may still be corrected.
         */
        if (array_key_exists('code', $validated) && $validated['code'] !== $program->code
            && $program->sections()->withoutGlobalScopes()->exists()) {
            throw ValidationException::withMessages(['code' => __('programs.errors.code_locked')]);
        }

        $deactivating = ($validated['is_active'] ?? true) === false && (bool) $program->is_active;

        if (! $deactivating) {
            $this->refusingDuplicateCode($request, fn (): bool => $program->update($validated));

            return $this->respond($request, ['data' => $this->present($program->loadCount(['courses', 'sections']))]);
        }

        /*
         * The contract's own key for the one question asked later — when did this programme go out
         * of use. saveQuietly suppresses the trait so exactly one entry is written (FR-028, FR-093).
         */
        DB::transaction(function () use ($program, $validated): void {
            $before = $program->only(['code', 'name_ar', 'name_en', 'unit', 'is_active']);

            $program->forceFill($validated)->saveQuietly();

            app(Auditor::class)->record('program.deactivated', $program, $before, $program->only(array_keys($before)));
        });

        return $this->respond($request, ['data' => $this->present($program->fresh()->loadCount(['courses', 'sections']))]);
    }

    /**
     * Replace the programme's course associations (FR-091). Audited with both id sets, so a reader
     * can see what was dropped as well as what was added.
     */
    public function updateCourses(Request $request, Program $program): JsonResponse|RedirectResponse
    {
        $this->ensureOwned($request, $program);

        $validated = $request->validate([
            // `present`, not `required`: an empty list is a legitimate "this programme has no
            // courses", and `required` refuses an empty array.
            'course_ids' => ['present', 'array'],
            'course_ids.*' => ['integer', 'distinct', $this->ownCourse($request)],
        ]);

        $new = array_map('intval', $validated['course_ids']);
        sort($new);

        DB::transaction(function () use ($program, $new): void {
            $previous = $program->courses()->orderBy('courses.id')->pluck('courses.id')
                ->map(static fn (mixed $id): int => (int) $id)->all();

            if ($previous === $new) {
                return;
            }

            $program->courses()->sync($new);

            app(Auditor::class)->record('program.courses_changed', $program, ['course_ids' => $previous], ['course_ids' => $new]);
        });

        $program->load(['courses' => fn ($q) => $q->orderBy('code')])->loadCount(['courses', 'sections']);

        return $this->respond($request, ['data' => $this->present($program) + [
            'courses' => $program->courses->map(fn (Course $course): array => $course->only(['id', 'code', 'name_ar', 'name_en']))->all(),
        ]]);
    }

    /**
     * The unique key is the final word on a duplicate, not the rule before it. Two Super Admins
     * submitting the same code at once both pass Rule::unique, and the second write meets the
     * (organization_id, code) key: answered here as the same 422 naming the code, not a 500.
     *
     * @template T
     *
     * @param  callable(): T  $write
     * @return T
     */
    private function refusingDuplicateCode(Request $request, callable $write): mixed
    {
        try {
            return $write();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => $this->messages($request)['code.unique']]);
        }
    }

    /**
     * @return Builder<Program>
     */
    private function owned(Request $request): Builder
    {
        return Program::query()->where('organization_id', $request->user()->organization_id);
    }

    /**
     * 404, not 403: another organization's programme does not exist as far as this caller knows.
     */
    private function ensureOwned(Request $request, Program $program): void
    {
        abort_unless((int) $program->organization_id === (int) $request->user()->organization_id, 404);
    }

    /**
     * The same normalisation Program::code applies on write, applied before validation so the
     * uniqueness check and the error message both see the stored form.
     */
    private function normaliseCode(Request $request): void
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => mb_strtoupper(trim($request->input('code')))]);
        }
    }

    /**
     * Per organization, and including soft-deleted rows — the unique key does not exclude them.
     */
    private function uniqueCode(Request $request): Unique
    {
        return Rule::unique('programs', 'code')->where('organization_id', $request->user()->organization_id);
    }

    private function ownCourse(Request $request): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('courses', 'id')->where('organization_id', $request->user()->organization_id);
    }

    /**
     * The duplicate-code refusal names the code (contracts/academic.md), so the person sees which
     * value collided rather than only that something did.
     *
     * @return array<string, string>
     */
    private function messages(Request $request): array
    {
        return [
            'code.unique' => __('programs.errors.code_taken', ['code' => (string) $request->input('code')]),
        ];
    }

    private function perPage(Request $request): int
    {
        return max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Program $program): array
    {
        return [
            'id' => $program->getKey(),
            'code' => $program->code,
            'name_ar' => $program->name_ar,
            'name_en' => $program->name_en,
            'unit' => $program->unit,
            'is_active' => (bool) $program->is_active,
            'courses_count' => (int) ($program->courses_count ?? 0),
            'sections_count' => (int) ($program->sections_count ?? 0),
        ];
    }
}
