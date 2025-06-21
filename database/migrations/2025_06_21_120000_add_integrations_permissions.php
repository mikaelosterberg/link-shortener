<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create integrations-specific permissions following Shield's page naming convention
        $permissions = [
            'page_IntegrationsSettings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to existing roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Users typically shouldn't access integrations settings
        // Only admins and super admins should have access to these system-level settings
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'page_IntegrationsSettings',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
