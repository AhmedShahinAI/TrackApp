<?php

declare(strict_types=1);

namespace App\Modules\Imports\Http\Controllers;

use App\Models\Course;
use App\Models\ImportJob;
use App\Modules\Imports\Actions\StartValidation;
use App\Modules\Imports\Modes\CentralMode;
use App\Modules\Imports\Modes\CourseMode;
use App\Modules\Imports\Modes\RegistrarMode;
use App\Modules\Imports\Modes\SectionMode;
use App\Modules\Imports\Modes\UserMode;
use App\Modules\Imports\Pipeline\ImportReasons;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T226 — PUT /imports/{id}/mapping: the operator's confirmed column mapping (FR-056, FR-058).
 *
 * ## Two refusals, both naming what to fix
 *
 *   - a required target left unmapped — the message names the target, in the reader's language;
 *   - a mapped source column that is not in the file's header row — which an API caller can send and
 *     a stale browser tab can too, and which would otherwise validate every row as "missing".
 *
 * ## A file that is plainly not a central roster says so
 *
 * When most required targets have no column at all, the file is almost certainly a different shape
 * — in this college, usually the registration unit's own export. A list of eight "required" errors
 * is accurate and useless; the operator is also told which import the file probably belongs to
 * (T221).
 *
 * ## The options take effect at commit
 *
 * `create_missing_courses` and `create_missing_instructors` are recorded here, shown again on the
 * preview, and acted on only by the commit (contracts/imports.md). Both default to skip.
 *
 * `new_course_program_id` is the third choice the first two imply. A created course's section must
 * belong to a programme (FR-090) and the central file names none, so "create unknown courses" is
 * only meaningful if the operator also says which programme they join. Without it, a would-be new
 * section is reported as unresolvable rather than attached to a programme picked by the importer.
 */
final class ImportMappingController extends Controller
{
    /**
     * The programmes a registrar job's NEW section may be placed in: the active programmes of its
     * course — the course the block resolved, or the candidate chosen in this request. Empty when no
     * course is settled, so no programme can be chosen for a section that cannot be created.
     *
     * @return list<int>
     */
    private function registrarProgramIds(ImportJob $import, Request $request): array
    {
        $resolution = $import->registrarResolution() ?? [];
        $chosen = (int) $request->input('options.course_id', 0);

        $courseId = $resolution['course_id']
            ?? (in_array($chosen, $resolution['course_candidates'] ?? [], true) ? $chosen : null);

        $course = $courseId === null ? null : Course::query()->find($courseId);

        return $course?->activeProgramIds() ?? [];
    }

    public function update(Request $request, ImportJob $import, StartValidation $start): JsonResponse|RedirectResponse
    {
        $isSection = $import->mode === SectionMode::NAME;
        $isRegistrar = $import->mode === RegistrarMode::NAME;
        $isCourse = $import->mode === CourseMode::NAME;
        $isUser = $import->mode === UserMode::NAME;
        $organizationId = $request->user()?->organization_id;

        $validated = $request->validate([
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:255'],

            // A section list creates no course and no account, so its job takes none of the choices
            // about creating them. Refused rather than stored: an inert value an instructor supplied
            // is one shared code path away from being acted on.
            // A staff list takes no choices either: every refusal there is an error, never a
            // create-or-skip (UserMode).
            'options' => $isSection || $isUser ? ['prohibited'] : ['sometimes', 'array'],
            'options.create_missing_courses' => ['sometimes', 'boolean'],
            'options.create_missing_instructors' => ['sometimes', 'boolean'],

            // US8 — the course mode's three choices (CourseMode).
            'options.create_missing_programs' => $isCourse ? ['sometimes', 'boolean'] : ['prohibited'],
            'options.associate_inactive_programs' => $isCourse ? ['sometimes', 'boolean'] : ['prohibited'],
            'options.new_program_unit' => $isCourse ? ['nullable', Rule::in(['admin', 'tech'])] : ['prohibited'],
            'options.new_course_program_id' => [
                'nullable', 'integer',
                Rule::exists('programs', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],

            /*
             * The registrar mode's two choices (T332). `course_id` settles a course name the file
             * shares with several courses — one of exactly those candidates, nothing else (FR-100).
             * `program_id` places a section the import will create when its course has no single
             * programme (FR-090). Neither means anything to another mode.
             */
            'options.course_id' => $isRegistrar
                ? ['nullable', 'integer', Rule::in($import->registrarResolution()['course_candidates'] ?? [])]
                : ['prohibited'],
            // One of the course's own ACTIVE programmes (review M1, H1) — not any programme in the
            // college. For an ambiguous course, the course is the one chosen in this same request.
            'options.program_id' => $isRegistrar
                ? ['nullable', 'integer', Rule::in($this->registrarProgramIds($import, $request))]
                : ['prohibited'],
            'start_validation' => ['sometimes', 'boolean'],
        ], [
            'options.prohibited' => __($isUser ? 'imports.conflicts.options_not_in_user_mode' : 'imports.conflicts.options_not_in_section_mode'),
        ]);

        if (! $import->canBeMapped()) {
            throw new ConflictHttpException(__('imports.conflicts.not_mappable'));
        }

        $headers = array_map('trim', $import->file_metadata['headers'] ?? []);
        $mode = $import->modeClass();
        $mapping = [];
        $errors = [];

        foreach ($mode::TARGETS as $target => $required) {
            $source = trim((string) ($validated['mapping'][$target] ?? ''));
            $mapping[$target] = $source === '' ? null : $source;

            if ($source === '') {
                if ($required) {
                    $errors['mapping.'.$target] = __('imports.mapping.required', ['column' => ImportReasons::column($target)]);
                }

                continue;
            }

            if (! in_array($source, $headers, true)) {
                $errors['mapping.'.$target] = __('imports.mapping.not_in_header', ['column' => $source]);
            }
        }

        $unmapped = count(array_filter(
            $mode::required(),
            static fn (string $target): bool => $mapping[$target] === null,
        ));

        // The "this looks like a different kind of file" hint is the central mode's: a section list
        // has two columns, and its two field errors already say everything there is to say.
        if ($mode === CentralMode::class && $unmapped * 2 > count($mode::required())) {
            $errors['mapping'] = __('imports.mapping.wrong_shape');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        /*
         * Compare-and-set on the status this request saw: a mapping saved over a job that moved on in
         * the meantime — a validation another tab started — would change what that validation reads
         * halfway through it.
         */
        $claimed = ImportJob::query()->withoutGlobalScopes()
            ->whereKey($import->id)
            ->where('status', $import->status)
            ->update(['status' => 'mapped', 'updated_at' => now()]);

        if ($claimed !== 1) {
            throw new ConflictHttpException(__('imports.conflicts.not_mappable'));
        }

        $import->forceFill([
            'mapping' => $mapping,
            'options' => $isUser ? [] : ($isCourse ? [
                'create_missing_programs' => filter_var($validated['options']['create_missing_programs'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'associate_inactive_programs' => filter_var($validated['options']['associate_inactive_programs'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'new_program_unit' => $validated['options']['new_program_unit'] ?? 'admin',
            ] : ($isRegistrar ? [
                // A registrar file creates no course and no account: its section's instructor is
                // the operator's choice at upload (FR-101).
                'create_missing_courses' => false,
                'create_missing_instructors' => false,
                'course_id' => isset($validated['options']['course_id']) ? (int) $validated['options']['course_id'] : null,
                'program_id' => isset($validated['options']['program_id']) ? (int) $validated['options']['program_id'] : null,
            ] : [
                'create_missing_courses' => filter_var($validated['options']['create_missing_courses'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'create_missing_instructors' => filter_var($validated['options']['create_missing_instructors'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'new_course_program_id' => isset($validated['options']['new_course_program_id'])
                    ? (int) $validated['options']['new_course_program_id']
                    : null,
            ])),
            'status' => 'mapped',

            // A new mapping invalidates whatever the last validation found.
            'summary' => null,
            'preview_fetched_at' => null,
        ])->save();

        if ($request->boolean('start_validation')) {
            $start->execute($import);
        }

        if (! $request->expectsJson()) {
            return redirect()->route('imports.show', $import);
        }

        $import->refresh();

        return response()->json(['data' => [
            'id' => $import->id,
            'status' => $import->status,
            'mapping' => $import->mapping,
            'options' => $import->options,
            'suggested_mapping' => $import->file_metadata['suggested_mapping'] ?? [],
        ]]);
    }
}
