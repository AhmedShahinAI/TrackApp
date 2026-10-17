<?php

declare(strict_types=1);

namespace App\Modules\Sections\Actions;

use App\Models\Section;
use App\Models\SectionApprovalHistory;
use App\Models\SectionInstructorHistory;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Imports\Actions\UploadImport;
use App\Modules\Sections\Enums\SectionStatus;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * T488 + T494 — create a section, whose creator decides whether it needs approving (FR-171, FR-172).
 *
 * **The status comes from the creator's role, never from the request**, and never from a permission. The
 * first version read `section.approve`, which a coordinator holds, so a coordinator's section was born
 * active and skipped the Super Admin's approval FR-172 requires. `scope.bypass_all`, the Super Admin's
 * mark, is the one thing that means "no approval".
 *
 * ## The roster (FR-175)
 *
 * An attached roster goes through the **existing** section import mode, never a parallel path. It lands
 * as an import job the creator walks through the usual wizard. Three properties hold:
 *
 *   - **Refused before anything is created.** Running a section import needs `import.section.run`;
 *     a creator without it (a coordinator holds no import permission) is told so before the section
 *     exists, not after a rollback.
 *   - **No transaction held across the parse** (Phase 27 review). UploadImport::stage() validates,
 *     stores, reads the header and hashes **before** the transaction opens. Inside it are two inserts.
 *   - **All or nothing.** If the section cannot be created, the staged file is deleted. If the job
 *     cannot be recorded, the section is rolled back with it.
 *
 * ## A duplicate number, raced
 *
 * The form request's uniqueness rule runs before the insert, so two creators claiming one number at the
 * same moment can both pass it. The index refuses the second, and that refusal is the same 422 the rule
 * gives, not a 500 (Phase 27 review).
 *
 * Audited: `section.created` (the model's trait, now carrying the creator's role and the status), plus
 * `section.submitted_for_approval` when the section is pending.
 *
 * @extends TransactionalAction<array{section: Section, import_job_id: int|null}>
 */
final class CreateSection extends TransactionalAction
{
    public function __construct(
        private readonly Auditor $auditor,
        private readonly UploadImport $upload,
    ) {}

    /**
     * Called as execute($creator, $validated, ?$roster).
     *
     * @return array{section: Section, import_job_id: int|null}
     */
    public function execute(mixed ...$arguments): mixed
    {
        /** @var User $creator */
        /** @var UploadedFile|null $roster */
        [$creator, $validated, $roster] = $arguments + [2 => null];

        if ($roster !== null && ! $creator->can('import.section.run')) {
            throw ValidationException::withMessages(['roster' => __('sections.approval.errors.roster_not_permitted')]);
        }

        $staged = $roster === null ? null : $this->upload->stage($roster);

        try {
            return parent::execute($creator, $validated, $roster, $staged);
        } catch (UniqueConstraintViolationException) {
            $this->discard($staged);

            throw ValidationException::withMessages(['section_number' => __('sections.errors.duplicate_key')]);
        } catch (Throwable $e) {
            $this->discard($staged);

            throw $e;
        }
    }

    protected function handle(mixed ...$arguments): array
    {
        /** @var User $creator */
        /** @var array<string, mixed> $validated */
        /** @var UploadedFile|null $roster */
        /** @var array{checked: array{extension: string, data_rows: int}, path: string, header: array<int, mixed>|null, hash: string}|null $staged */
        [$creator, $validated, $roster, $staged] = $arguments;

        $immediate = $creator->can('scope.bypass_all');
        $status = $immediate ? SectionStatus::Active : SectionStatus::Pending;

        $section = new Section;
        $section->forceFill($validated + [
            'degree' => $validated['degree'] ?? Settings::values(Settings::SECTION_DEGREES)[0],
            'activity' => $validated['activity'] ?? Settings::values(Settings::SECTION_ACTIVITIES)[0],
            'created_by' => $creator->getKey(),
            'status' => $status->value,
            'requires_approval' => ! $immediate,
        ])->save();

        SectionApprovalHistory::query()->create([
            'section_id' => $section->getKey(),
            'from_status' => null,
            'to_status' => $status->value,
            'actor_id' => $creator->getKey(),
            'reason' => null,
        ]);

        if ($status === SectionStatus::Pending) {
            $this->auditor->record('section.submitted_for_approval', $section, [], ['status' => $status->value]);
        }

        /*
         * The first instructor opens the teaching history, in the same transaction as the section.
         * ChangeSectionInstructor closes "the open period", so it assumes creation left one behind
         * (FR-048).
         */
        if ($section->instructor_id !== null) {
            SectionInstructorHistory::query()->create([
                'section_id' => $section->getKey(),
                'instructor_id' => $section->instructor_id,
                'assigned_at' => now(),
                'unassigned_at' => null,
                'changed_by' => $creator->getKey(),
            ]);
        }

        $importJobId = null;

        if ($roster !== null && $staged !== null) {
            // The section import mode's own rule, asked of the new section: the creator of a section
            // still awaiting approval may import into it (SectionPolicy::importRoster), whoever teaches.
            if (! $creator->can('importRoster', $section)) {
                throw ValidationException::withMessages(['roster' => __('sections.approval.errors.roster_not_permitted')]);
            }

            $importJobId = $this->upload->executeStaged($staged, $roster, $creator, $section->term_id, $section)->getKey();
        }

        return ['section' => $section, 'import_job_id' => $importJobId];
    }

    /**
     * @param  array{path: string}|null  $staged
     */
    private function discard(?array $staged): void
    {
        if ($staged !== null) {
            Storage::disk('private')->delete($staged['path']);
        }
    }
}
