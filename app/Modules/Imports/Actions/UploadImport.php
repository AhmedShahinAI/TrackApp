<?php

declare(strict_types=1);

namespace App\Modules\Imports\Actions;

use App\Models\ImportJob;
use App\Models\Section;
use App\Models\User;
use App\Modules\Imports\Modes\CentralMode;
use App\Modules\Imports\Modes\CourseMode;
use App\Modules\Imports\Modes\RegistrarMode;
use App\Modules\Imports\Modes\SectionMode;
use App\Modules\Imports\Modes\UserMode;
use App\Modules\Imports\Pipeline\ColumnMapper;
use App\Modules\Imports\Pipeline\FileValidator;
use App\Modules\Imports\Pipeline\MetadataBlockReader;
use App\Modules\Imports\Pipeline\ResolveRegistrarSection;
use App\Modules\Imports\Pipeline\SpreadsheetReader;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * T224 — step 1: accept a file, store it privately, and open a job in `uploaded` (FR-055).
 *
 * Arguments: the file, the actor, the term id, and — for the instructor's section upload (T254) —
 * the section, already resolved through the scoped binding and authorised by SectionPolicy.
 *
 * ## Refused before stored
 *
 * FileValidator runs against the upload's temporary file first. Only a file that passes every check
 * is written to the private disk, so a refused upload leaves nothing behind to clean up or serve.
 *
 * ## Stored where nothing serves it
 *
 * The `private` disk sits outside the public root and has `serve` off. The stored name is a UUID,
 * never the original filename: a name chosen by the uploader is a path chosen by the uploader, and
 * `../../public/x.php` is a valid filename on the machine it came from. The original name is kept
 * as data, for the audit entry and the imports list.
 *
 * ## The header is read now
 *
 * contracts/imports.md says the suggested mapping is "computed at upload". It is stored with the
 * header row in `file_metadata`, so the mapping step opens instantly and compares the operator's
 * choice against the same suggestion every time it is shown.
 *
 * ## Audited by creation
 *
 * `import.uploaded` is written by ImportJob's created hook, inside this transaction — see
 * ImportJob::auditActionFor().
 *
 * @extends TransactionalAction<ImportJob>
 */
final class UploadImport extends TransactionalAction
{
    public function __construct(
        private readonly FileValidator $validator,
        private readonly ColumnMapper $mapper,
        private readonly MetadataBlockReader $metadata,
        private readonly ResolveRegistrarSection $registrarSection,
    ) {}

    public function execute(mixed ...$arguments): mixed
    {
        /** @var UploadedFile $file */
        $file = $arguments[0];

        $checked = $this->validator->validate($file);

        $path = $file->storeAs(
            'imports/'.now()->format('Y/m'),
            Str::uuid()->toString().'.'.$checked['extension'],
            'private',
        );

        if ($path === false) {
            throw new RuntimeException('The import file could not be stored.');
        }

        try {
            return parent::execute($file, $arguments[1], $arguments[2], $path, $checked, $arguments[3] ?? null, $arguments[4] ?? null);
        } catch (Throwable $e) {
            // The row did not survive, so neither does the file it would have pointed at.
            Storage::disk('private')->delete($path);

            throw $e;
        }
    }

    /**
     * Phase 27 review — the slow half of an upload, for a caller that must not hold a transaction
     * while it runs: validate (which counts every row), store, read the header, hash.
     * CreateSection stages a roster before it opens its own transaction, so the new section's row
     * and its unique-key entry are locked for two inserts, not for a spreadsheet's parse.
     * {@see executeStaged()} is the other half. A caller that does not continue must delete
     * `path`.
     *
     * @return array{checked: array{extension: string, data_rows: int}, path: string, header: array<int, mixed>|null, hash: string}
     */
    public function stage(UploadedFile $file): array
    {
        $checked = $this->validator->validate($file);

        $path = $file->storeAs(
            'imports/'.now()->format('Y/m'),
            Str::uuid()->toString().'.'.$checked['extension'],
            'private',
        );

        if ($path === false) {
            throw new RuntimeException('The import file could not be stored.');
        }

        try {
            $absolute = Storage::disk('private')->path($path);

            return [
                'checked' => $checked,
                'path' => $path,
                'header' => SpreadsheetReader::header($absolute, $checked['extension']),
                'hash' => (string) hash_file('sha256', $absolute),
            ];
        } catch (Throwable $e) {
            Storage::disk('private')->delete($path);

            throw $e;
        }
    }

    /**
     * The transactional half: the job row only, from what {@see stage()} prepared. A section-mode or
     * central-mode upload; the registrar reads its metadata block, so it keeps execute().
     *
     * @param  array{checked: array{extension: string, data_rows: int}, path: string, header: array<int, mixed>|null, hash: string}  $staged
     */
    public function executeStaged(array $staged, UploadedFile $file, User $actor, ?int $termId, ?Section $section): ImportJob
    {
        try {
            /** @var ImportJob */
            return parent::execute($file, $actor, $termId, $staged['path'], $staged['checked'], $section, null, ['header' => $staged['header'], 'hash' => $staged['hash']]);
        } catch (Throwable $e) {
            Storage::disk('private')->delete($staged['path']);

            throw $e;
        }
    }

    protected function handle(mixed ...$arguments): ImportJob
    {
        /** @var UploadedFile $file */
        [$file, $actor, $termId, $path, $checked, $section, $context] = $arguments;
        /** @var array{header?: array<int, mixed>|null, hash?: string}|null $staged */
        $staged = $arguments[7] ?? null;

        /** @var User $actor */
        /** @var Section|null $section */
        /** @var array{mode: string, instructor_id?: int}|null $context */
        $absolute = Storage::disk('private')->path($path);

        if (($context['mode'] ?? null) === RegistrarMode::NAME) {
            return $this->registrar($file, $actor, (int) $termId, $path, $absolute, $checked, (int) ($context['instructor_id'] ?? 0));
        }

        /*
         * US8 — the course and staff modes: an ordinary header row, and no term. A catalogue and a
         * staff list belong to the college, not to a term, so `term_id` stays null and FR-067's
         * "same file" is judged within the organization instead (ImportJob::duplicateOf).
         */
        $mode = match ($context['mode'] ?? null) {
            CourseMode::NAME => CourseMode::class,
            UserMode::NAME => UserMode::class,
            default => $section === null ? CentralMode::class : SectionMode::class,
        };

        if (in_array($mode, [CourseMode::class, UserMode::class], true)) {
            $termId = null;
        }
        $header = $staged['header'] ?? SpreadsheetReader::header($absolute, $checked['extension']);
        $headers = $header[1] ?? [];

        $job = new ImportJob;

        $job->forceFill([
            'user_id' => $actor->getKey(),
            'mode' => $mode::NAME,

            // A section upload's term is the section's: the instructor chose a section, not a term,
            // and FR-067's "same file, same term" must compare like with like.
            'section_id' => $section?->getKey(),
            'term_id' => $section !== null ? $section->term_id : $termId,
            'original_filename' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'file_path' => $path,
            'file_hash' => $staged['hash'] ?? (string) hash_file('sha256', $absolute),
            'status' => 'uploaded',
            'total_rows' => $checked['data_rows'],
            'file_metadata' => [
                'header_row' => $header[0] ?? 1,
                'headers' => $headers,
                'suggested_mapping' => $this->mapper->suggest($mode::ALIASES, $headers),
            ],
        ])->save();

        return $job;
    }

    /**
     * T331 — a registration-unit export (FR-096 to FR-101).
     *
     * The header is not the first row here: it follows the metadata block, so the block is read
     * first and the header taken from where it ends. The block is then resolved — branch, course,
     * existing section — so the job opens already knowing what the file means, and a file whose
     * campus or course cannot be resolved is refused now, before any row is read (FR-099, FR-100).
     * A refusal is a ValidationException on `file`; execute() then deletes the stored copy.
     *
     * total_rows is the data rows only. FileValidator counts every non-empty row after the first,
     * which here includes the rest of the block and the header itself.
     *
     * @param  array{extension: string, data_rows: int}  $checked
     */
    private function registrar(UploadedFile $file, User $actor, int $termId, string $path, string $absolute, array $checked, int $instructorId): ImportJob
    {
        $block = $this->metadata->read($absolute, $checked['extension']);

        if ($block === null) {
            throw ValidationException::withMessages(['file' => __('imports.registrar.not_registrar_export')]);
        }

        $missing = MetadataBlockReader::missing($block['metadata']);

        if ($missing !== []) {
            throw ValidationException::withMessages(['file' => __('imports.registrar.metadata_missing', [
                'fields' => implode('، ', array_map(static fn (string $f): string => __('imports.registrar.fields.'.$f), $missing)),
            ])]);
        }

        $resolved = $this->registrarSection->resolve($block['metadata'], (int) $actor->organization_id, $termId);

        $job = new ImportJob;

        $job->forceFill([
            'user_id' => $actor->getKey(),
            'mode' => RegistrarMode::NAME,
            'section_id' => null,
            'term_id' => $termId,
            'instructor_id' => $instructorId,
            'original_filename' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'file_path' => $path,
            'file_hash' => (string) hash_file('sha256', $absolute),
            'status' => 'uploaded',
            'total_rows' => max(0, $checked['data_rows'] - $block['rows_above_header']),
            'file_metadata' => [
                'header_row' => $block['header_row'],
                'headers' => $block['headers'],
                'suggested_mapping' => $this->mapper->suggest(RegistrarMode::ALIASES, $block['headers']),

                // As the file wrote it, for the confirmation panel and the audit entry.
                'registrar' => $block['metadata'],
                'resolved' => $resolved,
            ],
        ])->save();

        return $job;
    }
}
