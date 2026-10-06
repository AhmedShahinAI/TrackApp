<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T017 — the organization hierarchy.
 *
 * The constitution requires that these tables "exist from v1 with a single college record": the
 * university/college split is real in the institution even though Phase 1 ships no screen that
 * manages it, and a self-referencing parent is cheaper now than a data migration later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['university', 'college']);
            $table->string('name_ar');
            $table->string('name_en');
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
