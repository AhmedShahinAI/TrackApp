<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * US5 (Phase 8) — the wizard state the status enum cannot carry.
 *
 * A new migration rather than an edit to 000018, which had already been run: an edited migration
 * changes nothing on a database that ran it, and the first upload there fails on a missing column
 * until someone runs migrate:fresh and loses the data.
 *
 *   - `summary` — the preview's create-versus-match counts (FR-061), computed once by the validation
 *     job. Recomputing on every preview would re-read up to fifty thousand staged rows.
 *   - `preview_fetched_at` — the commit's "the operator has seen what will happen" gate (409).
 *   - `commit_requested_at` — tells a failed commit (resumable) from a failed validation (not).
 *   - `queued_at`, `heartbeat_at` — whether a worker has picked the job up (C-13), and whether the
 *     worker that had it has gone quiet long enough to count as dead.
 *   - `link_only` — FR-067's "this file was already imported", decided once when the commit is
 *     confirmed. Re-deciding per chunk let the answer change halfway through a commit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_jobs', function (Blueprint $table): void {
            $table->json('summary')->nullable()->after('options');
            $table->boolean('link_only')->default(false)->after('summary');
            $table->timestamp('preview_fetched_at')->nullable()->after('finished_at');
            $table->timestamp('commit_requested_at')->nullable()->after('preview_fetched_at');
            $table->timestamp('queued_at')->nullable()->after('commit_requested_at');
            $table->timestamp('heartbeat_at')->nullable()->after('queued_at');
        });
    }

    public function down(): void
    {
        Schema::table('import_jobs', function (Blueprint $table): void {
            $table->dropColumn(['summary', 'link_only', 'preview_fetched_at', 'commit_requested_at', 'queued_at', 'heartbeat_at']);
        });
    }
};
