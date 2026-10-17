<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T451 — the retention sweep clears `submitted_ip` (research.md R-12, data-model.md "Cleared on
 * anonymisation"), and the column was created NOT NULL. Clearing it to an empty string would keep a
 * row that reads as "recorded, and blank" rather than "removed"; null is what cleared means.
 *
 * Every accepted request still records its address (FR-123): SubmitSupportRequest always sets it, so
 * nullable here admits only the sweep's null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table): void {
            $table->string('submitted_ip', 45)->nullable()->change();
        });
    }

    /**
     * Reversible once rows are anonymised, too (Phase 25 review, L-1). The cleared addresses cannot
     * come back, so they are written as '' before NOT NULL returns; without this, the ALTER fails on
     * the first anonymised row.
     */
    public function down(): void
    {
        DB::table('support_requests')->whereNull('submitted_ip')->update(['submitted_ip' => '']);

        Schema::table('support_requests', function (Blueprint $table): void {
            $table->string('submitted_ip', 45)->nullable(false)->change();
        });
    }
};
