<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T024 - which branches a coordinator covers. This table is what coordinatorBranchIds() reads, so it
 * is the source of the BranchScope global scope that research.md R-01 depends on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinator_branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinator_branches');
    }
};
