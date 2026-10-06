<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T033 - the staging table (FR-060, FR-065, research.md R-03).
 *
 * This table is the reason no core row is written before the operator confirms the preview, and the
 * reason the preview and the downloadable error report are cheap reads rather than a second parse of
 * the uploaded file. Without it, "show me what will happen" and "do it" would each have to parse the
 * file, and the two answers could differ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_job_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw');
            $table->json('resolved')->nullable();
            $table->enum('status', ['valid', 'warning', 'error', 'committed', 'skipped']);
            $table->json('reasons')->nullable();

            $table->index(['import_job_id', 'status']);
            $table->index(['import_job_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
