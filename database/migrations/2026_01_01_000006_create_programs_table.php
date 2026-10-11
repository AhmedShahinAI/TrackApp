<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T294 (Amendment 1, FR-089) — specialisation programmes.
 *
 * Scheduled with Phase 2 rather than with the programmes screens, because `sections.program_id` is
 * non-nullable and therefore this table must exist before the sections migration runs. Adding it
 * afterwards would mean backfilling 3,000 sections with no source of truth for the right value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('unit', ['admin', 'tech']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Not (organization_id, code, deleted_at): FR-089 makes a code unique within the
            // institution, and a soft-deleted programme still names the sections that point at it.
            $table->unique(['organization_id', 'code']);
        });

        self::requireNormalisedCode();
    }

    /**
     * T294, engine-level half. Program::code stores the code trimmed and uppercase, but only for
     * Eloquent writes — a query-builder update(), insert() or upsert() skips the mutator. On a
     * case-sensitive collation that lets `hr` sit beside `HR`; on a case-insensitive one it stores a
     * lowercase code the rest of the platform never expects. Refused here, whoever writes it.
     */
    private static function requireNormalisedCode(): void
    {
        match (DB::connection()->getDriverName()) {
            // Binary comparison: under utf8mb4_unicode_ci, 'hr' = UPPER('hr') is true.
            'mysql', 'mariadb' => DB::statement(
                "ALTER TABLE programs ADD CONSTRAINT programs_code_normalised CHECK (code <> '' AND CAST(code AS BINARY) = CAST(UPPER(TRIM(code)) AS BINARY))",
            ),
            'pgsql' => DB::statement(
                "ALTER TABLE programs ADD CONSTRAINT programs_code_normalised CHECK (code <> '' AND code = UPPER(BTRIM(code)))",
            ),
            // SQLite cannot add a CHECK to an existing table; a trigger per write does the same job.
            'sqlite' => array_map(DB::statement(...), [
                "CREATE TRIGGER programs_code_normalised_insert BEFORE INSERT ON programs
                    WHEN NEW.code = '' OR NEW.code <> UPPER(TRIM(NEW.code))
                    BEGIN SELECT RAISE(ABORT, 'programs.code must be non-empty, trimmed and uppercase'); END",
                "CREATE TRIGGER programs_code_normalised_update BEFORE UPDATE OF code ON programs
                    WHEN NEW.code = '' OR NEW.code <> UPPER(TRIM(NEW.code))
                    BEGIN SELECT RAISE(ABORT, 'programs.code must be non-empty, trimmed and uppercase'); END",
            ]),
            default => null,
        };
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
