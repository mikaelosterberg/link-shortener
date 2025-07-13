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
        Schema::table('notification_types', function (Blueprint $table) {
            $table->boolean('notify_link_owner')->default(false)->after('default_group_id');
            $table->json('apply_to_link_groups')->nullable()->after('notify_link_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_types', function (Blueprint $table) {
            $table->dropColumn(['notify_link_owner', 'apply_to_link_groups']);
        });
    }
};
