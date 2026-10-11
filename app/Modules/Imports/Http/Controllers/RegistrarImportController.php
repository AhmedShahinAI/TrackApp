<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\ImportJob;
use App\Models\User;
use App\Modules\Imports\Actions\UploadImport;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Support\Format;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * T331 — the registration unit's own export (FR-096 to FR-101, contracts/imports.md).
 *
 * ## The operator supplies what the file cannot
 *
 * The export carries no term and no instructor (FR-101), so both are required here. The term is
 * validated exactly as the central upload's; the instructor must be an active INSTRUCTOR of the
 * caller's organization — the account the section will be created under if it does not exist yet.
 *
 * ## Parsed and resolved at upload
 *
 * UploadImport reads the metadata block and resolves branch, course and existing section before it
 * returns, so the 201 carries what the file was understood to mean, and the confirmation step shows
 * it before a single row is read. A file with no block, an unresolvable campus or an unknown course
 * is a 422 on `file` here, and nothing is kept (contracts/imports.md, the registrar 422s).
 *
 * After upload the job is an ordinary import in `registrar` mode: the same mapping, validate,
 * preview, commit and resume endpoints, the same guarantees (FR-102).
 */
final class RegistrarImportController extends Controller
{
    /**
     * GET /imports/new/registrar — step 1 of a registrar import.
     */
    public function create(Request $request): View
    {
        return view('imports.show', [
            'job' => null,
            'step' => 1,
            'mode' => 'registrar',
            'terms' => $this->terms(),
            'instructors' => $this->instructors($request),
            'maxFileMb' => Settings::int(Settings::IMPORT_MAX_FILE_MB),
            'maxRows' => Settings::int(Settings::IMPORT_MAX_ROWS),
        ]);
    }

    /**
     * POST /api/v1/imports/registrar.
     */
    public function store(Request $request, UploadImport $upload): JsonResponse|RedirectResponse
    {
        $organizationId = $request->user()?->organization_id;

        $validated = $request->validate([
            // FileValidator checks name, MIME and content, and names the accepted types itself.
            'file' => ['required', 'file'],
            'term_id' => [
                'required', 'integer',
                Rule::exists('academic_terms', 'id')->whereNull('deleted_at')->whereNot('status', 'archived'),
            ],
            'instructor_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('organization_id', $organizationId)->where('is_active', true),
                // An instructor, not any active account: the section is created under them.
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (! User::query()->role('INSTRUCTOR')->whereKey((int) $value)->exists()) {
                        $fail('imports.registrar.instructor_invalid')->translate();
                    }
                },
            ],
        ], [
            'file.required' => __('imports.upload.required'),
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $job = $upload->execute($request->file('file'), $actor, (int) $validated['term_id'], null, [
            'mode' => 'registrar',
            'instructor_id' => (int) $validated['instructor_id'],
        ]);

        if (! $request->expectsJson()) {
            return redirect()->route('imports.show', $job);
        }

        return response()->json(['data' => self::present($job)], 201);
    }

    /**
     * The contract's 201 shape: the common fields, plus what the block said and what it resolved to.
     *
     * @return array<string, mixed>
     */
    public static function present(ImportJob $job): array
    {
        $resolved = $job->registrarResolution() ?? [];

        return [
            'id' => $job->id,
            'mode' => $job->mode,
            'status' => $job->status,
            'original_filename' => $job->original_filename,
            'total_rows' => $job->total_rows,
            'term_id' => $job->term_id,
            'instructor_id' => $job->instructor_id,
            'file_metadata' => $job->file_metadata['registrar'] ?? [],
            'resolved' => [
                'branch_id' => $resolved['branch_id'] ?? null,
                'course_id' => $resolved['course_id'] ?? null,
                'course_candidates' => $resolved['course_candidates'] ?? [],
                'section_id' => $resolved['section_id'] ?? null,
                'degree' => $resolved['degree'] ?? null,
                'activity' => $resolved['activity'] ?? null,
            ],
            'duplicate_of' => $job->duplicateOf(),
            'suggested_mapping' => $job->file_metadata['suggested_mapping'] ?? [],
        ];
    }

    /** @return array<int, string> */
    private function terms(): array
    {
        return AcademicTerm::query()
            ->where('status', '!=', 'archived')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get()
            ->mapWithKeys(static fn (AcademicTerm $term): array => [$term->id => Format::term($term)])
            ->all();
    }

    /**
     * The active instructors of the caller's organization — the set `instructor_id` accepts.
     *
     * @return array<int, string>
     */
    private function instructors(Request $request): array
    {
        $locale = app()->getLocale();

        return User::query()->role('INSTRUCTOR')
            ->where('organization_id', $request->user()?->organization_id)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->toBase()
            ->get(['users.id', 'name_ar', 'name_'.$locale.' as name'])
            ->mapWithKeys(static fn (object $u): array => [$u->id => $u->name ?? $u->name_ar])
            ->all();
    }
}
