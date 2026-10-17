<?php

declare(strict_types=1);

use OpenSpout\Reader\XLSX\Reader;
use Symfony\Component\Process\Process;

/*
|--------------------------------------------------------------------------
| SEC-019 — no real student ever enters the repository again (security plan G-01)
|--------------------------------------------------------------------------
|
| The registrar's real export, with 22 real students' names and University IDs, was committed with the
| design system and copied from there into the seeder, the React kit and the specs. Phase 1 of the
| security plan replaced every real value with a synthetic one. This test keeps it that way.
|
| It cannot hold the real values to compare against: a hash of a 9-digit ID is reversed in seconds, so
| a list of hashes would be the roster again. It pins the shape instead, and the security plan's
| out-of-repository scan proves the content:
|
|   - the only spreadsheets git tracks are the two names of the redacted export, by content hash;
|   - every University ID in that export is in the reserved synthetic block 999000xxx;
|   - the design system's spreadsheet folders and the old design-system-v1/ are gitignored, so a new
|     roster dropped there is never picked up by `git add .`.
|
| A genuinely new fixture spreadsheet needs this test changed beside it, by someone who has checked
| that it holds no real person.
|
*/

const REDACTED_EXPORT_SHA1 = 'ef3fa59ed7a8e654d9db1706afe9cfd730294a24';

/**
 * Run git in the repository root and return its stdout. Fails the test, rather than skipping it, when
 * git cannot run: a guard that quietly stops guarding is worse than none.
 */
function gitOutput(string ...$args): string
{
    $process = new Process(['git', ...$args], base_path());
    $process->run();

    if (! in_array($process->getExitCode(), [0, 1], true)) {
        throw new RuntimeException('git '.implode(' ', $args).' failed: '.$process->getErrorOutput());
    }

    return $process->getOutput();
}

test('the only spreadsheets git tracks are the redacted registrar export', function (): void {
    $tracked = array_values(array_filter(
        explode("\n", gitOutput('ls-files')),
        fn (string $path): bool => preg_match('/\.(xlsx|xlsm|xls|ods|csv)$/i', $path) === 1,
    ));
    sort($tracked);

    expect($tracked)->toBe([
        'design-system/scratch/roster.xlsx',
        'design-system/uploads/filedownloadservlet(21).xlsx',
    ]);

    foreach ($tracked as $path) {
        expect(sha1_file(base_path($path)))->toBe(REDACTED_EXPORT_SHA1, $path.' is not the redacted export');
    }
});

test('every University ID in the registrar export is in the synthetic block', function (): void {
    $reader = new Reader;
    $reader->open(base_path('design-system/uploads/filedownloadservlet(21).xlsx'));

    $ids = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->cells as $cell) {
                $value = trim((string) $cell->getValue());
                if (preg_match('/^\d{9}$/', $value) === 1) {
                    $ids[] = $value;
                }
            }
        }
    }
    $reader->close();

    expect($ids)->toHaveCount(22)
        ->and(array_values(array_filter($ids, fn (string $id): bool => ! str_starts_with($id, '999000'))))->toBe([]);
});

test('a roster dropped into the design system, or the old v1 folder, is ignored by git', function (string $path): void {
    expect(gitOutput('check-ignore', '--no-index', $path))->not->toBe('');
})->with([
    'design-system/uploads/another-export.xlsx',
    'design-system/scratch/roster_sample.xlsx',
    'design-system-v1/uploads/filedownloadservlet(21).xlsx',
]);

test('the redacted export itself is not ignored', function (string $path): void {
    expect(gitOutput('check-ignore', '--no-index', $path))->toBe('');
})->with([
    'design-system/uploads/filedownloadservlet(21).xlsx',
    'design-system/scratch/roster.xlsx',
]);
