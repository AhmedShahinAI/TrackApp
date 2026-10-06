<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T025 - shared section access. Feeds the second half of accessibleSectionIds(): the union of
 * sections a user teaches and sections explicitly granted to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_access_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('granted_at');
            $table->timestamps();

            $table->unique(['section_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_access_grants');
    }
};
