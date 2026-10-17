<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * SEC-065 (security plan G-21) — deleting a user can no longer rewrite the audit log.
 *
 * `audit_logs.user_id` was `nullOnDelete()`: a hard delete of a user set every entry they caused to
 * "nobody", which is an UPDATE of the audit log carried out by the database on the application's
 * behalf — the one thing the model guard, the REVOKE and track:audit-grants exist to prevent. Users
 * are soft-deleted (NoHardDeleteTest), so nothing in the application loses by this; a hard delete
 * attempted anyway now fails loudly instead of silently erasing attribution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
