<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 25 review, M-5 — the index the retention sweep and the monitor's overdue check need.
 *
 * Both ask for "not anonymised, created before X". With only `created_at` indexed, that range also
 * reads every request already anonymised, and those are never deleted. So the daily sweep, and the
 * monitor every five minutes, scanned the table's whole history, a cost that only grows.
 * `(anonymised_at, created_at)` puts the equality first: the null rows alone, in date order.
 *
 * The plain `created_at` index stays. The queue's date filters use it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table): void {
            $table->index(['anonymised_at', 'created_at'], 'support_requests_retention_index');
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table): void {
            $table->dropIndex('support_requests_retention_index');
        });
    }
};
