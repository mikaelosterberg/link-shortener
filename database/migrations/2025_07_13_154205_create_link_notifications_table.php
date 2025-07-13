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
        Schema::create('link_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->onDelete('cascade');
            $table->foreignId('notification_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('notification_type_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Override settings for this specific assignment
            $table->timestamps();

            $table->unique(['link_id', 'notification_group_id', 'notification_type_id'], 'link_notification_unique');
            $table->index(['link_id', 'is_active']);
            $table->index(['notification_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_notifications');
    }
};
