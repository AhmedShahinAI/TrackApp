<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\Program;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Imports\Actions\StartValidation;
use App\Modules\Imports\Actions\UploadImport;
use App\Modules\Imports\Jobs\CommitImportChunkJob;
use App\Modules\Imports\Modes\CourseMode;
use App\Modules\Imports\Modes\RegistrarMode;
use App\Modules\Imports\Modes\UserMode;
use App\Modules\Imports\Pipeline\ImportReasons;
use App\Modules\Imports\Pipeline\ResolveRegistrarSection;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * The import wizard's endpoints: upload, validate, preview, commit, resume, and the job itself
 * (contracts/imports.md). Mapping and the error report have their own controllers.
 *
 * ## One controller for the API and the screens
 *
 * The wizard's forms post to these same `api.v1.*` routes, as every dialog since Phase 7 does, and
 * content negotiation decides the response: JSON for an API caller, a redirect to the wizard for a
 * browser. A state refusal (409) reaches a browser as an error on the page it came from — see the
 * ConflictHttpException handler in bootstrap/app.php.
 *
 * ## The state checks are the model's
 *
 * Every 409 below is ImportJob saying which edge of its state machine this is not. The controller
 * does not compare status strings itself, so the screen's buttons and the endpoints cannot disagree
 * about what is possible.
 */
final class ImportJobController extends Controller
{
    private const PREVIEW_ROWS = 100;

    /**
     * GET /imports — the imports list (T239). Scoped by ImportJob's global scope.
     */
    public function index(Request $request): JsonResponse|View
    {
        $jobs = ImportJob::query()
            ->with(['user:id,name_ar,name_en', 'section:id,section_number'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $jobs->getCollection()->map($this->present(...))->all(),
                'meta' => ['current_page' => $jobs->currentPage(), 'last_page' => $jobs->lastPage(), 'total' => $jobs->total()],
            ]);
        }

        return view('imports.index', ['jobs' => $jobs]);
    }

    /**
     * GET /imports/new — step 1 of a central import.
     */
    public function create(): View
    {
        return view('imports.show', ['job' => null, 'step' => 1] + $this->uploadOptions());
    }

    /**
     * POST /imports — step 1, the central upload (T224).
     */
    public function store(Request $request, UploadImport $upload): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            // Not `mimes:` — FileValidator checks the type three ways and names the accepted types in
            // its message. A second, weaker check here would answer first with a vaguer sentence.
            'file' => ['required', 'file'],
            'term_id' => [
                'required', 'integer',
                Rule::exists('academic_terms', 'id')->whereNull('deleted_at')->whereNot('status', 'archived'),
            ],
        ], [
            'file.required' => __('imports.upload.required'),
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $job = $upload->execute($request->file('file'), $actor, (int) $validated['term_id']);

        if (! $request->expectsJson()) {
            return redirect()->route('imports.show', $job);
        }

        return response()->json(['data' => [
            'id' => $job->id,
            'mode' => $job->mode,
            'status' => $job->status,
            'original_filename' => $job->original_filename,
            'total_rows' => $job->total_rows,
            'duplicate_of' => $job->duplicateOf(),
            'suggested_mapping' => $job->file_metadata['suggested_mapping'] ?? [],
        ]], 201);
    }

    /**
     * GET /imports/{id} — the job, and in a browser the wizard at the step its status implies.
     *
     * The JSON carries a top-level `status` beside `data`. The shared poller (queue-poller.js) stops
     * on a terminal status read from the top of the payload, and every other poll target puts it
     * there; nesting it only here would leave the wizard's poller running after the import ended.
     */
    public function show(Request $request, ImportJob $import): JsonResponse|View
    {
        if ($request->expectsJson()) {
            return response()->json(['data' => $this->present($import), 'status' => $import->status]);
        }

        $step = $this->stepFor($import);
        $owner = $request->user()?->can('run', $import) ?? false;

        $data = ['job' => $import, 'step' => $step, 'canRun' => $owner, 'duplicateOf' => $import->duplicateOf()];

        if ($step === 2 || $request->boolean('remap')) {
            $data += ['targets' => $import->modeClass()::TARGETS, 'programs' => $this->programOptions($request)];

            if ($import->mode === RegistrarMode::NAME) {
                $data += ['registrar' => $this->registrarPanel($import)];
            }
        }

        // Rendering the preview to its owner IS the owner fetching it. A non-owner — a Super Admin
        // following a colleague's import — sees it without satisfying the commit gate on their behalf.
        if ($step >= 4 && $import->canBePreviewed()) {
            $data += ['preview' => $this->previewData($import, markFetched: $owner)];
        }

        return view('imports.show', $data + $this->uploadOptions());
    }

    /**
     * POST /imports/{id}/validate — 202, and the work is queued (T229).
     */
    public function startValidation(Request $request, ImportJob $import, StartValidation $start): JsonResponse|RedirectResponse
    {
        $job = $start->execute($import);

        return $this->accepted($request, $job, ['id' => $job->id, 'status' => $job->status]);
    }

    /**
     * GET /imports/{id}/preview — counts and the first 100 rows, read from staging (T230).
     */
    public function preview(Request $request, ImportJob $import): JsonResponse
    {
        if (! $import->canBePreviewed()) {
            throw new ConflictHttpException(__('imports.conflicts.not_validated'));
        }

        $preview = $this->previewData($import, markFetched: true);

        return response()->json([
            'data' => ['summary' => $preview['summary'], 'rows' => $preview['rows']],
            'meta' => ['per_page' => self::PREVIEW_ROWS, 'total' => $preview['summary']['total_rows'] ?? 0],
        ]);
    }

    /**
     * POST /imports/{id}/commit — the operator's confirmation, and the only door to the core tables
     * (T233, FR-060).
     */
    public function commit(Request $request, ImportJob $import): JsonResponse|RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'accepted'],

            /*
             * The create-or-skip choices are fixed at the mapping step and nowhere else.
             *
             * The contract once let the commit override them. But validation reads those choices —
             * it resolves a new course's programme and a new section's instructor only when "create"
             * was chosen — and the preview's counts are computed from them. A commit run on different
             * choices writes what nobody validated and nobody saw: an orphan course with no programme,
             * an account for an empty email. Changing a choice means mapping again, which re-validates.
             */
            'options' => ['prohibited'],
        ], [
            'options.prohibited' => __('imports.conflicts.options_at_mapping'),
        ]);

        if (! $import->canBeCommitted()) {
            throw new ConflictHttpException($import->status === 'validated'
                ? __('imports.conflicts.preview_not_seen')
                : __('imports.conflicts.not_validated'));
        }

        /*
         * Claimed with a compare-and-set, not checked-then-saved.
         *
         * Two requests a millisecond apart — a double click, two tabs — both pass canBeCommitted()
         * on the copy they loaded. Saved separately, both would dispatch a chain over the same rows.
         * The UPDATE's WHERE is the check: only one request can move the row out of `validated`.
         *
         * `link_only` is decided here, once. Asked again per chunk, it changed halfway through a
         * commit the moment another import of the same file completed (FR-067).
         */
        $claimed = ImportJob::query()->withoutGlobalScopes()
            ->whereKey($import->id)
            ->where('status', 'validated')
            ->whereNotNull('preview_fetched_at')
            ->update([
                'status' => 'committing',
                /*
                 * Link-only replays a roster without creating anything (FR-067). Not for the course
                 * and staff modes: they are idempotent by construction — an existing code is matched,
                 * a held email is refused — so re-running the same file can only create what the last
                 * run did not. Frozen to link-only, a staff file whose every row was refused the first
                 * time (a branch not yet set up) could never create its accounts once it was fixed.
                 */
                'link_only' => ! in_array($import->mode, [CourseMode::NAME, UserMode::NAME], true)
                    && $import->duplicateOf() !== null,
                'commit_requested_at' => now(),
                'queued_at' => now(),
                'heartbeat_at' => null,
                'processed' => 0,
                'imported' => 0,
                'duplicates' => 0,
                'errors' => 0,
                'last_committed_row_id' => null,
                'failure_reason' => null,
                'finished_at' => null,
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            throw new ConflictHttpException(__('imports.conflicts.not_validated'));
        }

        // Outside any transaction, for the reason StartValidation gives.
        CommitImportChunkJob::dispatch($import->id);

        $import->refresh();

        return $this->accepted($request, $import, ['id' => $import->id, 'status' => $import->status]);
    }

    /**
     * POST /imports/{id}/resume — continue a failed commit from its checkpoint (T234, FR-065).
     *
     * Also continues a commit whose worker died (ImportJob::canBeResumed). Claimed with a
     * compare-and-set for the same reason as commit(): two resumes must not start two chains. A dead
     * worker's job is matched on the exact heartbeat that made it look dead, so a worker that comes
     * back to life and writes a heartbeat wins, and the resume is refused.
     */
    public function resume(Request $request, ImportJob $import): JsonResponse|RedirectResponse
    {
        if (! $import->canBeResumed()) {
            throw new ConflictHttpException(__('imports.conflicts.not_failed'));
        }

        $from = (int) $import->last_committed_row_id;
        $previous = $import->status;

        $claimed = DB::transaction(function () use ($import, $from, $previous): bool {
            $query = ImportJob::query()->withoutGlobalScopes()->whereKey($import->id)->where('status', $previous);

            if ($previous === 'committing') {
                $query->where('heartbeat_at', $import->getRawOriginal('heartbeat_at'));
            }

            $updated = $query->update([
                'status' => 'committing',
                'queued_at' => now(),
                'heartbeat_at' => null,
                'failure_reason' => null,
                'updated_at' => now(),
            ]);

            if ($updated !== 1) {
                return false;
            }

            app(Auditor::class)->record('import.resumed', $import, ['status' => $previous], [
                'file' => $import->original_filename,
                'mode' => $import->mode,
                'resuming_from_row_id' => $from,
            ]);

            return true;
        });

        if (! $claimed) {
            throw new ConflictHttpException(__('imports.conflicts.not_failed'));
        }

        CommitImportChunkJob::dispatch($import->id);

        $import->refresh();

        return $this->accepted($request, $import, [
            'id' => $import->id,
            'status' => $import->status,
            'resuming_from_row_id' => $from,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(ImportJob $job): array
    {
        $waiting = $job->isWaitingForWorker();

        return [
            'id' => $job->id,
            'mode' => $job->mode,
            'section_id' => $job->section_id,
            'section_number' => $job->section?->section_number,
            'term_id' => $job->term_id,
            'user_id' => $job->user_id,
            'user_name' => $job->user?->name_ar,
            'original_filename' => $job->original_filename,
            'status' => $job->status,
            'total_rows' => $job->total_rows,
            'processed' => $job->processed,
            'imported' => $job->imported,
            'duplicates' => $job->duplicates,
            'errors' => $job->errors,
            'last_committed_row_id' => $job->last_committed_row_id,
            'failure_reason' => $job->failure_reason,
            'started_at' => $job->started_at?->toIso8601String(),
            'finished_at' => $job->finished_at?->toIso8601String(),
            'created_at' => $job->created_at?->toIso8601String(),
            'progress_percent' => $job->progressPercent(),
            'chunk' => $job->chunkProgress(),
            'mapping' => $job->mapping,
            'options' => $job->options,

            // C-13 — waiting is not failing, and the screen must be able to say which.
            'waiting' => $waiting,
            'waiting_message' => $waiting ? __('imports.queue.waiting') : null,
        ];
    }

    /**
     * The preview, as the endpoint returns it and as step 4 renders it.
     *
     * @return array{summary: array<string, mixed>, rows: list<array<string, mixed>>}
     */
    private function previewData(ImportJob $job, bool $markFetched): array
    {
        if ($markFetched && $job->preview_fetched_at === null && $job->status === 'validated') {
            $job->forceFill(['preview_fetched_at' => now()])->save();
        }

        $rows = ImportRow::query()
            ->where('import_job_id', $job->id)
            ->orderBy('row_number')
            ->orderBy('id')
            ->limit(self::PREVIEW_ROWS)
            ->get()
            ->map(static fn (ImportRow $row): array => [
                'row_number' => $row->row_number,

                // The validation outcome, not the commit one: the preview answers "what will
                // happen", and a committed row's answer was its validation status.
                'status' => in_array($row->status, ['committed', 'skipped'], true)
                    ? (ImportReasons::hasProblem($row->reasons) ? 'warning' : 'valid')
                    : $row->status,
                'values' => $row->raw,
                'reasons' => array_map(ImportReasons::localise(...), $row->reasons ?? []),
            ])
            ->all();

        return ['summary' => $job->summary ?? [], 'rows' => $rows];
    }

    /**
     * Which of the six steps a job's status puts the wizard on.
     */
    private function stepFor(ImportJob $job): int
    {
        return match ($job->status) {
            'uploaded', 'mapped' => 2,
            'validating' => 3,
            'validated' => 4,
            'committing' => 5,
            'completed' => 6,
            'failed' => $job->commit_requested_at !== null ? 5 : 2,
            default => 1,
        };
    }

    /**
     * @return array{terms: array<int|string, string>, maxFileMb: int, maxRows: int}
     */
    private function uploadOptions(): array
    {
        $locale = app()->getLocale();

        $terms = AcademicTerm::query()
            ->where('status', '!=', 'archived')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get()
            ->mapWithKeys(fn (AcademicTerm $term): array => [$term->id => Format::term($term)])
            ->all();

        return [
            'terms' => $terms,
            'maxFileMb' => Settings::int(Settings::IMPORT_MAX_FILE_MB),
            'maxRows' => Settings::int(Settings::IMPORT_MAX_ROWS),
        ];
    }

    /**
     * T334 — the registrar confirmation panel: each metadata value's resolution in the reader's
     * language. Resolved here rather than in the template, so the panel is a handful of lookups by
     * primary key, made once.
     *
     * @return array<string, mixed>
     */
    private function registrarPanel(ImportJob $job): array
    {
        $locale = app()->getLocale();
        $resolved = $job->registrarResolution() ?? [];
        $name = static fn (?object $row, string $column): ?string => $row === null ? null : ($row->{$column.'_'.$locale} ?? $row->{$column.'_ar'});

        $branch = Branch::query()->find($resolved['branch_id'] ?? null);
        $course = Course::query()->find($resolved['course_id'] ?? null);
        $sectionId = $course === null ? null : ResolveRegistrarSection::existingSection(
            (int) $job->term_id, (int) ($resolved['branch_id'] ?? 0), (int) $course->id, (string) ($resolved['section_number'] ?? ''),
        );
        $label = static fn (string $key, ?string $value): ?string => $value === null ? null
            : (collect(Settings::options($key))->firstWhere('value', $value)[$locale] ?? $value);

        return [
            'branch' => $branch === null ? null : $name($branch, 'campus_name').' ('.__('sections.gender.'.$branch->gender).')',
            'course' => $course === null ? __('imports.registrar.course_to_choose') : $course->code.' · '.$name($course, 'name'),
            'course_candidates' => Course::query()->whereIn('id', $resolved['course_candidates'] ?? [])->orderBy('code')->get()
                ->mapWithKeys(fn (Course $c): array => [$c->id => $c->code.' · '.$name($c, 'name')])->all(),
            'section' => ($resolved['section_number'] ?? '').' — '.__($sectionId === null ? 'imports.registrar.section_new' : 'imports.registrar.section_existing'),
            'section_exists' => $sectionId !== null,

            // The new section's programme choices: the course's own ACTIVE programmes (review M1) —
            // or, while the course is still to be chosen, those of every candidate. The server
            // accepts only the chosen course's (ImportMappingController::registrarProgramIds).
            'programs' => Program::query()
                ->where('is_active', true)
                ->whereHas('courses', fn ($q) => $q->whereIn('courses.id', $course !== null
                    ? [$course->id]
                    : ($resolved['course_candidates'] ?? [])))
                ->orderBy('code')
                ->get(['id', 'code', 'name_ar', 'name_en'])
                ->mapWithKeys(fn (Program $p): array => [$p->id => $p->code.' · '.$name($p, 'name')])
                ->all(),
            'degree' => $label(Settings::SECTION_DEGREES, $resolved['degree'] ?? null) ?? __('imports.registrar.default_value'),
            'activity' => $label(Settings::SECTION_ACTIVITIES, $resolved['activity'] ?? null) ?? __('imports.registrar.default_value'),
            'term' => $job->term === null ? null : Format::term($job->term),
            'instructor' => $name(User::query()->find($job->instructor_id), 'name'),
        ];
    }

    /**
     * The active programmes of the caller's organization a created course may join, for the mapping
     * step's choice. ImportMappingController accepts exactly this set.
     *
     * @return array<int|string, string>
     */
    private function programOptions(Request $request): array
    {
        $locale = app()->getLocale();

        return Program::query()
            ->where('organization_id', $request->user()?->organization_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Program $program): array => [
                $program->id => $program->code.' — '.($program->{'name_'.$locale} ?? $program->name_ar),
            ])
            ->all();
    }

    /**
     * 202 for an API caller; back to the wizard for a browser, which shows the queued step.
     *
     * @param  array<string, mixed>  $data
     */
    private function accepted(Request $request, ImportJob $job, array $data): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['data' => $data], 202);
        }

        return redirect()->route('imports.show', $job);
    }
}
