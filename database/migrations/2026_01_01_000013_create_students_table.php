<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T027 - students. university_id is unique across the table including soft-deleted rows, because the
 * identifier belongs to the person rather than to the enrollment; its format is checked against the
 * university_id_pattern setting rather than hard-coded (FR-043, FR-044, constitution IV).
 *
 * name_en is nullable: registrar exports frequently omit it, and refusing the row over a missing
 * English name would reject a valid student.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('university_id', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
