<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SetupRolePermissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);
        Role::create(['name' => 'panel_user']);

        // Create some test permissions
        $permissions = [
            'view_link', 'create_link', 'update_link', 'delete_link', 'view_any_link', 'delete_any_link',
            'view_link::group', 'create_link::group', 'update_link::group', 'delete_link::group', 'view_any_link::group', 'delete_any_link::group',
            'view_api::key', 'create_api::key', 'update_api::key', 'delete_api::key', 'view_any_api::key',
            'widget_OverviewStatsWidget', 'widget_LinkHealthWidget', 'widget_GeographicStatsWidget', 'widget_ClickTrendsChart', 'widget_TopLinksWidget',
            'page_UserProfile',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    public function test_setup_command_assigns_default_permissions(): void
    {
        $this->artisan('roles:setup')
            ->expectsOutput('🔧 Setting up role permissions...')
            ->expectsOutput('✅ Role permissions setup complete!')
            ->assertExitCode(0);

        // Check admin role permissions
        $adminRole = Role::findByName('admin');
        $this->assertTrue($adminRole->hasPermissionTo('view_link'));
        $this->assertTrue($adminRole->hasPermissionTo('create_link'));
        $this->assertTrue($adminRole->hasPermissionTo('widget_OverviewStatsWidget'));
        $this->assertTrue($adminRole->hasPermissionTo('page_UserProfile'));

        // Check user role permissions
        $userRole = Role::findByName('user');
        $this->assertTrue($userRole->hasPermissionTo('view_link'));
        $this->assertTrue($userRole->hasPermissionTo('create_link'));
        $this->assertTrue($userRole->hasPermissionTo('widget_OverviewStatsWidget'));
        $this->assertFalse($userRole->hasPermissionTo('widget_LinkHealthWidget')); // Admin only

        // Check panel_user role permissions
        $panelUserRole = Role::findByName('panel_user');
        $this->assertTrue($panelUserRole->hasPermissionTo('view_link'));
        $this->assertTrue($panelUserRole->hasPermissionTo('page_UserProfile'));
        $this->assertFalse($panelUserRole->hasPermissionTo('create_link')); // View only
    }

    public function test_setup_command_with_specific_role(): void
    {
        $this->artisan('roles:setup', ['--role' => ['admin']])
            ->expectsOutput('✅ Set up admin role with')
            ->assertExitCode(0);

        // Only admin role should be configured
        $adminRole = Role::findByName('admin');
        $userRole = Role::findByName('user');

        $this->assertTrue($adminRole->hasPermissionTo('view_link'));
        $this->assertFalse($userRole->hasPermissionTo('view_link'));
    }

    public function test_setup_command_with_reset_option(): void
    {
        // First, manually assign some permissions
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo('view_link');

        // Run setup with reset
        $this->artisan('roles:setup', ['--reset' => true])
            ->expectsOutput('🔄 Reset permissions for admin role')
            ->assertExitCode(0);

        // Permissions should be reset and then reassigned
        $this->assertTrue($adminRole->fresh()->hasPermissionTo('view_link'));
    }

    public function test_command_fails_when_no_permissions_exist(): void
    {
        // Remove all permissions
        Permission::query()->delete();

        $this->artisan('roles:setup')
            ->expectsOutput('❌ No permissions found! Please run "php artisan shield:generate --all" first.')
            ->assertExitCode(1);
    }

    public function test_command_warns_about_missing_role(): void
    {
        // Delete a role
        Role::findByName('admin')->delete();

        $this->artisan('roles:setup')
            ->expectsOutput("⚠️  Role 'admin' not found, skipping...")
            ->assertExitCode(0);
    }

    public function test_command_handles_missing_permissions_gracefully(): void
    {
        // Remove some permissions that the command expects
        Permission::where('name', 'widget_LinkHealthWidget')->delete();

        $this->artisan('roles:setup')
            ->expectsOutputToContain('⚠️  Some permissions don\'t exist for admin')
            ->assertExitCode(0);

        // Should still assign existing permissions
        $adminRole = Role::findByName('admin');
        $this->assertTrue($adminRole->hasPermissionTo('view_link'));
        $this->assertFalse($adminRole->hasPermissionTo('widget_LinkHealthWidget'));
    }

    public function test_command_displays_role_summary(): void
    {
        $this->artisan('roles:setup')
            ->expectsOutput('📋 Summary:')
            ->expectsOutputToContain('admin:')
            ->expectsOutputToContain('user:')
            ->expectsOutputToContain('panel_user:')
            ->expectsOutputToContain('Full link management + dashboard access')
            ->assertExitCode(0);
    }
}
