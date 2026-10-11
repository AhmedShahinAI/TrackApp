<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T287 — Group 6, part two: who an assignment targets, one record per targeted student, and the typed
 * EAV values that fill it (data-model.md Group 6, FR-087).
 *
 * `student_initiative_values` is the table the spike exists to measure: 1,500,000 rows, one per
 * (record, field), with the value in exactly one of five typed columns. Typed columns rather than one
 * text column so a number compares as a number and a date as a date, and so an index on `value_bool`
 * can serve a completion scan (the index itself is the separate migration T288, because whether it
 * ships is the spike's decision).
 *
 * `attachment_id` is a plain column: `attachments` belongs to the phase that stores files, not to this
 * feature (data-model.md Group 6, final note).
 *
 * Measurement-only in this feature — see the containment note on the previous migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initiative_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained('initiative_sections')->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('origin', 20)->default('roster');
            $table->timestamp('removed_at')->nullable();
            $table->string('removal_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
        });

        Schema::create('student_initiative_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained('initiative_sections')->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->boolean('is_complete')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
        });

        Schema::create('student_initiative_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('record_id')->constrained('student_initiative_records')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('initiative_fields')->restrictOnDelete();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 18, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->boolean('value_bool')->nullable();
            $table->json('value_json')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();

            // The record's grid is read through this index's leading column.
            $table->unique(['record_id', 'field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_initiative_values');
        Schema::dropIfExists('student_initiative_records');
        Schema::dropIfExists('initiative_targets');
    }
};
