<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Models\Branch;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\InSetting;
use App\Modules\Shared\Rules\UniversityId;
use App\Modules\Support\Actions\ReassignSupportRequest;
use App\Modules\Support\Actions\TransitionSupportRequest;
use App\Modules\Support\Enums\SupportRequestStatus;
use App\Modules\Support\Http\SupportRequestPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * T411–T413 — the staff queue and one request in full (contracts/support-staff.md).
 *
 * Everything here is read through SupportRequest's branch scope (constitution I). The controller
 * never decides visibility; a request outside it is a 404 from the binding, recorded as
 * `access.denied` (FR-143, FR-158).
 */
final class SupportQueueController
{
    private const PER_PAGE = 25;

    private const PER_PAGE_MAX = 100;

    /** A name search wider than this is not a search; the queue shows the first matches' requests. */
    private const NAME_MATCH_LIMIT = 500;

    /**
     * GET /support-requests (the screen) and GET /api/v1/support-requests (the queue).
     *
     * Oldest first, because the queue exists to be worked down (FR-137).
     */
    public function index(Request $request): JsonResponse|View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(SupportRequestStatus::class)],
            'category' => ['nullable', 'string', new InSetting(Settings::SUPPORT_CATEGORIES)],
            'branch_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            // A calendar day, nothing else: it is joined to a time of day below, and a datetime the
            // looser `date` rule accepts made a malformed timestamp (Phase 21 review).
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::PER_PAGE_MAX],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $query = SupportRequest::query()->with(SupportRequestPresenter::relations());

        $this->filter($query, $filters, $actor);

        $page = $query->oldest('created_at')->oldest('id')
            ->paginate((int) ($filters['per_page'] ?? self::PER_PAGE))
            ->withQueryString();

        $rows = $page->getCollection()->map(SupportRequestPresenter::row(...))->all();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $rows,
                'meta' => ['total' => $page->total(), 'page' => $page->currentPage(), 'per_page' => $page->perPage()],
            ]);
        }

        return view('support.staff.queue', [
            'page' => $page,
            'rows' => $rows,
            'filters' => $filters,
            'branches' => $this->branchOptions($actor),
            // T442: a queue already single-scoped — a coordinator of one branch — has nothing to choose
            // between, so the branch column and filter are shown to the Super Admin and to a coordinator
            // of several branches only.
            'showBranch' => $actor->can('scope.bypass_all') || count($actor->coordinatorBranchIds()) > 1,
            'assignees' => $this->options($this->assignableUsers($actor)),
        ]);
    }

    /**
     * GET /support-requests/{reference} and GET /api/v1/support-requests/{reference}.
     *
     * The side effect (T413): a `new` request opened by someone who may respond to it becomes
     * `in_progress`, recording who (FR-147). Stated in the contract because a read that writes is
     * unusual; the alternative, an explicit "claim", is a click on every request for no benefit.
     */
    public function show(Request $request, SupportRequest $supportRequest, TransitionSupportRequest $transition): JsonResponse|View
    {
        /** @var User $actor */
        $actor = $request->user();

        // Checked first without a lock so an already-open request costs a page view nothing; decided
        // under the lock (openIfNew), so two people opening it at once both get the page.
        if ($supportRequest->status === SupportRequestStatus::New && $actor->can('respond', $supportRequest)) {
            $supportRequest = $transition->openIfNew($supportRequest, $actor);
        }

        $supportRequest->load(SupportRequestPresenter::relations());
        $detail = SupportRequestPresenter::detail($supportRequest);

        if ($request->expectsJson()) {
            return response()->json(['data' => $detail]);
        }

        return view('support.staff.show', [
            'request' => $supportRequest,
            'detail' => $detail,
            'targets' => array_values(array_filter(
                SupportRequestStatus::cases(),
                fn (SupportRequestStatus $to): bool => TransitionSupportRequest::permits($supportRequest->status, $to)
                    && ($to !== SupportRequestStatus::Closed || $actor->can('close', $supportRequest)),
            )),
            // Only the people who could open this request: offering anyone else is offering to strand
            // it. The student's branches are asked once and every candidate's are eager-loaded, so this
            // is three queries however many staff there are (Phase 21 review).
            'assignees' => $actor->can('assign', $supportRequest)
                ? $this->assigneeCandidates($supportRequest, $actor)
                : [],
        ]);
    }

    /**
     * @param  Builder<SupportRequest>  $query
     * @param  array<string, mixed>  $filters
     */
    private function filter(Builder $query, array $filters, User $actor): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        /*
         * The day a person picks is a day where they are — the display timezone — and `created_at` is
         * stored in UTC. Comparing the picked day's clock times directly against UTC put every
         * boundary three hours off in Riyadh: a request at 01:30 on the 12th fell on the 11th (Phase
         * 21 review). The day's first and last instant are converted to UTC first.
         */
        $zone = (string) config('app.display_timezone', 'Asia/Riyadh');

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', $filters['date_from'], $zone)->startOfDay()->utc());
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', $filters['date_to'], $zone)->endOfDay()->utc());
        }

        /*
         * `branch_id` narrows only within the caller's own scope (contracts/support-staff.md): the Super
         * Admin may pick any branch; a coordinator's choice applies only if it is one of theirs and is
         * otherwise ignored — their own scope comes back, not an error, so the filter cannot be used
         * to ask whether another branch has requests.
         */
        if (isset($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];

            $own = $actor->coordinatorBranchIds();

            // A coordinator of only this branch is already exactly this (the scope says so): adding it
            // again doubled the scope's work for nothing (Phase 23 review).
            $redundant = ! $actor->can('scope.bypass_all') && $own === [$branchId];

            if (! $redundant && ($actor->can('scope.bypass_all') || in_array($branchId, $own, true))) {
                $query->whereIn('student_id', SupportRequest::holdersIn([$branchId]));
            }
        }

        if (isset($filters['q']) && trim((string) $filters['q']) !== '') {
            $this->search($query, trim((string) $filters['q']));
        }
    }

    /**
     * `q`: a reference (typed any way the student might read it out), a University ID, or part of the
     * student's name.
     *
     * @param  Builder<SupportRequest>  $query
     */
    private function search(Builder $query, string $q): void
    {
        /*
         * Cheapest first, and only one (Phase 21 review). A reference is the unique index; a University
         * ID is the students' unique index. Only text that is neither reaches the name match — a
         * leading-wildcard LIKE no index can serve — and it is run ONCE against students, bounded, and
         * handed to the queue as a list of ids. It used to be a correlated subquery evaluated per
         * candidate request, twice per page (the count and the rows).
         */
        $reference = SupportRequestPresenter::referenceFrom($q);

        if ($reference !== null) {
            $query->where('reference', $reference);

            return;
        }

        $universityId = UniversityId::normalise($q);

        if (ctype_digit($universityId)) {
            $query->whereIn('student_id', Student::query()->withoutGlobalScopes()->where('university_id', $universityId)->select('id'));

            return;
        }

        $like = '%'.addcslashes($q, '\\%_').'%';

        // Through the caller's own student scope, before the cap (Phase 23 review): capped across every
        // branch, a coordinator's own matches could be crowded out by other branches' students — and
        // whether their search found anything then said something about other branches' data.
        $studentIds = Student::query()
            ->where(fn (Builder $s) => $s->where('name_ar', 'like', $like)->orWhere('name_en', 'like', $like))
            ->limit(self::NAME_MATCH_LIMIT)
            ->pluck('id')
            ->all();

        $query->whereIn('student_id', $studentIds);
    }

    /**
     * The branches the caller may filter by: every branch for a college-wide caller, their own for a
     * coordinator.
     *
     * @return list<array{value: int, label: string}>
     */
    private function branchOptions(User $actor): array
    {
        $locale = app()->getLocale();

        $branches = Branch::query()->withoutGlobalScopes()
            ->when(! $actor->can('scope.bypass_all'), fn (Builder $q) => $q->whereIn('id', $actor->coordinatorBranchIds()))
            ->orderBy('campus_name_'.$locale)
            ->get();

        return $branches->map(fn (Branch $branch): array => [
            'value' => (int) $branch->id,
            'label' => (string) ($branch->{'campus_name_'.$locale} ?? $branch->campus_name_ar),
        ])->values()->all();
    }

    /**
     * Active users who hold `support.view` — the people a request can be filtered by or given to.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function assignableUsers(User $actor): \Illuminate\Support\Collection
    {
        /*
         * Within the caller's scope (Phase 21 review). Listing every support.view holder showed a
         * coordinator the names and ids of every other branch's staff. A coordinator now sees
         * themselves and the staff who share one of their branches — exactly the people a request in
         * their queue can be assigned to — and the Super Admin sees everyone.
         */
        return User::permission('support.view')
            ->where('is_active', true)
            ->when(! $actor->can('scope.bypass_all'), fn (Builder $q) => $q->where(fn (Builder $mine) => $mine
                ->whereKey($actor->getKey())
                ->orWhereHas('coordinatorBranches', fn (Builder $b) => $b->whereIn('branches.id', $actor->coordinatorBranchIds()))))
            ->orderBy('name_'.app()->getLocale())
            ->get()
            ->toBase();
    }

    /**
     * The people this request may be given to, for the reassignment control.
     *
     * @return list<array{value: int, label: string}>
     */
    private function assigneeCandidates(SupportRequest $request, User $actor): array
    {
        $studentBranchIds = $request->studentBranchIds();

        /*
         * SEC-055 (G-22 review). `studentBranchIds()` spans every branch the student is enrolled in,
         * not only the caller's, so filtering on "could open it" alone showed a branch A coordinator
         * the names of branch B's staff, and with them the student's enrollment in B: the disclosure
         * assignableUsers() was fixed for in the Phase 21 review. Same rule here: the caller's own
         * organization always, and for anyone without scope.bypass_all, themselves and the staff who
         * share one of their branches.
         */
        $users = User::permission('support.view')
            ->where('is_active', true)
            ->where('organization_id', $actor->organization_id)
            ->when(! $actor->can('scope.bypass_all'), fn (Builder $q) => $q->where(fn (Builder $mine) => $mine
                ->whereKey($actor->getKey())
                ->orWhereHas('coordinatorBranches', fn (Builder $b) => $b->whereIn('branches.id', $actor->coordinatorBranchIds()))))
            ->with(['coordinatorBranches', 'roles.permissions', 'permissions'])
            ->orderBy('name_'.app()->getLocale())
            ->get()
            ->toBase()
            ->filter(fn (User $user): bool => ReassignSupportRequest::canOpen($user, $request, $studentBranchIds));

        return $this->options($users);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @return list<array{value: int, label: string}>
     */
    private function options(\Illuminate\Support\Collection $users): array
    {
        $locale = app()->getLocale();

        return $users->map(fn (User $user): array => [
            'value' => (int) $user->getKey(),
            'label' => (string) ($locale === 'en' && $user->name_en ? $user->name_en : $user->name_ar),
        ])->values()->all();
    }
}
