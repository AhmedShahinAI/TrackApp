<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

use DateTimeInterface;
use Generator;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * The one way the import pipeline reads a file: streamed, first sheet only, every cell a string.
 *
 * ## Streamed, because the ceiling is fifty thousand rows
 *
 * OpenSpout reads a row at a time, so a 20 MB roster costs the same memory as a 20-row one. That is
 * the whole reason it is used directly rather than through maatwebsite/excel's import layer, which
 * materialises collections (research.md R-03).
 *
 * ## Row numbers are the spreadsheet's row numbers
 *
 * Empty rows are preserved by the reader and skipped here, after counting. An operator told "row
 * 214" opens the file and looks at row 214; numbering the non-empty rows instead would point them
 * at the wrong student every time the registrar left a blank line between sections.
 *
 * ## Every cell becomes a string, deliberately
 *
 * Excel stores 441234567 as a number, and a number that has passed through a float can come back as
 * `441234567.0` or, worse, in exponent form. A university id is an identifier that happens to be
 * written in digits, so integral numbers are printed as integers and everything else is trimmed
 * text. Validation then sees exactly what the registrar typed.
 */
final class SpreadsheetReader
{
    /** The accepted extensions (FR-055). Anything else is refused before it is read. */
    public const EXTENSIONS = ['xlsx', 'csv'];

    /**
     * Every non-empty row of the first sheet, keyed by its spreadsheet row number.
     *
     * @return Generator<int, list<string>>
     */
    public static function rows(string $path, string $extension): Generator
    {
        $reader = self::open($path, $extension);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $number = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $number++;

                    $cells = array_map(self::cellToString(...), $row->toArray());

                    if (self::isBlank($cells)) {
                        continue;
                    }

                    yield $number => $cells;
                }

                // The first sheet only. A workbook with a "notes" tab is common, and importing it
                // as students is not what anyone who uploaded it meant.
                break;
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * The first non-empty row and its row number — the header, in every central and section file.
     *
     * @return array{0: int, 1: list<string>}|null
     */
    public static function header(string $path, string $extension): ?array
    {
        $header = null;

        foreach (self::rows($path, $extension) as $number => $cells) {
            $header = [$number, $cells];

            break;
        }

        self::release();

        return $header;
    }

    /**
     * Free the file handle a reader that stopped early is still holding.
     *
     * OpenSpout's XLSX row iterator and its XMLReader reference each other, so a reader abandoned
     * mid-sheet — the header read, or a row count that stopped at the limit — keeps the zip stream
     * open after close() until the cycle collector happens to run. On Linux that is a leaked handle;
     * on Windows it is a lock, and the stored file can then be neither replaced nor deleted. Found
     * when a test's fake disk could not be cleaned between tests.
     */
    public static function release(): void
    {
        gc_collect_cycles();
    }

    public static function cellToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            // Integral and within the range a float holds exactly: print it as the integer it is.
            if (floor($value) === $value && abs($value) < 1e15) {
                return number_format($value, 0, '.', '');
            }

            return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Non-breaking spaces and the right-to-left marks Arabic spreadsheets carry around names are
        // invisible on screen and fatal to an equality check, so they go before the trim.
        $text = str_replace(["\u{00A0}", "\u{200F}", "\u{200E}", "\u{FEFF}"], [' ', '', '', ''], (string) $value);

        return trim($text);
    }

    /**
     * @param  list<string>  $cells
     */
    private static function isBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== '') {
                return false;
            }
        }

        return true;
    }

    private static function open(string $path, string $extension): CsvReader|XlsxReader
    {
        if (strtolower($extension) === 'csv') {
            $reader = new CsvReader(new CsvOptions(
                SHOULD_PRESERVE_EMPTY_ROWS: true,
                FIELD_DELIMITER: self::sniffDelimiter($path),
                ENCODING: self::sniffEncoding($path),
            ));
        } else {
            $reader = new XlsxReader(new XlsxOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));
        }

        $reader->open($path);

        return $reader;
    }

    /**
     * Excel in an Arabic or European locale saves "CSV" with semicolons. Read as commas, every row
     * is one column and the mapping step offers the operator a single header containing the whole
     * line — which reads as the file being broken when it is only the locale.
     */
    private static function sniffDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ',';
        }

        $line = (string) fgets($handle);
        fclose($handle);

        $counts = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($counts);

        $best = (string) array_key_first($counts);

        return $counts[$best] > 0 ? $best : ',';
    }

    /**
     * UTF-8 unless the bytes say otherwise.
     *
     * A CSV saved from Excel on an Arabic Windows machine without "UTF-8" chosen is Windows-1256.
     * Read as UTF-8, every name is mojibake, every name "differs from the stored one", and the
     * preview fills with warnings that are entirely the encoding's fault.
     */
    private static function sniffEncoding(string $path): string
    {
        $sample = (string) file_get_contents($path, false, null, 0, 65536);

        // A multi-byte character cut by the sample's end is not evidence of anything, so up to three
        // trailing bytes are allowed to be the partial character.
        for ($cut = 0; $cut <= 3; $cut++) {
            if (mb_check_encoding($cut === 0 ? $sample : substr($sample, 0, -$cut), 'UTF-8')) {
                return 'UTF-8';
            }
        }

        return 'CP1256';
    }
}
