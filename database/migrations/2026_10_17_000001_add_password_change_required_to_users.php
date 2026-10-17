<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * SEC-047 — the owner's decision D-3 (security plan G-04, 2026-10-17): a password someone else set
 * must be changed by its owner before they do anything else.
 *
 * Set when a Super Admin creates an account or resets a password; cleared when the owner changes it.
 * Default false, so every existing account and every fixture is unaffected until a password is next
 * set for it. Not fillable: only CreateUser, ResetPassword and ChangePassword write it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('password_change_required')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password_change_required');
        });
    }
};
