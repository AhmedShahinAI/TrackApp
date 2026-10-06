<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T294 (Amendment 1, FR-089) — specialisation programmes.
 *
 * Scheduled with Phase 2 rather than with the programmes screens, because `sections.program_id` is
 * non-nullable and therefore this table must exist before the sections migration runs. Adding it
 * afterwards would mean backfilling 3,000 sections with no source of truth for the right value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('unit', ['admin', 'tech']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
