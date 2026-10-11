<?php

declare(strict_types=1);

namespace App\Modules\AcademicTerms\Http\Controllers;

use App\Models\AcademicTerm;
use App\Modules\AcademicTerms\Actions\ChangeTermStatus;
use App\Modules\AcademicTerms\Actions\SetCurrentTerm;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * T186 — terms, per contracts/academic.md.
 *
 * Five endpoints: list, create, edit, change status, set current. The last two are their own
 * endpoints rather than fields on the edit, because each has its own permission, its own audit key
 * and its own refusal — folding them into `PUT /terms/{id}` would make "may edit a name" and "may
 * close a term" the same authority.
 */
final class TermController extends Controller
{
    use RespondsToBoth;

    private const MAX_PER_PAGE = 100;

    /**
     * Archived terms are excluded unless asked for by name (contracts/academic.md, the
     * default-view rule). An archive that fills the default list is an archive nobody archived.
     */
    public function index(Request $request): JsonResponse|View
    {
        $request->validate([
            'status' => ['nullable', Rule::in(array_keys(ChangeTermStatus::TRANSITIONS))],
            'is_current' => ['nullable', 'boolean'],
            'academic_year' => ['nullable', 'string', 'max:9'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $terms = AcademicTerm::query()
            ->when($request->filled('status'),
                fn ($q) => $q->where('status', $request->string('status')->toString()),
                fn ($q) => $q->where('status', '!=', 'archived'),
            )
            ->when($request->filled('is_current'), fn ($q) => $q->where('is_current', $request->boolean('is_current')))
            ->when($request->filled('academic_year'), fn ($q) => $q->where('academic_year', $request->string('academic_year')->toString()))
            ->withCount('sections')
            ->orderByDesc('start_date')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $terms->getCollection()->map($this->present(...))->all(),
                'meta' => [
                    'current_page' => $terms->currentPage(),
                    'last_page' => $terms->lastPage(),
                    'total' => $terms->total(),
                ],
            ]);
        }

        return view('terms.index', ['terms' => $terms]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->rules());

        // Always draft. The contract says so, and a term that could be created already active would
        // make the first transition unauditable.
        $term = AcademicTerm::query()->create($validated + ['status' => 'draft', 'is_current' => false]);

        return $this->respond($request, ['data' => $this->present($term->loadCount('sections'))], 201);
    }

    public function update(Request $request, AcademicTerm $term): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->rules(required: false));

        $term->update($validated);

        return $this->respond($request, ['data' => $this->present($term->loadCount('sections'))]);
    }

    /**
     * PATCH /terms/{id}/status — the state machine, not a field assignment.
     */
    public function changeStatus(Request $request, AcademicTerm $term): JsonResponse|RedirectResponse
    {
        /*
         * The reason is a field rule, not only the action's business check (T263, FR-078).
         *
         * The dialog marks the textarea required with minlength 3 when leaving a closed term. The
         * server used to accept any non-blank string — "بب" reopened a term — and refused a blank one
         * with a 409, which a browser shows as a banner rather than under the field. Trimmed first, so
         * three spaces are a missing reason, as on the withdraw endpoint.
         */
        $request->merge(['reason' => $request->filled('reason') ? trim((string) $request->input('reason')) : null]);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ChangeTermStatus::TRANSITIONS))],
            'reason' => [
                Rule::requiredIf(ChangeTermStatus::needsReason($term->status, (string) $request->input('status'))),
                'nullable', 'string', 'min:3', 'max:500',
            ],
        ], [
            'reason.required' => __('terms.errors.reason_required'),
        ]);

        /*
         * Reopening a closed term needs `term.edit_closed`, checked here rather than on the route:
         * the route's permission is `term.change_status`, and only this one transition costs more.
         */
        if ($term->status === 'closed' && $validated['status'] !== 'archived') {
            abort_unless($request->user()?->can('term.edit_closed'), 403);
        }

        $updated = app(ChangeTermStatus::class)->execute($term, $validated['status'], $validated['reason'] ?? null);

        return $this->respond($request, ['data' => $this->present($updated->loadCount('sections'))]);
    }

    public function setCurrent(Request $request, AcademicTerm $term): JsonResponse|RedirectResponse
    {
        $updated = app(SetCurrentTerm::class)->execute($term);

        return $this->respond($request, ['data' => $this->present($updated->loadCount('sections'))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $required = true): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return [
            'name_ar' => [$presence, 'string', 'max:255'],
            'name_en' => [$presence, 'string', 'max:255'],

            // `2026/2027` — the shape the registrar writes, pinned so a list sorts and groups.
            'academic_year' => [$presence, 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => [$presence, Rule::in(['first', 'second', 'summer'])],
            'start_date' => [$presence, 'date'],
            'end_date' => [$presence, 'date', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AcademicTerm $term): array
    {
        return [
            'id' => $term->getKey(),
            'name_ar' => $term->name_ar,
            'name_en' => $term->name_en,
            'academic_year' => $term->academic_year,
            'semester' => $term->semester,
            'start_date' => $term->start_date?->toDateString(),
            'end_date' => $term->end_date?->toDateString(),
            'status' => $term->status,
            'is_current' => (bool) $term->is_current,
            'sections_count' => (int) ($term->sections_count ?? 0),

            // What the screen may offer, decided here rather than duplicated in Blade.
            'allowed_transitions' => ChangeTermStatus::TRANSITIONS[$term->status] ?? [],
        ];
    }
}
