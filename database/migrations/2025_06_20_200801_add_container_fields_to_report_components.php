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
        Schema::table('report_components', function (Blueprint $table) {
            $table->foreignId('container_id')->nullable()->after('report_id')->constrained('report_containers')->onDelete('cascade');

            // Flexbox properties for individual components
            $table->string('flex_basis')->default('auto')->after('height'); // e.g., '25%', '300px', 'auto'
            $table->integer('flex_grow')->default(1)->after('flex_basis');
            $table->integer('flex_shrink')->default(1)->after('flex_grow');

            $table->index(['container_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_components', function (Blueprint $table) {
            $table->dropForeign(['container_id']);
            $table->dropIndex(['container_id', 'order_index']);
            $table->dropColumn(['container_id', 'flex_basis', 'flex_grow', 'flex_shrink']);
        });
    }
};
