<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T023 + T296 + T297 + T470 + T471 - sections, with Amendment 1's programme and descriptors and
 * Amendment 4's approval workflow folded in.
 *
 * tasks.md sequences T023 (four-column unique index) before T471 (drop it, add the generated-column
 * index). Nothing has shipped, so creating the index only to drop it in the same deployment would be
 * pure waste; the end state is identical and is built here directly.
 *
 * Gender is absent by design - it is read through branch.gender (FR-050), so there is exactly one
 * row in the database that can answer the question.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();

            // Non-nullable from the start (FR-090). Making it nullable now and tightening it later
            // means backfilling every existing section with a programme nobody can derive.
            $table->foreignId('program_id')->constrained()->restrictOnDelete();

            $table->string('section_number', 20);
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();

            // Plain strings, not database enums (FR-108, FR-109, constitution IV). The permitted
            // values live in the section_degrees and section_activities settings and are checked by
            // the InSetting rule, so adding a degree is a seeder row rather than a migration.
            $table->string('degree', 40)->default('bachelor');
            $table->string('activity', 40)->default('lecture');

            // Amendment 4 (FR-173). Default active so a Super Admin's section and every row that
            // predates the amendment need no backfill - only a submitted section is ever pending.
            $table->enum('status', ['pending', 'returned', 'rejected', 'active'])->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_reason', 500)->nullable();

            // T471 / FR-176. The section key must be unique among sections that are not rejected,
            // and MySQL has no partial index. A stored generated column that collapses to NULL for a
            // rejected row gives exactly that: two live sections collide, a rejected one releases
            // its number, and any number of rejected rows coexist because NULLs never collide in a
            // unique index.
            //
            // Adding status to the index instead fails - two rejected rows with the same number
            // would still collide with each other. Adding deleted_at fails for the same reason, and
            // also because rejection is not deletion: the row stays visible with its reason.
            $table->string('approval_key', 20)
                ->storedAs("case when status = 'rejected' then null else section_number end");

            $table->timestamps();

            $table->unique(['term_id', 'branch_id', 'course_id', 'approval_key']);
            $table->index('instructor_id');
            $table->index(['branch_id', 'term_id']);
            $table->index('program_id');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
