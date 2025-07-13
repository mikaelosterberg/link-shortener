<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification_types', function (Blueprint $table) {
            // Add new JSON column for multiple groups
            $table->json('default_groups')->nullable()->after('description');
        });

        // Migrate existing data from single group to multiple groups
        $notificationTypes = DB::table('notification_types')->whereNotNull('default_group_id')->get();

        foreach ($notificationTypes as $type) {
            DB::table('notification_types')
                ->where('id', $type->id)
                ->update([
                    'default_groups' => json_encode([$type->default_group_id]),
                ]);
        }

        Schema::table('notification_types', function (Blueprint $table) {
            // Drop the old single group column
            $table->dropForeign(['default_group_id']);
            $table->dropColumn('default_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_types', function (Blueprint $table) {
            // Add back the single group column
            $table->foreignId('default_group_id')->nullable()->constrained('notification_groups')->after('description');
        });

        // Migrate data back (take first group from array)
        $notificationTypes = DB::table('notification_types')->whereNotNull('default_groups')->get();

        foreach ($notificationTypes as $type) {
            $groups = json_decode($type->default_groups, true);
            $firstGroup = is_array($groups) && ! empty($groups) ? $groups[0] : null;

            if ($firstGroup) {
                DB::table('notification_types')
                    ->where('id', $type->id)
                    ->update(['default_group_id' => $firstGroup]);
            }
        }

        Schema::table('notification_types', function (Blueprint $table) {
            // Drop the multiple groups column
            $table->dropColumn('default_groups');
        });
    }
};
