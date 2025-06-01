<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupRolePermissions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'roles:setup 
                            {--reset : Reset all role permissions before setting up defaults}
                            {--role=* : Only setup specific roles (admin, user, panel_user)}';

    /**
     * The console command description.
     */
    protected $description = 'Set up logical default permissions for all roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Setting up role permissions...');

        // Check if Shield permissions exist
        if (Permission::count() === 0) {
            $this->error('❌ No permissions found! Please run "php artisan shield:generate --all" first.');
            return 1;
        }

        $rolesToSetup = $this->option('role');
        if (empty($rolesToSetup)) {
            $rolesToSetup = ['admin', 'user', 'panel_user'];
        }

        if ($this->option('reset')) {
            $this->resetRolePermissions($rolesToSetup);
        }

        foreach ($rolesToSetup as $roleName) {
            $this->setupRolePermissions($roleName);
        }

        $this->info('✅ Role permissions setup complete!');
        $this->newLine();
        $this->info('📋 Summary:');
        $this->displayRoleSummary();

        return 0;
    }

    /**
     * Reset permissions for specified roles
     */
    private function resetRolePermissions(array $roles): void
    {
        foreach ($roles as $roleName) {
            $role = Role::findByName($roleName);
            if ($role) {
                $role->syncPermissions([]);
                $this->info("🔄 Reset permissions for {$roleName} role");
            }
        }
    }

    /**
     * Set up permissions for a specific role
     */
    private function setupRolePermissions(string $roleName): void
    {
        try {
            $role = Role::findByName($roleName);
        } catch (\Exception $e) {
            $this->warn("⚠️  Role '{$roleName}' not found, skipping...");
            return;
        }

        $permissions = $this->getDefaultPermissions($roleName);
        
        if (empty($permissions)) {
            $this->warn("⚠️  No default permissions defined for '{$roleName}' role");
            return;
        }

        // Filter to only existing permissions
        $existingPermissions = Permission::whereIn('name', $permissions)->pluck('name')->toArray();
        $missingPermissions = array_diff($permissions, $existingPermissions);

        if (!empty($missingPermissions)) {
            $this->warn("⚠️  Some permissions don't exist for {$roleName}: " . implode(', ', $missingPermissions));
        }

        $role->syncPermissions($existingPermissions);
        $this->info("✅ Set up {$roleName} role with " . count($existingPermissions) . " permissions");
    }

    /**
     * Get default permissions for each role
     */
    private function getDefaultPermissions(string $roleName): array
    {
        return match($roleName) {
            'admin' => [
                // Link Management (Full Access)
                'view_any_link',
                'view_link', 
                'create_link',
                'update_link',
                'delete_link',
                'delete_any_link',
                
                // Link Groups (Full Access)
                'view_any_link::group',
                'view_link::group',
                'create_link::group', 
                'update_link::group',
                'delete_link::group',
                'delete_any_link::group',
                
                // API Keys (Own keys only)
                'view_any_api::key',
                'view_api::key',
                'create_api::key',
                'update_api::key',
                'delete_api::key',
                
                // Dashboard Widgets
                'widget_OverviewStatsWidget',
                'widget_LinkHealthWidget',
                'widget_GeographicStatsWidget', 
                'widget_ClickTrendsChart',
                'widget_TopLinksWidget',
                
                // Profile Management
                'page_UserProfile',
            ],
            
            'user' => [
                // Basic Link Access
                'view_any_link',
                'view_link',
                'create_link',
                'update_link',
                'delete_link', // Only own links due to policies
                
                // View Groups (for categorization)
                'view_any_link::group',
                'view_link::group',
                
                // Basic API Access
                'view_any_api::key',
                'view_api::key',
                'create_api::key',
                'update_api::key',
                'delete_api::key',
                
                // Basic Widgets
                'widget_OverviewStatsWidget',
                'widget_ClickTrendsChart',
                
                // Profile
                'page_UserProfile',
            ],
            
            'panel_user' => [
                // Minimal access - just view their own data
                'view_link',
                'view_link::group',
                'page_UserProfile',
            ],
            
            default => []
        };
    }

    /**
     * Display summary of role permissions
     */
    private function displayRoleSummary(): void
    {
        $roles = ['super_admin', 'admin', 'user', 'panel_user'];
        
        foreach ($roles as $roleName) {
            $role = Role::findByName($roleName);
            if (!$role) continue;
            
            $permissionCount = $role->permissions->count();
            
            $description = match($roleName) {
                'super_admin' => 'Unrestricted access to everything (automatic)',
                'admin' => 'Full link management + dashboard access',
                'user' => 'Basic link management + limited dashboard',
                'panel_user' => 'View-only access to own data',
                default => 'Unknown role'
            };
            
            $this->line("  🔹 <info>{$roleName}</info>: {$permissionCount} permissions - {$description}");
        }

        $this->newLine();
        $this->info('💡 Tip: You can customize these permissions anytime in the admin panel at Settings → Roles');
    }
}