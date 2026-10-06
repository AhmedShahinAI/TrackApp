<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T034 - the flat error report (FR-071, PRD 11.4).
 *
 * Materialized from import_rows at report time rather than written during validation. The staging
 * table already holds the reasons as structured JSON; this one exists so the downloadable report and
 * any later query have a stable flat shape that does not change when the reason structure does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_errors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_job_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('column', 100)->nullable();
            $table->string('value', 500)->nullable();
            $table->string('reason', 500);

            $table->index('import_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
