<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T028 + T298 - enrollments.
 *
 * status and academic_status are two columns on purpose (FR-110, FR-111). status is TRACK's own
 * state, set by a person with a mandatory written reason and an audit entry. academic_status is the
 * registration unit's standing, arriving in an import file and overwritten by the next import of
 * that file. Collapsing them means either an import silently erasing a TRACK withdrawal and its
 * reason, or a TRACK withdrawal misstating the registrar's record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['active', 'withdrawn'])->default('active');
            $table->timestamp('enrolled_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('withdraw_reason', 500)->nullable();
            $table->enum('academic_status', ['regular', 'withdrawn', 'excused'])->nullable();
            $table->unsignedInteger('roster_sequence')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'section_id']);
            $table->index(['section_id', 'status']);

            // FR-111 — the roster's "is this section sequenced?" probe and its registrar order,
            // both answered from the index rather than by sorting the section's rows.
            $table->index(['section_id', 'roster_sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_enrollments');
    }
};
