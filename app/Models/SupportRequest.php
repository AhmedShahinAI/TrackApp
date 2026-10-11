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
 * A coordinator who also teaches stays under both scopes, which AND together, and so sees no request
 * at all. That is the fails-safe direction SectionScope already documents for dual claims, and it is
 * flagged with the rest of that question rather than decided here.
 *
 * ## Audit
 *
 * `description` and `contact` are **not** in the allowlist. They are free text the student wrote and
 * are cleared or kept for two years (R-12); copying them into a five-year audit payload would defeat
 * that retention. The originating IP is in, because contracts/support-public.md puts it in the entry.
 */
#[Fillable(['student_id', 'term_id', 'category', 'description', 'contact', 'submitted_ip'])]
class SupportRequest extends Model implements ConstrainableToBranches, ConstrainableToSections
{
    /** @use HasFactory<SupportRequestFactory> */
    use Auditable, BelongsToBranchScope, BelongsToSectionScope, HasFactory;

    /**
     * Every request starts in `new`. Mirrors the column default so a freshly created model reads
     * its real state without a refresh.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
    ];

    protected static function booted(): void
    {
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
     * Every inner query drops the global scopes: Student, SectionEnrollment and Section are scoped
     * themselves, and applying the caller's constraint again inside the definition of that constraint
     * narrows the result in a way that cannot be seen from here.
     *
     * @param  Builder<covariant Model>  $query
     * @param  list<int>  $branchIds
     */
    public function constrainToBranches(Builder $query, array $branchIds): void
    {
        $query->whereHas('student', function (Builder $students) use ($branchIds): void {
            $students->withoutGlobalScopes()->whereHas('enrollments', function (Builder $enrollments) use ($branchIds): void {
                $enrollments->withoutGlobalScopes()
                    ->where('status', 'active')
                    ->whereIn('section_id', Section::query()->withoutGlobalScopes()->whereIn('branch_id', $branchIds)->select('id'));
            });
        });
    }

    /**
     * No section grants access to a support request. See the class docblock.
     *
     * @param  Builder<covariant Model>  $query
     * @param  list<int>  $sectionIds
     */
    public function constrainToSections(Builder $query, array $sectionIds): void
    {
        $query->whereKey([]);
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
