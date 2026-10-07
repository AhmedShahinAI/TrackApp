<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Section;
use App\Models\Student;
use App\Modules\Audit\Http\Resources\AuditLogResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * T166 — `GET /api/v1/{entity}/{id}/activity` (FR-033).
 *
 * One entity's history, for terms, branches, sections and students.
 *
 * ## Why the entity is resolved here rather than by route-model binding
 *
 * The route takes `{entity}` as a *word*, so there is nothing for the binder to bind. Resolving it
 * by hand means the 404-before-anything-else ordering has to be reproduced deliberately:
 *
 *   • an entity name outside the four → 404, not 422. A 422 would confirm that `/initiatives/5/activity`
 *     is a route the platform knows about and `/nonsense/5/activity` is not, which is a map of the
 *     application handed to anyone who asks.
 *   • an id the caller cannot see → 404, because the model is fetched through its scoped query.
 *
 * contracts/audit.md states the second one as a requirement in its own right: "the activity view
 * cannot be used to probe for records the caller cannot otherwise reach". Without it this endpoint
 * would be the one hole in US2's scope — a cheap oracle for the existence of any id in the college.
 *
 * ## Permission
 *
 * The entity's own `*.view` plus `audit.view`, both checked here rather than in route middleware,
 * because the first of the pair depends on which entity was named.
 */
final class EntityActivityController extends Controller
{
    /**
     * The four entities with an activity view, and the permission each needs.
     *
     * A map rather than a convention: `terms` resolving to AcademicTerm by string manipulation
     * would quietly admit any model whose name happened to match, and the point of this endpoint is
     * that it admits exactly four.
     *
     * @var array<string, array{model: class-string<Model>, permission: string, type: string}>
     */
    private const ENTITIES = [
        'terms' => ['model' => AcademicTerm::class, 'permission' => 'term.view', 'type' => 'AcademicTerm'],
        'branches' => ['model' => Branch::class, 'permission' => 'branch.view', 'type' => 'Branch'],
        'sections' => ['model' => Section::class, 'permission' => 'section.view', 'type' => 'Section'],
        'students' => ['model' => Student::class, 'permission' => 'student.view', 'type' => 'Student'],
    ];

    private const MAX_PER_PAGE = 100;

    public function index(Request $request, string $entity, int $id): JsonResponse
    {
        $definition = self::ENTITIES[$entity] ?? null;

        if ($definition === null) {
            // 404, not 422: the set of valid entity names is not something to disclose.
            throw new NotFoundHttpException;
        }

        $caller = $request->user();

        /*
         * Both permissions, checked here rather than in route middleware: which entity permission
         * applies depends on which word arrived in the URL, so the route cannot name it.
         */
        abort_unless($caller !== null && $caller->can($definition['permission']), 403);
        abort_unless($caller->can('audit.view'), 403);

        /*
         * Through the model's own query, so the global scope applies. An out-of-scope id is simply
         * not there, and `findOrFail` turns that into the same 404 a nonexistent id produces.
         */
        $definition['model']::query()->findOrFail($id);

        $entries = AuditLog::query()
            ->where('entity_type', $definition['type'])
            ->where('entity_id', $id)
            ->with('user:id,name_ar,name_en')
            ->orderByDesc('created_at')
            ->paginate(max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE)))
            ->withQueryString();

        return response()->json([
            'data' => AuditLogResource::collection($entries->getCollection())->resolve(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total' => $entries->total(),
            ],
        ]);
    }
}
