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
        Schema::table('clicks', function (Blueprint $table) {
            $table->foreignId('ab_test_variant_id')->nullable()->after('utm_content')->constrained()->onDelete('set null');
            $table->index('ab_test_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropForeign(['ab_test_variant_id']);
            $table->dropIndex(['ab_test_variant_id']);
            $table->dropColumn('ab_test_variant_id');
        });
    }
};
