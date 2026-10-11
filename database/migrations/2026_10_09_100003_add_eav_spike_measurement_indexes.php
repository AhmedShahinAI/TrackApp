<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T288 — the two indexes under measurement, kept apart so the spike can run with and without them
 * (data-model.md Group 6, research.md R-06).
 *
 *   student_initiative_values(field_id, value_bool)        — completion scans: "every record whose
 *                                                             required Yes/No field is Yes"
 *   student_initiative_records(assignment_id, is_complete) — per-section completion counts
 *
 * Whether they ship is decided by spike-eav-results.md, not in advance — so this migration is a no-op
 * for the application (`eav_spike.measurement_indexes` is false) and creates them only in the spike's
 * own database, where the harness switches them on for the "with" run. The decision, once the MySQL
 * run has made it, lands as an ordinary unconditional migration; this one stays a no-op.
 *
 * It is still recorded in `migrations` when it does nothing — `migrate:status` reports it as run on
 * every database, which is true: it ran, and its answer there was "not here".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! (bool) config('eav_spike.measurement_indexes', false)) {
            return;
        }

        Schema::table('student_initiative_values', function (Blueprint $table): void {
            $table->index(['field_id', 'value_bool'], 'siv_field_bool_index');
        });

        Schema::table('student_initiative_records', function (Blueprint $table): void {
            $table->index(['assignment_id', 'is_complete'], 'sir_assignment_complete_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasIndex('student_initiative_values', 'siv_field_bool_index')) {
            Schema::table('student_initiative_values', fn (Blueprint $table) => $table->dropIndex('siv_field_bool_index'));
        }

        if (Schema::hasIndex('student_initiative_records', 'sir_assignment_complete_index')) {
            Schema::table('student_initiative_records', fn (Blueprint $table) => $table->dropIndex('sir_assignment_complete_index'));
        }
    }
};
