<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T362 — a staff response to a support request. Append-only.
 *
 * There is **no `updated_at` column**, and that absence is the schema-level half of the rule: a
 * response is a statement made to a student at a point in time, and editing it after the student
 * has read it would make the status page unreliable as a record. A correction is a new response
 * (data-model.md `support_responses`). The model (T364) supplies the other half — no update or
 * delete path.
 *
 * Cascade from the request because a response has no meaning without it; requests are never
 * deleted in practice (anonymisation keeps the row), so the cascade is the honest FK rather than a
 * deletion path anyone uses. `user_id` restricts, so a staff account with responses on record
 * cannot be hard-deleted out from under them — accounts are deactivated, not removed.
 *
 * Responses survive anonymisation: they carry staff text, not student personal data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            // The thread, oldest first, for one request.
            $table->index(['support_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_responses');
    }
};
