<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 23 review — `sections (branch_id, status)`.
 *
 * The support branch scope asks "the active sections of these branches" on every queue load, count and
 * binding (SupportRequest::holdersIn). The existing (branch_id, term_id) index finds the branch but
 * leaves status to a row check per section; this answers the whole condition from the index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            $table->index(['branch_id', 'status'], 'sections_branch_id_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            $table->dropIndex('sections_branch_id_status_index');
        });
    }
};
