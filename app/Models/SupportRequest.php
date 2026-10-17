<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use App\Modules\Authorization\Concerns\BelongsToBranchScope;
use App\Modules\Authorization\Concerns\BelongsToSectionScope;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
use App\Modules\Support\Enums\SupportRequestStatus;
use App\Modules\Support\Services\ReferenceGenerator;
use Carbon\CarbonInterval;
use Database\Factories\SupportRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * T363 + T365 — a support request raised by a student through the public form (Amendment 2).
 *
 * ## Scope
 *
 * Feature 001's `BelongsToBranchScope`, unchanged, resolved through the student's **active**
 * enrollments (data-model.md "Scoping"): a coordinator sees a request when its student holds an active
 * enrollment in a section of one of their branches. A student enrolled in two branches is visible to
 * both coordinators; an anonymised request has no student and so is visible to the Super Admin only.
 * The scope is inert with no authenticated user, which is what lets the public controllers create and
 * read a request without a caller.
 *
 * ## Why it also carries the section scope, constrained to nothing
 *
 * BranchScope **stands aside** for a caller with no branches who holds sections — an instructor —
 * because SectionScope governs them. A model with only the branch scope therefore gives an instructor
 * an unconstrained query. Instructors hold no support permission (FR-142), so the policy would refuse
 * them first; but the scope is the layer that holds when a policy check is forgotten (constitution
 * I), and here it would have held nothing. The section constraint resolves to no rows, so an
 * instructor's query is empty by construction.
 *
 * A coordinator who also teaches is governed by the branch scope alone: teaching is no claim on a
 * support request, so it must not narrow a branch claim to nothing (Phase 19 review; see
 * {@see constrainToSections()}).
 *
 * ## Audit
 *
 * `description` and `contact` are **not** in the allowlist. They are free text the student wrote, and
 * copying them into a five-year audit payload would put them beyond the request's own retention.
 * The sweep (R-12) clears `contact` at `support_retention_months`. It **keeps** `description`, as
 * FR-159 is written; whether it should is an open decision (security review R-06). The originating IP is in, because contracts/support-public.md puts it in the entry.
 *
 * @property int $id
 * @property string $reference
 * @property int|null $student_id
 * @property int $term_id
 * @property string $category
 * @property string $description
 * @property string|null $contact
 * @property SupportRequestStatus $status
 * @property string|null $rejection_reason
 * @property int|null $assigned_to
 * @property string|null $submitted_ip
 * @property Carbon|null $anonymised_at
 * @property Carbon|null $created_at
 * @property-read string $display_reference
 * @property-read CarbonInterval $waiting_time
 */
#[Fillable(['student_id', 'term_id', 'category', 'description', 'contact', 'submitted_ip'])]
class SupportRequest extends Model implements ConstrainableToBranches, ConstrainableToSections
{
    /** @use HasFactory<SupportRequestFactory> */
    use Auditable, BelongsToBranchScope, BelongsToSectionScope, HasFactory {
        Auditable::bootAuditable as private bootAuditableEveryEvent;
    }

    /**
     * Every request starts in `new`. Mirrors the column default so a freshly created model reads
     * its real state without a refresh.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * The Auditable trait, for creation only (Phase 19 review).
     *
     * Every later change to a request has a name in the contract — `support.status_changed`,
     * `support.reassigned`, `support.anonymised` — written by the action that makes it. The trait's
     * generic `updated` and `deleted` events would add a second, off-contract `support.updated` beside
     * each, and worse: the retention sweep clearing `student_id` and `submitted_ip` would copy their old
     * values into `old_values`, keeping the IP for the audit log's five years after the request's own
     * two. So only `created` is wired; an update through Eloquent writes no audit entry by itself, and
     * the action that made it is responsible for the named one.
     *
     * Laravel boots a trait by calling `boot<Trait>` on the class, so this method replaces the
     * trait's; the trait's version is kept under another name and never called.
     */
    public static function bootAuditable(): void
    {
        // `channel: public` (contracts/support-public.md): the public form is the only way a request
        // is created, and the entry says so beside its null actor rather than inventing a user.
        static::created(function (SupportRequest $request): void {
            $request->auditor()->record(
                $request->auditActionFor('created'),
                $request,
                [],
                $request->auditPayload($request->getAttributes()) + ['channel' => 'public'],
            );
        });
    }

    protected static function booted(): void
    {
        // T360. `student_id` is nullable only so the retention sweep can clear it; a request is never
        // created without the student it was verified against, or without its reference. Without this
        // a forgotten field stores a row no coordinator can see and no one can tell from an anonymised
        // one — and no reference means no way for the student to read it back.
        //
        // The address too (FR-123; Phase 25 review, L-2). `submitted_ip` became nullable so the retention
        // sweep can clear it, and the column no longer refuses a request recorded without one.
        static::creating(function (SupportRequest $request): void {
            if ($request->student_id === null
                || trim((string) $request->reference) === ''
                || trim((string) $request->submitted_ip) === '') {
                throw new LogicException('A support request is created with its student, its reference and its address.');
            }
        });

        static::saving(function (SupportRequest $request): void {
            // The reference is the student's only credential for reading the request back, and it is
            // written on a piece of paper somewhere. Changing it would lock the student out of their
            // own request with no way to find it again (T363).
            if ($request->exists && $request->isDirty('reference')) {
                throw new LogicException('A support request reference is never changed.');
            }

            // FR-149. The form request checks this first; this is the guard that holds when a later
            // code path forgets to (data-model.md "Validation rules").
            if ($request->status === SupportRequestStatus::Rejected
                && trim((string) $request->rejection_reason) === '') {
                throw new LogicException('A rejected support request must carry a rejection reason.');
            }
        });
    }

    /**
     * The student's active enrollments in the caller's branches, not every enrollment they ever held.
     *
     * "Active" is the same three conditions VerifyStudentIdentity uses (Phase 19 review): the
     * enrollment is active, the registrar has not marked it withdrawn, and its section is active — a
     * section still pending approval, returned or rejected (Amendment 4) grants no visibility, as it
     * grants no verification.
     *
     * Every inner query drops the global scopes: Student, SectionEnrollment and Section are scoped
     * themselves, and applying the caller's constraint again inside the definition of that constraint
     * narrows the result in a way that cannot be seen from here.
     *
     * @param  Builder<covariant Model>  $query
     * @param  list<int>  $branchIds
     */
    public function constrainToBranches(Builder $query, array $branchIds): void
    {
        /*
         * A semi-join on student_id (Phase 23 review), not whereHas('student') → whereHas('enrollments'):
         * that was a correlated EXISTS re-evaluated for every support_requests row, so a queue's count(*)
         * cost a nested probe per request in the table. This is one uncorrelated subquery — the students
         * who hold an enrollment in these branches now — and a lookup of student_id in it.
         *
         * Soft-deleted students are excluded: the first version dropped every global scope on Student,
         * SoftDeletingScope with them, and a deleted student's request stayed openable by coordinators
         * while rendering with no student at all.
         */
        $query->whereIn($this->qualifyColumn('student_id'), self::holdersIn($branchIds));
    }

    /**
     * The branches this request's student is actively enrolled in — the same three conditions as
     * {@see constrainToBranches()}, asked once (Phase 21 review). Who may be given the request is
     * "a college-wide viewer, or a coordinator of one of these", and asking that per candidate cost an
     * EXISTS query for every staff member on every page view.
     *
     * @return list<int>
     */
    public function studentBranchIds(): array
    {
        if ($this->student_id === null) {
            return [];
        }

        return Section::query()->withoutGlobalScopes()
            ->whereIn('id', SectionEnrollment::query()->withoutGlobalScopes()
                ->currentlyHeld()
                ->where('student_id', $this->student_id)
                ->select('section_id'))
            ->distinct()
            ->pluck('branch_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * The ids of the (not deleted) students who currently hold an enrollment in one of these branches —
     * the set a request's student must be in to be seen from those branches.
     *
     * @param  list<int>  $branchIds
     * @return Builder<SectionEnrollment>
     */
    public static function holdersIn(array $branchIds): Builder
    {
        return SectionEnrollment::query()->withoutGlobalScopes()
            ->currentlyHeld()
            ->whereIn('section_enrollments.section_id', Section::query()->withoutGlobalScopes()->whereIn('branch_id', $branchIds)->select('id'))
            ->whereIn('section_enrollments.student_id', Student::query()->withoutGlobalScopes()->whereNull('deleted_at')->select('id'))
            ->select('section_enrollments.student_id');
    }

    /**
     * No section grants access to a support request. See the class docblock.
     *
     * A caller who also holds branches — a coordinator who teaches — is left to the branch scope
     * (Phase 19 review). Both scopes are registered and Eloquent ANDs them, so constraining here too
     * would empty that coordinator's whole queue: the policy grants `support.view`, and the queue
     * would show nothing. SectionScope's own fails-safe AND is right for sections, where teaching is a
     * claim; here teaching is no claim at all, so it must neither widen nor narrow a branch claim.
     *
     * @param  Builder<covariant Model>  $query
     * @param  list<int>  $sectionIds
     */
    public function constrainToSections(Builder $query, array $sectionIds): void
    {
        $caller = Auth::user();

        if ($caller instanceof User && $caller->coordinatorBranchIds() !== []) {
            return;
        }

        $query->whereKey([]);
    }

    /**
     * T412 — staff URLs carry the reference, never the id: the id is sequential and a coordinator
     * could walk it, the reference is not (contracts/support-staff.md).
     */
    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * Bound through the scoped query, as every model here is: a request outside the caller's
     * visibility is a ModelNotFoundException, so a 404 indistinguishable from no such reference, and
     * AuditDeniedAccess records it (FR-143, FR-158). The URL may carry the hyphenated form or lower
     * case; it is normalised to the stored one first, and anything that is not a reference matches
     * nothing.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        // Another key (a `{supportRequest:id}` route, say) binds the ordinary way — still scoped.
        if ($field !== null && $field !== $this->getRouteKeyName()) {
            return static::query()->where($field, $value)->first();
        }

        $reference = is_string($value) ? ReferenceGenerator::normalise($value) : null;

        return $reference === null
            ? null
            : static::query()->where($this->getRouteKeyName(), $reference)->first();
    }

    /**
     * The reference as a person reads it: `K7Q2-M9XV-T3X`. Stored canonical, without the hyphens.
     *
     * @return Attribute<string, never>
     */
    protected function displayReference(): Attribute
    {
        return Attribute::get(fn (): string => ReferenceGenerator::format((string) $this->reference));
    }

    /**
     * How long the request has waited since it was raised — what the queue sorts and shows.
     *
     * From creation, not from the last change: the student's wait began when they asked.
     *
     * @return Attribute<CarbonInterval, never>
     */
    protected function waitingTime(): Attribute
    {
        return Attribute::get(function (): CarbonInterval {
            /** @var Carbon $created */
            $created = $this->created_at ?? now();

            return $created->diffAsCarbonInterval(now());
        });
    }

    /** @return HasMany<SupportResponse, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(SupportResponse::class)->oldest('created_at')->oldest('id');
    }

    /**
     * withTrashed: a student soft-deleted after raising a request still raised it.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * contracts names the entity `support`, and the creation `support.request_created` — the trait's
     * default would be `support_request.created`, a key no audit screen can filter or label.
     */
    public function auditEntityKey(): string
    {
        return 'support';
    }

    public function auditActionFor(string $event): string
    {
        return $this->auditEntityKey().'.'.($event === 'created' ? 'request_created' : $event);
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return [
            'reference', 'student_id', 'term_id', 'category', 'status', 'rejection_reason',
            'assigned_to', 'submitted_ip', 'anonymised_at',
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SupportRequestStatus::class,
            'anonymised_at' => 'datetime',
        ];
    }
}
