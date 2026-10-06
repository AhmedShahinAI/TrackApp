<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T021 — academic terms.
 *
 * `is_current` carries no partial unique index: MySQL has none, so FR-036's "exactly one current
 * term" is enforced by the SetCurrentTerm action inside a transaction, with a test asserting the
 * invariant holds after concurrent attempts. The index below only makes the lookup cheap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('academic_year', 9);
            $table->enum('semester', ['first', 'second', 'summer']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'closed', 'archived'])->default('draft');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_current');
            $table->index(['academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
