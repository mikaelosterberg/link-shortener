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
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index(); // 'google_analytics', 'webhook', etc.
            $table->string('key')->index(); // 'measurement_id', 'api_secret', etc.
            $table->text('value')->nullable(); // The actual setting value
            $table->boolean('is_active')->default(false);
            $table->json('metadata')->nullable(); // Additional config as JSON
            $table->timestamps();

            $table->unique(['provider', 'key']); // Prevent duplicate settings
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
    }
};
