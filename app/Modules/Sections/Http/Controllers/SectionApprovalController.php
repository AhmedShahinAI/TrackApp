<?php

declare(strict_types=1);

namespace App\Modules\Sections\Http\Controllers;

use App\Models\Section;
use App\Models\SectionApprovalHistory;
use App\Models\User;
use App\Modules\Sections\Actions\DecideSectionApproval;
use App\Modules\Sections\Actions\ResubmitSection;
use App\Modules\Sections\Enums\SectionStatus;
use App\Modules\Sections\Http\Requests\DecideSectionRequest;
use App\Modules\Sections\Http\Requests\StoreSectionRequest;
use App\Modules\Shared\Http\Concerns\RespondsToBoth;
use App\Modules\Shared\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * T492 + T493 — the section approval surface (contracts/academic.md "Section approval").
 *
 * Every route binds the section through its global scopes, so a section outside the caller's branches is
 * a 404 before any policy runs. The 403s are the policy's: a section the caller can see but may not
 * decide (FR-178).
 */
final class SectionApprovalController extends Controller
{
    use RespondsToBoth;

    private const MAX_PER_PAGE = 100;

    /**
     * T492 — `GET /sections/approvals`: what this caller may decide, oldest waiting first. The creator
     * with their role, the course, the branch, the enrollment count and the waiting time come in one
     * query per kind, however many rows.
     */
    public function index(Request $request): JsonResponse|View
    {
        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'course_id' => ['nullable', 'integer'],
            'created_by' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $sections = Section::query()
            ->awaitingDecisionBy($actor)
            ->when(isset($filters['branch_id']), fn ($q) => $q->where('branch_id', $filters['branch_id']))
            ->when(isset($filters['course_id']), fn ($q) => $q->where('course_id', $filters['course_id']))
            ->when(isset($filters['created_by']), fn ($q) => $q->where('created_by', $filters['created_by']))
            ->with(['course:id,code,name_ar,name_en', 'branch:id,campus_name_ar,campus_name_en,gender', 'creator:id,name_ar,name_en', 'creator.roles:id,name'])
            ->withCount(['enrollments as active_enrollments' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();

        $rows = $sections->getCollection()->map(fn (Section $section): array => $this->queueRow($section))->all();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $rows,
                'meta' => ['current_page' => $sections->currentPage(), 'last_page' => $sections->lastPage(), 'total' => $sections->total()],
            ]);
        }

        return view('sections.approvals', ['sections' => $sections, 'rows' => $rows]);
    }

    /**
     * T493 — `PATCH /sections/{id}/approval`. The policy decides who (403); the action decides what
     * (409); the request decides the shape (422).
     */
    public function decide(DecideSectionRequest $request, Section $section): JsonResponse|RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        Gate::forUser($actor)->authorize('approve', $section);

        $decided = app(DecideSectionApproval::class)->execute($section, $actor, $request->validated('decision'), $request->validated('reason'));

        return $this->respond($request, ['data' => $this->decision($decided)]);
    }

    /**
     * T493 — `PATCH /sections/{id}/resubmit`. The creator only, from `returned` only. The section's own
     * fields may be corrected in the same request, through the same form request as create.
     */
    public function resubmit(StoreSectionRequest $request, Section $section): JsonResponse|RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        Gate::forUser($actor)->authorize('resubmit', $section);

        $changes = array_intersect_key($request->validated(), array_flip($section->getFillable()));
        $resubmitted = app(ResubmitSection::class)->execute($section, $actor, $changes);

        return $this->respond($request, ['data' => $this->decision($resubmitted)]);
    }

    /**
     * T493 — `GET /sections/{id}/approval-history`: the append-only decision trail.
     */
    public function history(Section $section): JsonResponse
    {
        $locale = app()->getLocale();

        return response()->json([
            'data' => $section->approvalHistory()->with('actor:id,name_ar,name_en')->get()
                ->map(fn (SectionApprovalHistory $row): array => [
                    'from_status' => $row->from_status,
                    'to_status' => $row->to_status,
                    'actor' => ['id' => $row->actor_id, 'name' => $row->actor?->{'name_'.$locale} ?: $row->actor?->name_ar],
                    'reason' => $row->reason,
                    'at' => $row->created_at->toIso8601String(),
                ])->all(),
        ]);
    }

    /** @return array<string, mixed> */
    private function queueRow(Section $section): array
    {
        $locale = app()->getLocale();
        $role = $section->creator?->roles->pluck('name')->first();

        return [
            'id' => $section->getKey(),
            'section_number' => $section->section_number,
            'course' => ['code' => $section->course?->code, 'name' => $section->course?->{'name_'.$locale} ?: $section->course?->name_ar],
            'branch' => $section->branch === null ? null : ($section->branch->{'campus_name_'.$locale} ?: $section->branch->campus_name_ar).' ('.__('branches.gender.'.$section->branch->gender).')',
            'creator' => [
                'id' => $section->created_by,
                'name' => $section->creator?->{'name_'.$locale} ?: $section->creator?->name_ar,
                'role' => $role,
                'role_label' => $role === null ? null : __('users.roles.'.$role),
            ],
            'active_enrollments' => (int) $section->getAttribute('active_enrollments'),
            'status' => SectionStatus::Pending->value,
            'waiting_since' => $section->created_at?->toIso8601String(),
            'waiting_label' => Format::dateTime($section->created_at),
        ];
    }

    /** @return array<string, mixed> */
    private function decision(Section $section): array
    {
        return [
            'id' => $section->getKey(),
            'status' => $section->status,
            'requires_approval' => (bool) $section->requires_approval,
            'created_by' => $section->created_by,
            'reviewed_by' => $section->reviewed_by,
            'reviewed_at' => \Illuminate\Support\Carbon::make($section->reviewed_at)?->toIso8601String(),
            'review_reason' => $section->review_reason,
        ];
    }
}
