<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Section;
use App\Models\User;
use App\Modules\Audit\Exports\AuditLogExport;
use App\Modules\Audit\Http\Resources\AuditLogResource;
use App\Modules\Audit\Services\Auditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The audit log, read through the same scope as everything else.
 *
 * Per contracts/audit.md a coordinator sees "entries whose entity resolves into their branches,
 * plus their own actions", and `branch_id` filtering "for a coordinator is implicit and cannot be
 * widened". Both halves are the global scope's doing: the filter below can only ever narrow what
 * the scope already allows, because it is applied to the same builder.
 *
 * This replaces the unscoped `DB::table('audit_logs')` query the landing page was using, which
 * showed every coordinator the whole college's activity while its own comment claimed otherwise.
 */
final class AuditLogController extends Controller
{
    private const MAX_PER_PAGE = 100;

    /**
     * The filters contracts/audit.md defines, named once so the list, the export and the export's
     * header block cannot drift apart.
     *
     * @var list<string>
     */
    private const FILTERS = ['user_id', 'action', 'entity_type', 'entity_id', 'date_from', 'date_to', 'branch_id'];

    public function index(Request $request): JsonResponse|View
    {
        $scoped = $this->filtered($request);

        $entries = (clone $scoped)
            ->with('user:id,name_ar,name_en')
            ->orderByDesc('created_at')
            ->paginate(max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE)))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => AuditLogResource::collection($entries->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'total' => $entries->total(),
                ],
            ]);
        }

        return view('audit.index', [
            'entries' => $entries,

            /*
             * The actor dropdown lists only people who appear in entries this caller can see. The
             * obvious alternative — every user in the college — would turn the filter into a staff
             * directory for a coordinator, which is a scope leak through a <select>.
             */
            /*
             * Distinct actors across the whole scoped log, not the twenty-five rows on this page.
             * Built from the page, the dropdown offered whoever happened to be visible — and
             * collapsed to a single name the moment a filter was applied, which is exactly when a
             * person wants to change it.
             *
             * Still scoped: the subquery runs on the same globally-scoped builder, so a coordinator
             * is offered only people who appear in entries they may read. Listing every user in the
             * college here would turn the filter into a staff directory.
             */
            'actors' => User::query()
                ->whereIn('id', (clone $scoped)->select('user_id')->distinct())
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en']),

            'entityOptions' => ['' => __('common.labels.all')] + collect(array_keys(
                (array) __('audit.entities'),
            ))->mapWithKeys(fn (string $type) => [$type => __('audit.entities.'.$type)])->all(),
        ]);
    }

    /**
     * FR-023, FR-032 — the same rows the list returns, under the same scope.
     *
     * Exporting the audit log is itself audited: someone taking a copy of the accountability record
     * is exactly the event the accountability record should contain.
     */
    /**
     * FR-023, FR-032 — the same rows the list returns, under the same scope.
     *
     * The builder is handed to the export rather than rebuilt inside it, which is what makes "the
     * export contains exactly what the list contains" structural instead of a promise two methods
     * take turns keeping.
     *
     * Exporting the audit log is itself audited: someone taking a copy of the accountability record
     * is precisely the event the accountability record should contain.
     */
    public function export(Request $request): StreamedResponse
    {
        app(Auditor::class)->record('audit.exported', null, [], [
            'filters' => $request->only(self::FILTERS),
        ]);

        return (new AuditLogExport(
            $this->filtered($request),
            $request->only(self::FILTERS),
            $request->user(),
        ))->toResponse();
    }

    /**
     * The scoped, filtered query both the list and the export are built from.
     *
     * Every `when` here can only narrow. The builder starts from AuditLog::query(), which the
     * global scope has already constrained to what this caller may see, so a filter the caller
     * controls cannot reach past it — which is how "for a coordinator branch_id is implicit and
     * cannot be widened" is true by construction rather than by a check someone has to remember.
     *
     * @return Builder<AuditLog>
     */
    private function filtered(Request $request): Builder
    {
        /*
         * Validated before use. `Request::date()` throws on an unparseable value and on an array,
         * so `?date_from=garbage` and `?date_from[]=1` were 500s on the list, the screen and the
         * export link alike — a malformed query string should be the caller's problem, answered
         * with a 422, not an exception page that says the server is broken.
         */
        $request->validate([
            'user_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:100'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'entity_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        // The days the person picked are Riyadh days, matching the dates the list shows them
        // (Format::dateTime); the column is UTC, so each bound is converted before it is compared.
        $zone = (string) config('app.display_timezone', 'Asia/Riyadh');

        return AuditLog::query()
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')->toString()))
            ->when($request->filled('entity_type'), fn ($q) => $q->where('entity_type', $request->string('entity_type')->toString()))
            ->when($request->filled('entity_id'), fn ($q) => $q->where('entity_id', $request->integer('entity_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->where('created_at', '>=', $request->date('date_from', null, $zone)?->startOfDay()->utc()))
            /*
             * endOfDay, because a date is a day and not a midnight.
             *
             * `<= 2026-10-07` compared against `2026-10-07 00:00:00` excluded everything that
             * happened on the day the person selected — measured: an entry at 14:30 and a filter
             * naming that date returned zero rows. The same bug reached the export, which is built
             * from this builder, so a report of "the last week" silently ended seven days ago.
             */
            ->when($request->filled('date_to'), fn ($q) => $q->where('created_at', '<=', $request->date('date_to', null, $zone)?->endOfDay()->utc()))
            ->when($request->filled('branch_id'), fn ($q) => $this->narrowToBranch($q, $request->integer('branch_id')));
    }

    /**
     * T163 — narrow to one branch's entries.
     *
     * This can only ever narrow. It is applied to the same builder the global scope has already
     * constrained, so for a coordinator asking after a branch they do not hold, the two predicates
     * intersect to nothing — which is what contracts/audit.md means by "for a coordinator it is
     * implicit and cannot be widened". The filter needs no permission check of its own, and must
     * not be given one: a check here would be a second place for the rule to live.
     *
     * The shape mirrors AuditLog::constrainToBranches(): an entry belongs to a branch when the
     * entity it names resolves into that branch. An entry with no scoped entity — a sign-in, a
     * password change — belongs to no branch and is correctly excluded by a branch filter.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    private function narrowToBranch(Builder $query, int $branchId): Builder
    {
        $sections = Section::query()->withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->select('id');

        return $query->where(function (Builder $outer) use ($sections): void {
            $outer->where(function (Builder $inner) use ($sections): void {
                $inner->where('entity_type', 'Section')->whereIn('entity_id', $sections);
            })->orWhere(function (Builder $inner) use ($sections): void {
                $inner->where('entity_type', 'SectionEnrollment')
                    ->whereIn('entity_id', DB::table('section_enrollments')
                        ->select('id')
                        ->whereIn('section_id', $sections));
            });
        });
    }
}
