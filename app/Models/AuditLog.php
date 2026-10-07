<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Authorization\Concerns\BelongsToBranchScope;
use App\Modules\Authorization\Concerns\BelongsToSectionScope;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * T040 - a read model over the audit log.
 *
 * This class is the second of the three immutability layers (constitution II, research.md R-02). The
 * first is the schema: no updated_at column, so there is nowhere to record a change. The third is
 * the engine: the application database user holds no UPDATE or DELETE on the table.
 *
 * This layer closes the application path. Every mutating entry point Eloquent offers is overridden to
 * throw, and $guarded is the whole attribute set so nothing can be mass-assigned. Overriding rather
 * than merely omitting matters: a developer calling $log->delete() next month gets an immediate,
 * explanatory failure instead of a query that only fails in production, where the grant exists.
 *
 * The only writer is the Auditor service, which inserts through the query builder.
 */
class AuditLog extends Model implements ConstrainableToBranches, ConstrainableToSections
{
    use BelongsToBranchScope, BelongsToSectionScope;

    public const UPDATED_AT = null;

    /**
     * The entity types an audit entry can point at that are themselves scoped.
     *
     * Kept as a map rather than inferred, because `entity_type` stores a class basename written by
     * the Auditor and an entry pointing at a Branch or a Setting has no section to resolve into.
     * Anything absent from this map is visible only to its own author, which is the conservative
     * answer for a type nobody has thought about yet.
     *
     * @var array<string, string>
     */
    private const SCOPED_ENTITIES = [
        'Section' => 'id',
        'SectionEnrollment' => 'section_id',
    ];

    /**
     * Nothing is mass-assignable. The Auditor does not use this model to write.
     *
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * T146 — audit visibility, per contracts/audit.md.
     *
     * > a coordinator sees only entries whose entity resolves into their branches, plus their own
     * > actions.
     *
     * "Plus their own actions" is not a convenience: without it a coordinator loses sight of what
     * they themselves did the moment it concerned something outside a section — changing their own
     * password, say — and an accountability trail the actor cannot read is not a trail.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>  $branchIds
     */
    public function constrainToBranches(Builder $query, array $branchIds): void
    {
        $this->constrainToEntitiesIn(
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
        $this->constrainToEntitiesIn($query, $sectionIds);
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>|Builder<covariant \Illuminate\Database\Eloquent\Model>  $sectionIds
     */
    private function constrainToEntitiesIn(Builder $query, array|Builder $sectionIds): void
    {
        $actorId = \Illuminate\Support\Facades\Auth::id();

        $query->where(function (Builder $outer) use ($sectionIds, $actorId): void {
            // whereNull when there is no actor: `where(col, null)` compiles to `= NULL`, which is
            // never true, so a console-written entry would silently drop out rather than being
            // excluded deliberately.
            $actorId === null
                ? $outer->whereNull($this->qualifyColumn('user_id'))
                : $outer->where($this->qualifyColumn('user_id'), $actorId);

            foreach (self::SCOPED_ENTITIES as $type => $column) {
                $outer->orWhere(function (Builder $inner) use ($type, $column, $sectionIds): void {
                    $inner->where($this->qualifyColumn('entity_type'), $type)
                        ->whereIn(
                            $this->qualifyColumn('entity_id'),
                            $column === 'id'
                                ? $sectionIds
                                : \Illuminate\Support\Facades\DB::table('section_enrollments')
                                    ->select('id')
                                    ->whereIn($column, $sectionIds),
                        );
                });
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        throw new LogicException(self::refusal('save'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException(self::refusal('update'));
    }

    public function delete(): bool
    {
        throw new LogicException(self::refusal('delete'));
    }

    public function forceDelete(): bool
    {
        throw new LogicException(self::refusal('forceDelete'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    private static function refusal(string $method): string
    {
        return sprintf(
            'The audit log is append-only: %s() is not available on %s. '
            .'If a recorded entry is wrong, record a correcting entry - do not rewrite history.',
            $method,
            self::class,
        );
    }
}
