<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T032 + T299 - one row per import run, carrying the whole six-step wizard's state.
 *
 * mode holds all five values from the start (FR-096, FR-103, FR-104); adding them later would mean
 * an ALTER on a table the import pipeline is actively writing to.
 *
 * last_committed_row_id is the resume checkpoint from research.md R-03: a commit that dies at row
 * 12,400 of 50,000 restarts after the last committed row rather than re-importing from the top.
 * Which row that was has to survive the process that died, so it is a column rather than state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('mode', ['central', 'section', 'registrar', 'course', 'user']);
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('file_metadata')->nullable();
            $table->string('original_filename');
            $table->string('file_path', 500);
            $table->char('file_hash', 64);
            $table->json('mapping')->nullable();
            $table->json('options')->nullable();
            $table->enum('status', [
                'uploaded', 'mapped', 'validating', 'validated', 'committing', 'completed', 'failed',
            ]);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('imported')->default(0);
            $table->unsignedInteger('duplicates')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->unsignedBigInteger('last_committed_row_id')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['file_hash', 'term_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
