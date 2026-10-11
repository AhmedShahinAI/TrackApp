<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T360 + T361 — one row per support request a student raised through the public form (Amendment 2).
 *
 * `students` is not altered. No credential, contact or activation column is added there, which is
 * why the support channel needed no change to the Group 1–6 schema (data-model.md Group 7).
 *
 * Three columns are shaped by the retention sweep (research.md R-12), which clears `student_id`,
 * `contact` and `submitted_ip` after `support_retention_months` and stamps `anonymised_at`:
 *
 *   • `student_id` is **nullable only so it can be cleared**. Creation always sets it — the action
 *     that writes a request (T390) has just verified the student — and nothing else may null it.
 *   • `restrictOnDelete`, not cascade: a student is soft-deleted, never removed (constitution V),
 *     and a hard delete that took a student's support history with it is exactly what that forbids.
 *
 * `category` is a string checked against the `support_categories` setting, so adding one needs no
 * migration (constitution IV). `status` is a database enum because it is a workflow contract the
 * state machine, the queue filters and five years of audit history depend on — see data-model.md
 * "Why status is a database enum while category is not".
 *
 * `reference` is `char(11)`, not the `char(12)` T360 names: ten random Crockford base32 characters
 * (50 bits, research.md R-11) plus one check character, stored **canonical** — uppercase, no
 * hyphens. The hyphens (`K7Q2-M9XV-T3X`) are presentation, added on output and stripped from input,
 * so a student who types the reference with or without them, or in lower case, reaches the same
 * row. T360's `char(12)` was sized to R-11's example, which shows ten symbols in all and so cannot
 * be ten plus a check; the rule and its entropy figure are what this follows (tasks.md Phase 19).
 * It is the student's only credential for reading the request back, so it is unique and never
 * updated (T363).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('reference', 11);
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
            $table->string('category', 40);
            $table->text('description');
            $table->string('contact', 255)->nullable();
            $table->enum('status', ['new', 'in_progress', 'resolved', 'closed', 'rejected'])->default('new');
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submitted_ip', 45);
            $table->timestamp('anonymised_at')->nullable();
            $table->timestamps();

            // T361. Each index names the query that needs it.
            $table->unique('reference');                  // status retrieval, and the collision retry
            $table->index(['status', 'created_at']);      // the queue's oldest-first default
            $table->index('student_id');                  // the branch-scope join through enrollments
            $table->index(['category', 'created_at']);    // the category filter
            $table->index('assigned_to');                 // "assigned to me"
            $table->index('created_at');                  // the retention sweep
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
