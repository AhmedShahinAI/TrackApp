<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

use App\Models\ImportJob;
use App\Modules\Imports\Modes\RegistrarMode;
use Illuminate\Support\Facades\Storage;

/**
 * T225 — the header row of a stored import, and nothing else.
 *
 * The mapping step needs one row of a file that may hold fifty thousand. SpreadsheetReader is a
 * generator, so asking for the first row reads the first row and stops; the rest of the file is
 * never parsed until validation streams it in the background.
 */
final class HeaderReader
{
    /**
     * @return array{row: int, headers: list<string>}
     */
    public function read(ImportJob $job): array
    {
        /*
         * A registrar export's header is not its first row: the metadata block comes first, and
         * where the header sits was found at upload (MetadataBlockReader). Re-reading "the first
         * non-empty row" would map the block's «المقر» line as the header and every metadata row as
         * a student.
         */
        if ($job->mode === RegistrarMode::NAME && isset($job->file_metadata['header_row'])) {
            return ['row' => (int) $job->file_metadata['header_row'], 'headers' => $job->file_metadata['headers'] ?? []];
        }

        $header = SpreadsheetReader::header(self::path($job), self::extension($job));

        if ($header === null) {
            return ['row' => 1, 'headers' => []];
        }

        [$row, $cells] = $header;

        return ['row' => $row, 'headers' => $cells];
    }

    public static function path(ImportJob $job): string
    {
        return Storage::disk('private')->path($job->file_path);
    }

    public static function extension(ImportJob $job): string
    {
        return strtolower(pathinfo($job->file_path, PATHINFO_EXTENSION));
    }
}
