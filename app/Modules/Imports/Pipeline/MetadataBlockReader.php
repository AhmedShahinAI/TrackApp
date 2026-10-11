<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

/**
 * T329 — the label/value block a registration-unit export carries above its header row (FR-097).
 *
 * The reference file opens (campus name elided — NoHardcodedBranchesTest keeps them out of app/):
 *
 *     المقر       <campus> (طلاب)
 *     الدرجة      الدبلوم المتوسط
 *     اسم المقرر  برمجة 2
 *     النشاط      محاضرة
 *     الشعبة      2298
 *
 *     تسلسل  رقم الطالب  اسم الطالب  الحالة
 *
 * Each block row is recognised by its LABEL, matched after ColumnMapper::normalise() (hamza, taa
 * marbuta, diacritics and spacing folded, a trailing colon dropped), and its value is the next
 * non-empty cell. The first row that is not a block row is the header. Position is never assumed: a
 * blank line, a reordered block or an extra label the platform does not know are all tolerated.
 *
 * ## Streamed, and stops early
 *
 * At most SCAN_ROWS rows are read, through the same generator as the rest of the pipeline, and the
 * reader is released straight after (SpreadsheetReader::release). The block is five lines; a file
 * whose header has not appeared by row 30 is not this kind of file.
 *
 * ## What "no block" means
 *
 * If the FIRST non-empty row is not a block row, the file has no metadata block — it is a central
 * roster or a section list — and read() returns null so the upload can say so (spec Edge Cases).
 */
final class MetadataBlockReader
{
    private const SCAN_ROWS = 30;

    /**
     * Field => the labels it is written under. Arabic as the registration unit writes them, English
     * for an export taken from an English interface.
     *
     * @var array<string, list<string>>
     */
    public const LABELS = [
        'campus' => ['المقر', 'الفرع', 'campus', 'branch'],
        'degree' => ['الدرجة', 'الدرجة العلمية', 'المرحلة', 'degree'],
        'course' => ['اسم المقرر', 'المقرر', 'course', 'course name'],
        'activity' => ['النشاط', 'نوع النشاط', 'activity'],
        'section' => ['الشعبة', 'رقم الشعبة', 'section', 'section number'],
    ];

    /** Without these three, the section the file describes cannot be found. */
    public const REQUIRED = ['campus', 'course', 'section'];

    /**
     * @return array{metadata: array<string, string>, header_row: int, headers: list<string>, rows_above_header: int}|null
     */
    public function read(string $path, string $extension): ?array
    {
        $labels = [];

        foreach (self::LABELS as $field => $spellings) {
            foreach ($spellings as $spelling) {
                $labels[ColumnMapper::normalise($spelling)] = $field;
            }
        }

        $metadata = [];
        $above = 0;
        $result = null;
        $scanned = 0;

        foreach (SpreadsheetReader::rows($path, $extension) as $number => $cells) {
            if (++$scanned > self::SCAN_ROWS) {
                break;
            }

            $filled = array_values(array_filter($cells, static fn (string $cell): bool => $cell !== ''));
            $label = ColumnMapper::normalise(rtrim($filled[0] ?? '', ':： '));
            $field = $labels[$label] ?? null;

            // A block row: a known label and a value beside it, before any header has been seen.
            if ($field !== null && isset($filled[1]) && ! isset($metadata[$field])) {
                $metadata[$field] = $filled[1];
                $above++;

                continue;
            }

            // The first non-empty row was not a block row: there is no block.
            if ($metadata === []) {
                break;
            }

            $result = [
                'metadata' => $metadata,
                'header_row' => $number,
                'headers' => $cells,
                'rows_above_header' => $above,
            ];

            break;
        }

        SpreadsheetReader::release();

        return $result;
    }

    /**
     * The required fields a block lacks, for the refusal that names them.
     *
     * @param  array<string, string>  $metadata
     * @return list<string>
     */
    public static function missing(array $metadata): array
    {
        return array_values(array_filter(self::REQUIRED, static fn (string $field): bool => ($metadata[$field] ?? '') === ''));
    }
}
