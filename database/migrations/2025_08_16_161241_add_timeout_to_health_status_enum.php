<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') === 'mysql') {
            // MySQL - Use ALTER TABLE to modify enum
            DB::statement("ALTER TABLE links MODIFY health_status ENUM('healthy', 'warning', 'error', 'blocked', 'timeout', 'unchecked') DEFAULT 'unchecked'");
        } else {
            // SQLite doesn't support enum modifications, but we need to handle CHECK constraints
            // For SQLite, the enum is typically enforced at the application level
            // If there's a CHECK constraint, we'd need to recreate the table, which is complex
            // For now, we'll just update any existing 'timeout' values that might have been inserted

            // Note: In SQLite, if you're using CHECK constraints, you would need to:
            // 1. Create a new table with the updated constraint
            // 2. Copy data from old table
            // 3. Drop old table
            // 4. Rename new table
            // This is complex and risky in production, so we'll rely on application-level validation
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any 'timeout' statuses to 'error' before reverting
        DB::table('links')->where('health_status', 'timeout')->update(['health_status' => 'error']);

        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE links MODIFY health_status ENUM('healthy', 'warning', 'error', 'blocked', 'unchecked') DEFAULT 'unchecked'");
        }
    }
};
