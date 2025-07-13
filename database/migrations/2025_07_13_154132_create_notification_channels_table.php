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
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_group_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Channel name (e.g., "DevOps Email", "Slack #alerts")
            $table->enum('type', ['email', 'webhook', 'slack', 'discord', 'teams']); // Channel type
            $table->json('config'); // Channel-specific configuration (email address, webhook URL, etc.)
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Additional settings like retry attempts, delays, etc.
            $table->timestamps();

            $table->index(['notification_group_id', 'type']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
