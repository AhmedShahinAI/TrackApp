<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use App\Modules\Authorization\Concerns\BelongsToBranchScope;
use App\Modules\Authorization\Concerns\BelongsToSectionScope;
use App\Modules\Authorization\Contracts\ConstrainableToBranches;
use App\Modules\Authorization\Contracts\ConstrainableToSections;
use App\Modules\Imports\Modes\CentralMode;
use App\Modules\Imports\Modes\CourseMode;
use App\Modules\Imports\Modes\RegistrarMode;
use App\Modules\Imports\Modes\SectionMode;
use App\Modules\Imports\Modes\UserMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * An import job — one run of the six-step wizard, and the whole of its state (T222).
 *
 * Created in US2 because T146 requires the five scoped models to exist together: a model added later
 * inherits whatever scoping the author remembers, which is the failure mode global scopes exist to
 * prevent. US5 adds the behaviour around it; the pipeline itself lives in app/Modules/Imports.
 *
 * ## The status is a state machine, and the endpoints are its edges
 *
 *     uploaded → mapped → validating → validated → committing → completed
 *                  ↑______________________|            ↓
 *                  (mapping may be redone)            failed → committing (resume)
 *
 * Each endpoint in contracts/imports.md refuses with 409 from any state that is not its source. The
 * checks live on this model, as named predicates, so the controller, the queued jobs and the screens
 * ask the same question in the same words rather than each comparing status strings.
 *
 * @property int $id
 * @property int $user_id
 * @property string $mode
 * @property int|null $section_id
 * @property int|null $term_id
 * @property string $original_filename
 * @property string $file_path
 * @property string $file_hash
 * @property string $status
 * @property array<string, mixed>|null $file_metadata
 * @property array<string, string|null>|null $mapping
 * @property array<string, bool|int|null>|null $options
 * @property array<string, mixed>|null $summary
 * @property bool $link_only
 * @property int $total_rows
 * @property int $processed
 * @property int $imported
 * @property int $duplicates
 * @property int $errors
 * @property int|null $last_committed_row_id
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $preview_fetched_at
 * @property \Illuminate\Support\Carbon|null $commit_requested_at
 * @property \Illuminate\Support\Carbon|null $queued_at
 * @property \Illuminate\Support\Carbon|null $heartbeat_at
 */
#[Fillable([
    'user_id', 'mode', 'section_id', 'term_id', 'instructor_id', 'file_metadata', 'original_filename',
    'file_path', 'file_hash', 'mapping', 'options', 'summary', 'status',
])]
class ImportJob extends Model implements ConstrainableToBranches, ConstrainableToSections
{
    use Auditable, BelongsToBranchScope, BelongsToSectionScope;

    /** The seven states, in the order a successful import passes through them. */
    public const STATUSES = ['uploaded', 'mapped', 'validating', 'validated', 'committing', 'completed', 'failed'];

    /**
     * Each state's x-track-status-badge tone. The badge always carries the state's own label beside
     * the colour (FR-077); this only picks which of the fixed tones it wears.
     */
    public const BADGES = [
        'uploaded' => 'draft',
        'mapped' => 'draft',
        'validating' => 'running',
        'validated' => 'pending',
        'committing' => 'running',
        'completed' => 'completed',
        'failed' => 'failed',
    ];

    /**
     * Silence after which a worker that had the job is presumed dead. Longer than the chunk job's
     * timeout and than one 500-row validation batch, each of which writes a heartbeat.
     */
    public const STALE_AFTER_MINUTES = 15;

    /** FR-064 — the all-or-nothing unit of a commit, and therefore of a resume. */
    public const CHUNK_SIZE = 500;

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

    /** @return BelongsTo<AcademicTerm, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }

    /** @return HasMany<ImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    /** @return HasMany<ImportError, $this> */
    public function errorLines(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    /**
     * The earlier, completed import of the same file for the same term (FR-067), if there is one.
     *
     * Computed rather than stored. "Was this file already imported?" is a question about the other
     * jobs, and its answer changes when one of them completes; a column written at upload would be
     * right at upload and wrong an hour later.
     *
     * Any other completed job counts, whichever was uploaded first. "Uploaded earlier" was the first
     * rule, and it let the second upload of a file import in full when the first upload happened to
     * finish later. This is the warning; the commit's decision is frozen in `link_only` when the
     * operator confirms, so a job completing elsewhere cannot change an import already under way.
     *
     * Unscoped, deliberately: the warning exists to stop a second Super Admin re-running the first
     * one's roster. Constrained to the caller's own visible jobs, it would say nothing in exactly
     * the case it is for.
     */
    public function duplicateOf(): ?int
    {
        /*
         * The course and staff modes carry no term — a catalogue and a staff list are not a term's —
         * so for them "the same file" is the same file in the same organization (FR-067 through
         * FR-106). Every other mode is compared within its term, and has one.
         */
        $termless = in_array($this->mode, [CourseMode::NAME, UserMode::NAME], true);

        if ($this->term_id === null && ! $termless) {
            return null;
        }

        $id = self::query()
            ->withoutGlobalScopes()
            ->where('file_hash', $this->file_hash)
            ->when(
                $termless,
                fn ($q) => $q->whereNull('term_id')->whereIn('user_id', User::query()->withTrashed()
                    ->where('organization_id', User::query()->withTrashed()->whereKey($this->user_id)->value('organization_id'))
                    ->select('id')),
                fn ($q) => $q->where('term_id', $this->term_id),
            )
            ->where('mode', $this->mode)
            // A section list is "the same file" only for the same section: two instructors may
            // well upload identical two-column lists for different sections.
            ->when(
                $this->section_id !== null,
                fn ($q) => $q->where('section_id', $this->section_id),
                fn ($q) => $q->whereNull('section_id'),
            )
            ->where('status', 'completed')
            ->whereKeyNot($this->getKey())
            ->orderByDesc('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * The column contract this job's mode uses (FR-052, FR-053, FR-098).
     *
     * @return class-string<CentralMode|SectionMode|RegistrarMode|CourseMode|UserMode>
     */
    public function modeClass(): string
    {
        return match ($this->mode) {
            SectionMode::NAME => SectionMode::class,
            RegistrarMode::NAME => RegistrarMode::class,
            CourseMode::NAME => CourseMode::class,
            UserMode::NAME => UserMode::class,
            default => CentralMode::class,
        };
    }

    /**
     * A registrar job's resolution from its metadata block (ResolveRegistrarSection), or null.
     *
     * @return array{branch_id: int, course_id: int|null, course_candidates: list<int>, section_id: int|null, section_number: string, degree: string|null, activity: string|null}|null
     */
    public function registrarResolution(): ?array
    {
        return $this->mode === RegistrarMode::NAME ? ($this->file_metadata['resolved'] ?? null) : null;
    }

    public function canBeMapped(): bool
    {
        // A mapping may be corrected after validation and before commit — the preview is where an
        // operator discovers they paired the wrong column, and sending them back to re-upload would
        // be punishing the discovery. A failed *validation* returns here too: nothing was written,
        // and the honest next step is to check the mapping and try again.
        return in_array($this->status, ['uploaded', 'mapped', 'validated'], true)
            || ($this->status === 'failed' && $this->commit_requested_at === null)
            || ($this->status === 'validating' && $this->workerWentQuiet());
    }

    public function canBeValidated(): bool
    {
        return $this->status === 'mapped';
    }

    public function canBePreviewed(): bool
    {
        return in_array($this->status, ['validated', 'committing', 'completed'], true)
            || ($this->status === 'failed' && $this->commit_requested_at !== null);
    }

    public function canBeCommitted(): bool
    {
        return $this->status === 'validated' && $this->preview_fetched_at !== null;
    }

    /**
     * Only a failed *commit* resumes. A failed validation has staged nothing worth keeping, and its
     * honest remedy is to validate again from the mapping step.
     *
     * A commit whose worker died is failed in all but name: a crash between a chunk's transaction and
     * the dispatch of the next one leaves the job `committing` with nothing queued, and no failed()
     * handler runs for a process that no longer exists. Without this branch that import could never
     * finish (FR-065).
     */
    public function canBeResumed(): bool
    {
        return ($this->status === 'failed' && $this->commit_requested_at !== null)
            || ($this->status === 'committing' && $this->workerWentQuiet());
    }

    /**
     * A worker picked the job up and has said nothing for longer than any chunk or validation batch
     * can take — so it is gone, not slow.
     *
     * Distinct from isWaitingForWorker(): there the heartbeat predates the queueing, meaning nobody
     * has started, and the job is still safely in the queue. Here somebody started and stopped.
     */
    public function workerWentQuiet(): bool
    {
        return $this->heartbeat_at !== null
            && ($this->queued_at === null || $this->heartbeat_at->gte($this->queued_at))
            && $this->heartbeat_at->lt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    /**
     * C-13 — queued, and no worker has touched it since.
     *
     * A heartbeat older than the queueing means the work is waiting, not failing. The screen says so
     * in those words, because "processing" with a bar stuck at zero reads as broken and an operator
     * who thinks an import is broken uploads it again.
     */
    public function isWaitingForWorker(): bool
    {
        if (! in_array($this->status, ['validating', 'committing'], true) || $this->queued_at === null) {
            return false;
        }

        return $this->heartbeat_at === null || $this->heartbeat_at->lt($this->queued_at);
    }

    public function progressPercent(): int
    {
        if ($this->status === 'completed') {
            return 100;
        }

        $total = (int) $this->total_rows;

        if ($total === 0) {
            return 0;
        }

        return (int) min(100, floor(((int) $this->processed / $total) * 100));
    }

    /**
     * The chunk the commit is on and how many there are, for "الدفعة 2 من 4".
     *
     * @return array{current: int, total: int}
     */
    public function chunkProgress(): array
    {
        $total = max(1, (int) ceil((int) $this->total_rows / self::CHUNK_SIZE));
        $current = min($total, intdiv((int) $this->processed, self::CHUNK_SIZE) + 1);

        return ['current' => $current, 'total' => $total];
    }

    /**
     * The option the operator chose for a create-or-skip decision (FR-058). Defaults to skip: an
     * import that silently invents courses and accounts is the worse surprise.
     */
    public function option(string $key): bool
    {
        return (bool) (($this->options ?? [])[$key] ?? false);
    }

    /**
     * T224 — the upload is the audited moment, and it already happens through create().
     *
     * contracts/audit.md names `import.uploaded`, not `import.created`, and the trait's created hook
     * carries exactly the payload the contract asks for: mode, term, section and filename. Mapping
     * the event here keeps the entry inside the upload's own transaction, which an explicit
     * Auditor call after the create would also do — but then the trait would write a second,
     * off-contract `import.created` beside it.
     */
    public function auditActionFor(string $event): string
    {
        return $this->auditEntityKey().'.'.($event === 'created' ? 'uploaded' : $event);
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

    /**
     * `status` is deliberately absent.
     *
     * The status moves seven times in a normal import, mostly inside queued jobs, and the trait would
     * write an `import.updated` for each — a key the contract does not define and a trail nobody can
     * read. The moments that matter are recorded under their own names by the endpoints that cause
     * them: `import.committed`, `import.resumed`, `import.error_report_downloaded`. With status gone,
     * nothing in this list changes after upload, so no update ever reaches the log.
     *
     * @return list<string>
     */
    public function auditedAttributes(): array
    {
        return ['mode', 'section_id', 'term_id', 'original_filename'];
    }

    /**
     * contracts/imports.md: a registrar upload is audited "with the filename and the parsed
     * metadata". The block and the instructor the operator chose are added to the CREATION entry
     * only — recognised by the full attribute set, which carries the id; an update's payload is just
     * its changed columns — so file_metadata can never turn a later save into an `import.updated`
     * the contract does not list.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditPayload(array $attributes): array
    {
        $payload = array_intersect_key($attributes, array_flip($this->auditedAttributes()));

        if (($attributes['mode'] ?? null) === RegistrarMode::NAME && array_key_exists('id', $attributes)) {
            $metadata = is_string($attributes['file_metadata'] ?? null)
                ? json_decode($attributes['file_metadata'], true)
                : ($attributes['file_metadata'] ?? null);

            $payload['metadata'] = is_array($metadata) ? ($metadata['registrar'] ?? null) : null;
            $payload['instructor_id'] = $attributes['instructor_id'] ?? null;
        }

        return $payload;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'mapping' => 'array',
            'options' => 'array',
            'summary' => 'array',
            'link_only' => 'boolean',
            'file_metadata' => 'array',
            'total_rows' => 'integer',
            'processed' => 'integer',
            'imported' => 'integer',
            'duplicates' => 'integer',
            'errors' => 'integer',
            'last_committed_row_id' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'preview_fetched_at' => 'datetime',
            'commit_requested_at' => 'datetime',
            'queued_at' => 'datetime',
            'heartbeat_at' => 'datetime',
        ];
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
