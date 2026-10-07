<?php

declare(strict_types=1);

namespace App\Modules\Audit\Exports;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Audit\Services\AuditLabels;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * T165 — the audit log as an Arabic-safe .xlsx (FR-023, FR-032).
 *
 * ## The invariant
 *
 * The rows are the list endpoint's rows. Not "the same filters applied to a similar query" — the
 * same builder, handed in by the controller after the global scope has already constrained it.
 * ExportRespectsScopeTest asserts the relation rather than a row count, because an export is where
 * a scope leak hides best: a list is read constantly and a missing constraint shows up in a day,
 * while an export is written once, often as hand-tuned SQL for speed, and nobody reads the bytes
 * again.
 *
 * ## Arabic-safe
 *
 * xlsx stores strings as UTF-8 inside the zip, so there is no BOM question and no codepage to get
 * wrong — which is the reason this is xlsx and not the CSV it started as. A CSV of Arabic names
 * opens as mojibake in Excel unless it carries a BOM, and even then the encoding is a guess.
 *
 * ## Streaming
 *
 * Written straight to the response through OpenSpout rather than assembled and then sent. A Super
 * Admin's export is every entry in the college, and SC-004 sizes that at a million rows; chunking
 * the query into a stream keeps the memory flat whether the filter matched forty rows or forty
 * thousand.
 */
final class AuditLogExport
{
    private const CHUNK = 500;

    /**
     * @param  Builder<AuditLog>  $query  already scoped and filtered by the controller
     * @param  array<string, mixed>  $filters  what the caller asked for, for the header block
     */
    public function __construct(
        private readonly Builder $query,
        private readonly array $filters,
        private readonly ?User $requestedBy,
    ) {}

    public function toResponse(string $filename = 'audit-log.xlsx'): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $writer = new Writer;
            $writer->openToFile('php://output');

            foreach ($this->headerBlock() as $line) {
                $writer->addRow(self::textRow($line));
            }

            $writer->addRow(self::textRow([
                __('audit.fields.created_at'),
                __('audit.fields.user'),
                __('audit.fields.action'),
                __('audit.fields.entity_type'),
                __('audit.fields.entity_id'),
                __('audit.fields.ip_address'),
            ]));

            /*
             * The id is a tiebreaker, and it is not cosmetic. `chunk()` pages with LIMIT/OFFSET, so
             * ordering by `created_at` alone — which is not unique, and is identical for every row
             * a bulk import writes — lets rows repeat or vanish across a page boundary. The export
             * is supposed to contain exactly what the list contains; silently doing neither is the
             * worst outcome of the three.
             */
            $this->query
                ->with('user:id,name_ar,name_en')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->chunk(self::CHUNK, function ($entries) use ($writer): void {
                    foreach ($entries as $entry) {
                        $writer->addRow(self::textRow([
                            $entry->created_at?->format('Y-m-d H:i:s') ?? '',
                            $entry->user->name_ar ?? '—',
                            AuditLabels::for((string) $entry->action),
                            (string) $entry->entity_type,
                            (string) $entry->entity_id,
                            (string) $entry->ip_address,
                        ]));
                    }
                });

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * A row whose every cell is text, never a formula.
     *
     * ## This is a security control, not a formatting preference
     *
     * `Row::fromValues()` routes each value through `Cell::fromValue()`, which turns any string
     * starting with `=` into a FormulaCell — the spreadsheet then holds a live formula. An account
     * named `=cmd|' /c calc'!A1` is a valid 255-character string as far as StoreUserRequest is
     * concerned, and the export turned it into `<f>cmd|&#039; /c calc&#039;!A1</f>` in the sheet,
     * verified by unpacking the xlsx.
     *
     * That is spreadsheet formula injection, and the audit log is the worst place for it: the file
     * is opened by administrators, it carries names supplied by whoever could get an account
     * created, and the whole point of the document is to be trusted.
     *
     * Constructing StringCell directly is the complete fix for xlsx, because the risk here is that
     * the writer emits an `<f>` element at all — an inlineStr cell is never evaluated. No apostrophe
     * prefix, so the exported value reads exactly as it is stored.
     *
     * @param  list<string>  $values
     */
    private static function textRow(array $values): Row
    {
        return new Row(array_map(
            static fn (string $value): StringCell => new StringCell($value),
            $values,
        ));
    }

    /**
     * The header block the contract requires: the applied filters, the generation timestamp, and
     * who asked for it.
     *
     * It is on the sheet rather than in the filename because a file gets renamed, forwarded and
     * attached to a message months later, and a sheet that cannot say what it is a filtered view of
     * is a sheet that will be read as the whole log.
     *
     * @return list<list<string>>
     */
    private function headerBlock(): array
    {
        $applied = [];

        foreach ($this->filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $applied[] = __('audit.fields.'.$key).': '.(is_scalar($value) ? (string) $value : json_encode($value));
        }

        return [
            [__('audit.export.title')],
            [__('audit.export.generated_at'), now()->format('Y-m-d H:i:s')],
            [__('audit.export.requested_by'), $this->requestedBy->name_ar ?? '—'],
            [__('audit.export.filters'), $applied === [] ? __('audit.export.no_filters') : implode(' · ', $applied)],
            [],
        ];
    }
}
