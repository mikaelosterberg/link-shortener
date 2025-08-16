<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('exclude_from_health_checks')->default(false);
            $table->integer('notification_count')->default(0);
            $table->timestamp('last_notification_sent_at')->nullable();
            $table->timestamp('first_failure_detected_at')->nullable();
            $table->boolean('notification_paused')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn([
                'exclude_from_health_checks',
                'notification_count',
                'last_notification_sent_at',
                'first_failure_detected_at',
                'notification_paused',
            ]);
        });
    }
};
