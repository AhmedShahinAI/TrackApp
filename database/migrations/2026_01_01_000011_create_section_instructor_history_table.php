<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T026 - who taught a section and when (FR-048).
 *
 * Append-only in practice: a reassignment closes the open row's unassigned_at and inserts a new open
 * row. Historical rows are never rewritten, which is what makes a past term's record still true
 * after this term's instructor changes (constitution V).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_instructor_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['section_id', 'assigned_at']);
            $table->index('instructor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_instructor_history');
    }
};
