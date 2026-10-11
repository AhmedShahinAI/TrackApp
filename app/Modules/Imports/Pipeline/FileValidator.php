<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

use App\Modules\Settings\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

/**
 * T223 — whether an uploaded file may be stored at all (FR-055).
 *
 * ## Before storage, not after
 *
 * Every check runs against the upload's temporary file. A refused file never reaches the private
 * disk, so there is nothing to clean up and nothing an attacker's file can be later served from.
 *
 * ## Name, declared type, and content — all three
 *
 * The extension is what the user claims, the MIME type is what PHP's fileinfo infers, and the magic
 * bytes are what the file is. A `.xlsx` renamed from `.exe` passes the first check and fails the
 * other two; a CSV with a `.xlsx` extension passes the first and fails the third. Checking only the
 * extension is how upload forms end up hosting executables.
 *
 * The content check for xlsx goes one step past "starts with PK": any zip starts with PK, and a
 * `.docx` renamed to `.xlsx` is a zip. A workbook has `xl/workbook.xml` inside it, and nothing else
 * does.
 *
 * ## The limits are settings
 *
 * `import_max_file_mb` and `import_max_rows` (constitution IV). The row count is streamed, and it
 * stops counting at the limit, so a hostile million-row file costs the same as a legitimate one.
 */
final class FileValidator
{
    /** The MIME types fileinfo reports for each accepted extension, as seen on real exports. */
    /** How many times the upload limit an xlsx may declare once unpacked (review L6). */
    public const MAX_EXPANSION = 15;

    private const MIME_TYPES = [
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/octet-stream',
        ],
        'csv' => [
            'text/csv',
            'text/plain',
            'application/csv',
            'text/x-csv',
            'application/vnd.ms-excel',
            'text/comma-separated-values',
        ],
    ];

    /**
     * @return array{extension: string, data_rows: int}
     *
     * @throws ValidationException
     */
    public function validate(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, SpreadsheetReader::EXTENSIONS, true)) {
            $this->refuse(__('imports.upload.bad_type'));
        }

        $maxMb = Settings::int(Settings::IMPORT_MAX_FILE_MB);

        if ((int) $file->getSize() > $maxMb * 1024 * 1024) {
            $this->refuse(__('imports.upload.too_large', ['mb' => $maxMb]));
        }

        $path = (string) $file->getRealPath();

        if (! in_array((string) $file->getMimeType(), self::MIME_TYPES[$extension], true)
            || ! $this->contentMatches($path, $extension)) {
            $this->refuse(__('imports.upload.bad_type'));
        }

        /*
         * Review L6 — the size limit is on the bytes uploaded, and an xlsx is a zip: a few hundred
         * kilobytes can declare gigabytes once unpacked. The central directory states every entry's
         * unpacked size, so the sum is read without decompressing anything, and a workbook that
         * would expand past MAX_EXPANSION times the upload limit is refused before it is parsed.
         * Real rosters compress about five to ten times; fifteen leaves room for very repetitive text.
         */
        if ($extension === 'xlsx' && $this->unpackedBytes($path) > $maxMb * 1024 * 1024 * self::MAX_EXPANSION) {
            $this->refuse(__('imports.upload.unpacked_too_large'));
        }

        $maxRows = Settings::int(Settings::IMPORT_MAX_ROWS);
        $rows = $this->countDataRows($path, $extension, $maxRows);

        if ($rows > $maxRows) {
            $this->refuse(__('imports.upload.too_many_rows', ['rows' => number_format($maxRows)]));
        }

        return ['extension' => $extension, 'data_rows' => $rows];
    }

    /** The sum of a zip's declared unpacked entry sizes, read from its central directory. */
    private function unpackedBytes(string $path): int
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return PHP_INT_MAX;
        }

        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $total += (int) ($zip->statIndex($i)['size'] ?? 0);
        }

        $zip->close();

        return $total;
    }

    private function contentMatches(string $path, string $extension): bool
    {
        $head = (string) file_get_contents($path, false, null, 0, 8192);

        if ($extension === 'xlsx') {
            if (! str_starts_with($head, "PK\x03\x04")) {
                return false;
            }

            $zip = new ZipArchive;

            if ($zip->open($path, ZipArchive::RDONLY) !== true) {
                return false;
            }

            $isWorkbook = $zip->locateName('xl/workbook.xml') !== false;
            $zip->close();

            return $isWorkbook;
        }

        /*
         * A CSV is text. A NUL byte, an executable header, or a zip signature means it is not, whatever
         * its name says. UTF-16 text has NULs too, and is refused for the same reason — Excel offers it
         * as "Unicode Text", which is a tab-separated file and not one this importer can promise to read
         * correctly.
         */
        return ! str_contains($head, "\0")
            && ! str_starts_with($head, 'MZ')
            && ! str_starts_with($head, "PK\x03\x04")
            && ! str_starts_with($head, "\x7FELF");
    }

    /**
     * Data rows — every non-empty row after the header — counted up to one past the limit.
     */
    private function countDataRows(string $path, string $extension, int $limit): int
    {
        $count = -1;

        try {
            foreach (SpreadsheetReader::rows($path, $extension) as $_) {
                if (++$count > $limit) {
                    break;
                }
            }
        } catch (Throwable) {
            SpreadsheetReader::release();
            $this->refuse(__('imports.upload.unreadable'));
        }

        SpreadsheetReader::release();

        return max(0, $count);
    }

    /**
     * @throws ValidationException
     */
    private function refuse(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
