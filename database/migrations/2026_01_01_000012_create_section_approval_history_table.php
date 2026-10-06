<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T472 (Amendment 4, FR-182) - every approval transition a section went through.
 *
 * No updated_at column. That absence is the schema-level statement that the table is append-only:
 * there is nowhere to record a modification, so a row cannot be quietly rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_approval_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->enum('from_status', ['pending', 'returned', 'rejected', 'active'])->nullable();
            $table->enum('to_status', ['pending', 'returned', 'rejected', 'active']);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['section_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_approval_history');
    }
};
