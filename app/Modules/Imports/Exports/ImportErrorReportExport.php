<?php

declare(strict_types=1);

namespace App\Modules\Imports\Exports;

use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Modules\Imports\Pipeline\ImportReasons;
use Generator;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * T236 — the downloadable error report: row number, column, submitted value, reason (FR-071, SC-009).
 *
 * ## Every rejected row, from the rows themselves
 *
 * "Rejected" is every staged row whose status is `error` or `skipped` — failed validation, skipped
 * by the operator's create-or-skip choice, or refused at commit by a unique index. The report is
 * read from `import_rows`, not reconstructed from the job's counters, so a row cannot be counted as
 * an error and missing from the file. ErrorReportTest compares the two directly.
 *
 * One line per reason, so a row with two faults shows both, each against its own column. A row with
 * no stated reason still gets a line; leaving it out is the one failure SC-009 forbids.
 *
 * ## A download writes nothing
 *
 * The first version rebuilt `import_errors` on every request and then streamed it with offset
 * pagination. Two people downloading at once interleaved their deletes and inserts, so a report could
 * come out with lines in both languages, a block repeated, or a block missing — and any `import.view`
 * holder could make a GET rewrite fifty thousand rows. Now the download streams `import_rows`
 * directly, keyset-paginated by id (lazyById), and localises each line for its reader as it goes.
 *
 * `import_errors` keeps PRD 11.4's flat shape for anything that later queries it. It is written once,
 * when the import completes (materialise()), in the language of the person who ran it.
 *
 * ## Arabic-safe and formula-safe
 *
 * xlsx carries UTF-8 natively, so no BOM or codepage is involved. And every cell is written as a
 * StringCell for the reason AuditLogExport gives at length: the submitted values are whatever was in
 * someone's spreadsheet, and a value beginning `=` must arrive as text, never as a live formula in a
 * file an administrator will open.
 */
final class ImportErrorReportExport
{
    private const CHUNK = 500;

    public function __construct(private readonly ImportJob $job) {}

    /**
     * Whether there is anything to report. The contract answers 404 when there is not.
     */
    public static function hasErrors(ImportJob $job): bool
    {
        return ImportRow::query()
            ->where('import_job_id', $job->id)
            ->whereIn('status', ['error', 'skipped'])
            ->exists();
    }

    /**
     * Write the job's lines into `import_errors`, once. Called inside the transaction that completes
     * the import, so the table and the `completed` status appear together.
     */
    public static function materialise(ImportJob $job, string $locale): void
    {
        if (ImportError::query()->where('import_job_id', $job->id)->exists()) {
            return;
        }

        $batch = [];

        foreach ((new self($job))->lines($locale) as $line) {
            $batch[] = [
                'import_job_id' => $job->id,
                'row_number' => $line['row_number'],
                'column' => $line['column'] === null ? null : mb_substr($line['column'], 0, 100),
                'value' => $line['value'] === null ? null : mb_substr($line['value'], 0, 500),
                'reason' => mb_substr($line['reason'], 0, 500),
            ];

            if (count($batch) === self::CHUNK) {
                ImportError::query()->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            ImportError::query()->insert($batch);
        }
    }

    public function toResponse(): StreamedResponse
    {
        $locale = app()->getLocale();
        $filename = pathinfo($this->job->original_filename, PATHINFO_FILENAME).'-errors.xlsx';

        return response()->streamDownload(function () use ($locale): void {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(self::textRow([
                __('imports.report.columns.row', [], $locale),
                __('imports.report.columns.column', [], $locale),
                __('imports.report.columns.value', [], $locale),
                __('imports.report.columns.reason', [], $locale),
            ]));

            foreach ($this->lines($locale) as $line) {
                $writer->addRow(self::textRow([
                    (string) $line['row_number'],
                    ImportReasons::column($line['column'], $locale),
                    (string) $line['value'],
                    $line['reason'],
                ]));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Every line of the report, in source-row order, read by id in chunks so memory stays flat at
     * fifty thousand rows and a concurrent writer cannot shift a page boundary.
     *
     * Ordered by id rather than row number: rows are staged in file order, so the two agree, and id
     * is what keyset pagination needs.
     *
     * @return Generator<int, array{row_number: int, column: ?string, value: ?string, reason: string}>
     */
    private function lines(string $locale): Generator
    {
        $rows = ImportRow::query()
            ->where('import_job_id', $this->job->id)
            ->whereIn('status', ['error', 'skipped'])
            ->lazyById(self::CHUNK);

        foreach ($rows as $row) {
            $reasons = array_values(array_filter(
                $row->reasons ?? [],
                static fn (array $r): bool => ! in_array($r['reason_key'], ImportReasons::INFORMATIONAL, true),
            ));

            if ($reasons === []) {
                yield ['row_number' => $row->row_number, 'column' => null, 'value' => null, 'reason' => __('imports.reasons.unspecified', [], $locale)];

                continue;
            }

            foreach ($reasons as $reason) {
                yield [
                    'row_number' => $row->row_number,
                    'column' => $reason['column'] ?? null,
                    'value' => $reason['value'] ?? null,
                    'reason' => ImportReasons::localise($reason, $locale),
                ];
            }
        }
    }

    /**
     * @param  list<string>  $values
     */
    private static function textRow(array $values): Row
    {
        return new Row(array_map(
            static fn (string $value): StringCell => new StringCell($value),
            $values,
        ));
    }
}
