<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T295 (Amendment 1, FR-091) — a course belongs to many programmes and a programme holds many
 * courses. Cascade on both sides: the pivot row carries no information of its own, so losing it
 * with either parent loses nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_program', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();

            $table->unique(['course_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_program');
    }
};
