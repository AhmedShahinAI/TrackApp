<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use App\Modules\Authorization\Concerns\BelongsToBranchScope;
use App\Modules\Authorization\Concerns\BelongsToSectionScope;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * An import job.
 *
 * Created here rather than in US5 because T146 requires the five scoped models to exist together:
 * a model added later inherits whatever scoping the author remembers, which is the failure mode
 * global scopes exist to prevent. The columns match the `import_jobs` migration; the behaviour that
 * runs an import — mapping, validation, preview, commit — belongs to US5 and is not here.
 */
#[Fillable([
    'user_id', 'mode', 'section_id', 'term_id', 'instructor_id', 'original_filename',
    'file_path', 'file_hash', 'mapping', 'options', 'status',
])]
class ImportJob extends Model implements ConstrainableToBranches, ConstrainableToSections
{
    use Auditable, BelongsToBranchScope, BelongsToSectionScope;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * T167 — the contract names this entity `import`, not `import_job`.
     *
     * contracts/audit.md lists 38 action keys and a Pest test treats that list as closed. The
     * trait's default is snake(class_basename), which would emit `import_job.created` — a key the
     * contract does not contain, and therefore an action no audit screen has a label or a filter
     * for.
     */
    public function auditEntityKey(): string
    {
        return 'import';
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return ['mode', 'section_id', 'term_id', 'original_filename', 'status'];
    }

    /**
     * An import reaches its branch through the section it targets.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $branchIds
     */
    public function constrainToBranches(Builder $query, array $branchIds): void
    {
        $this->constrainToSectionsOrOwn(
            $query,
            Section::query()->withoutGlobalScopes()->whereIn('branch_id', $branchIds)->select('id'),
        );
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $sectionIds
     */
    public function constrainToSections(Builder $query, array $sectionIds): void
    {
        $this->constrainToSectionsOrOwn($query, $sectionIds);
    }

    /**
     * Jobs targeting an in-scope section, plus the caller's own jobs.
     *
     * The second half exists because `section_id` is nullable: a registrar, course, or user import
     * targets a whole term and names no section. Without it the person who ran such an import could
     * not see the job they just started, which reads as the upload having failed.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>|Builder<covariant \Illuminate\Database\Eloquent\Model>  $sectionIds
     */
    private function constrainToSectionsOrOwn(Builder $query, array|Builder $sectionIds): void
    {
        $query->where(function (Builder $outer) use ($sectionIds): void {
            $outer->whereIn($this->qualifyColumn('section_id'), $sectionIds)

                /*
                 * Gated on section_id being null, not an open OR.
                 *
                 * Unqualified, "or it is mine" re-admitted a job that names a section the caller can
                 * no longer see — an instructor whose grant was revoked could still read the import
                 * they once ran against that section, including its row counts. The exception exists
                 * only for the modes that target a whole term and name no section at all.
                 */
                ->orWhere(function (Builder $own): void {
                    $own->whereNull($this->qualifyColumn('section_id'))
                        ->where($this->qualifyColumn('user_id'), Auth::id());
                });
        });
    }
}
