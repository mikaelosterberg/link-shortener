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
        // Create report-specific permissions following Shield naming convention
        $permissions = [
            'view_any_report',
            'view_report',
            'create_report',
            'update_report',
            'update_any_report',
            'delete_report',
            'delete_any_report',
            'force_delete_report',
            'force_delete_any_report',
            'restore_report',
            'restore_any_report',
            'replicate_report',
            'reorder_report',
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
            $admin->givePermissionTo([
                'view_any_report',
                'view_report',
                'create_report',
                'update_report',
                'delete_report',
                'replicate_report',
            ]);
        }

        $user = Role::where('name', 'user')->first();
        if ($user) {
            $user->givePermissionTo([
                'view_any_report',
                'view_report',
                'create_report',
                'update_report',
                'delete_report',
                'replicate_report',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'view_any_report',
            'view_report',
            'create_report',
            'update_report',
            'update_any_report',
            'delete_report',
            'delete_any_report',
            'force_delete_report',
            'force_delete_any_report',
            'restore_report',
            'restore_any_report',
            'replicate_report',
            'reorder_report',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
