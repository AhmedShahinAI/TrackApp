<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

use BackedEnum;
use Stringable;

/**
 * SEC-060 (G-07) — a CSV cell that a spreadsheet will show, never run.
 *
 * Excel, LibreOffice and Google Sheets treat a cell beginning with `=`, `+`, `-`, `@`, a tab or a
 * carriage return as a formula. A section number, a course code or a name typed by one user and
 * exported by another is exactly such a cell, and `=HYPERLINK(...)` or a DDE payload in it runs on
 * the exporter's machine. The xlsx exports are safe by construction (StringCell); a CSV has no cell
 * types, so the value itself is defused: a leading apostrophe, which every one of those programs
 * reads as "this is text" and does not display.
 *
 * OWASP's CSV-injection guidance, applied to every cell rather than to a guessed list of columns.
 */
final class CsvSafe
{
    private const TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    public static function cell(mixed $value): string
    {
        $text = match (true) {
            $value === null => '',
            $value instanceof BackedEnum => (string) $value->value,
            is_scalar($value), $value instanceof Stringable => (string) $value,
            default => '',
        };

        return $text !== '' && in_array($text[0], self::TRIGGERS, true) ? "'".$text : $text;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<string>
     */
    public static function row(array $values): array
    {
        return array_values(array_map(self::cell(...), $values));
    }
}
