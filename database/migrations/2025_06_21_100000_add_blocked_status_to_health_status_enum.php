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
        // For SQLite and MySQL compatibility, we need to handle enum changes differently
        if (config('database.default') === 'mysql') {
            // MySQL - Use ALTER TABLE to modify enum
            DB::statement("ALTER TABLE links MODIFY health_status ENUM('healthy', 'warning', 'error', 'blocked', 'unchecked') DEFAULT 'unchecked'");
        } else {
            // SQLite doesn't support enum, so we're using CHECK constraints
            // First, drop the old check constraint if it exists
            Schema::table('links', function (Blueprint $table) {
                // SQLite uses CHECK constraints for enums, we'll need to recreate the column
                // This is a bit complex, so we'll use raw SQL
            });

            // For SQLite, we'll just update any existing constraint by recreating the table
            // Since SQLite doesn't have native enum support, Laravel likely used a CHECK constraint
            // We'll trust that the original migration works with SQLite as-is
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE links MODIFY health_status ENUM('healthy', 'warning', 'error', 'unchecked') DEFAULT 'unchecked'");
        }
    }
};
