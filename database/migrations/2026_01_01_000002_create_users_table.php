<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T018 — accounts, plus the two framework-owned auth tables Laravel keeps beside them.
 *
 * Replaces the default Laravel users migration, which had to move behind `organizations` for the
 * foreign key. There is no `name` column: FR-062 requires every display name in both languages, so
 * `name_ar` and `name_en` replace it rather than supplement it.
 *
 * No `email_verified_at`: the constitution forbids self-registration, so there is no address to
 * verify — the SUPER_ADMIN creates the account and the address is a given.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('name_ar');
            $table->string('name_en');

            // Unique on `email` alone, deliberately not `(email, deleted_at)`. FR-005 requires a
            // deactivated account's address to stay taken: reusing it would silently transfer the
            // deactivated person's audit trail to whoever got the address next.
            $table->string('email')->unique();

            $table->string('password');
            $table->char('locale', 2)->default('ar');

            // Distinct from `deleted_at` on purpose. Deactivation is reversible and keeps the row
            // fully visible to an administrator; a soft delete is the end of the account's life.
            $table->boolean('is_active')->default(true);

            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
