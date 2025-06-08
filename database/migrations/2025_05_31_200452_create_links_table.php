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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('short_code', 50)->unique();
            $table->text('original_url');
            $table->foreignId('group_id')->nullable()->constrained('link_groups')->nullOnDelete();
            $table->integer('redirect_type')->default(302);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('click_count')->default(0);
            $table->string('custom_slug')->nullable();
            $table->timestamps();

            $table->index('short_code');
            $table->index('is_active');
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
