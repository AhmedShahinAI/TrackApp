<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T286 — Group 6, part one: an initiative, its versioned field schema, and the sections it runs in
 * (data-model.md Group 6, FR-087).
 *
 * Created in this feature **solely** so the capacity spike (Phase 12) measures the real schema: the
 * index composition and column types are what is under test, so a stand-in table would prove nothing
 * (plan.md Complexity Tracking). No controller, route, policy or view touches these tables in this
 * feature — tests/Architecture/EavSpikeContainmentTest.php fails if one does.
 *
 * `initiatives.current_schema_version_id` and `initiative_schema_versions.initiative_id` point at each
 * other. The version's key is the real foreign key; the initiative's pointer is an indexed column
 * without a constraint, because a circular constraint makes every insert a two-step dance and the
 * pointer is only ever set by the publish action that also creates the version (Phase 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initiatives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('short_name', 60)->nullable();
            $table->text('description')->nullable();
            $table->text('objective')->nullable();
            $table->text('instructions')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // A workflow state Phase 2 defines; a string here so the spike does not pre-empt it.
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('current_schema_version_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['term_id', 'status']);
        });

        Schema::create('initiative_schema_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('initiative_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['initiative_id', 'version_number']);
        });

        Schema::create('initiative_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('schema_version_id')->constrained('initiative_schema_versions')->restrictOnDelete();
            $table->string('field_key', 60);
            $table->string('label_ar');
            $table->string('label_en');
            // One of the PRD's 15 dynamic field types (ui-conflicts C-08); validated in Phase 2.
            $table->string('type', 30);
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('visible_to_instructor')->default(true);
            $table->boolean('editable_by_instructor')->default(true);
            $table->boolean('show_in_reports')->default(true);
            $table->boolean('required_for_completion')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['schema_version_id', 'field_key']);
        });

        Schema::create('initiative_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('initiative_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('return_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['initiative_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initiative_sections');
        Schema::dropIfExists('initiative_fields');
        Schema::dropIfExists('initiative_schema_versions');
        Schema::dropIfExists('initiatives');
    }
};
