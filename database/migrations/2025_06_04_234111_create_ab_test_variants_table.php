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
        Schema::create('ab_test_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url');
            $table->integer('weight')->default(50);
            $table->integer('click_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->timestamps();

            $table->index(['ab_test_id', 'weight']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ab_test_variants');
    }
};
