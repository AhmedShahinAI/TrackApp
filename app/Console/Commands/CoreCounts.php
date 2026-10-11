<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * T206 — `track:core-counts`: the probe for FR-060, the import contract's central guarantee.
 *
 * quickstart.md scenario 5.4 runs it before upload and again before confirming the preview, and the
 * two outputs must be identical. It counts the tables directly rather than through the models, so
 * no global scope or soft-delete filter can make a written row invisible to the check.
 */
final class CoreCounts extends Command
{
    /** The core tables of contracts/imports.md, plus terms, which FR-060 also protects. */
    public const TABLES = ['academic_terms', 'branches', 'courses', 'sections', 'students', 'section_enrollments'];

    protected $signature = 'track:core-counts {--json : Print the counts as JSON}';

    protected $description = 'Print row counts for the core academic tables (FR-060 probe)';

    public function handle(): int
    {
        $counts = self::snapshot();

        if ($this->option('json')) {
            $this->line((string) json_encode($counts, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->table(['table', 'rows'], array_map(
            static fn (string $table, int $count): array => [$table, number_format($count)],
            array_keys($counts),
            $counts,
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<string, int>
     */
    public static function snapshot(): array
    {
        $counts = [];

        foreach (self::TABLES as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }
}
