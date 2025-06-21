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
        Schema::create('report_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->string('name')->default('Row');
            $table->integer('order_index')->default(0);

            // Flexbox layout properties
            $table->enum('flex_direction', ['row', 'column'])->default('row');
            $table->enum('justify_content', ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'])->default('flex-start');
            $table->enum('align_items', ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'])->default('stretch');
            $table->string('gap')->default('16px'); // CSS gap value
            $table->string('min_height')->default('auto'); // e.g., 'auto', '200px', '50vh'

            $table->timestamps();

            $table->index(['report_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_containers');
    }
};
