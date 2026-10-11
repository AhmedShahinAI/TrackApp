<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T238 - Laravel's standard database-notification table, unmodified.
 *
 * FR-072 requires the person who ran an import to be told when it finishes. In this feature the
 * wizard's own poller does the telling; this table exists so the notification-centre feature that
 * follows finds every finished import already recorded, rather than starting from the day it ships.
 * No email is sent and no SMTP dependency exists (contracts/imports.md, "Notification").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
