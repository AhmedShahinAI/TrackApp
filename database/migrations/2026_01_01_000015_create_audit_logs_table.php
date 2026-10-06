<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T029 - the audit log (constitution II, FR-028 to FR-034).
 *
 * There is no updated_at column. That is the first of the three immutability layers: the table has
 * nowhere to record a modification, so an update is not merely forbidden by convention - it has no
 * place to land. The second layer is the absence of any update path in the model and the routes; the
 * third is the engine-level REVOKE in the next migration.
 *
 * user_id is nullable because the most security-relevant entries happen before authentication
 * succeeds: a failed sign-in has no user to attribute (FR-029).
 *
 * The four indexes are not speculative. They are exactly the FR-032 filter set, and SC-004 requires
 * a user plus date-range filter to return within two seconds over 1,000,000 rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
