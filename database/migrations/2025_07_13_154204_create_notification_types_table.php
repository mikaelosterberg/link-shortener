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
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'link_health', 'system_alert', 'maintenance'
            $table->string('display_name'); // e.g., 'Link Health Alert'
            $table->text('description')->nullable();
            $table->foreignId('default_group_id')->nullable()->constrained('notification_groups')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->json('default_settings')->nullable(); // Default settings like retry attempts, frequency, etc.
            $table->timestamps();

            $table->unique('name');
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
