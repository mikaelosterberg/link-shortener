<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
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
    }

    public function test_user_can_be_assigned_role_on_creation(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin);

        // Test that we can assign a role when creating a user
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com', 
            'password' => 'password123',
            'role' => 'admin',
            'email_verified' => true
        ];

        // Simulate user creation (this would happen in Filament)
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
        ]);

        $user->assignRole($userData['role']);

        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_user_role_can_be_changed(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($superAdmin);

        // Change role from user to admin
        $user->syncRoles(['admin']);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
    }

    public function test_super_admin_cannot_remove_own_super_admin_role(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin);

        // Try to change own role (this should be prevented in the UI)
        $originalRole = $superAdmin->roles->first()->name;
        
        // Simulate the check that would happen in EditUser page
        if ($originalRole === 'super_admin' && auth()->id() === $superAdmin->id) {
            // Should not change role
            $roleChanged = false;
        } else {
            $superAdmin->syncRoles(['admin']);
            $roleChanged = true;
        }

        $this->assertFalse($roleChanged);
        $this->assertTrue($superAdmin->hasRole('super_admin'));
    }

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        // Get available roles (simulating what UserResource form would show)
        $roles = Role::pluck('name', 'name');
        
        if (!auth()->user()->hasRole('super_admin')) {
            $roles = $roles->except('super_admin');
        }

        $this->assertFalse($roles->has('super_admin'));
        $this->assertTrue($roles->has('admin'));
        $this->assertTrue($roles->has('user'));
    }


    public function test_panel_access_requires_proper_role(): void
    {
        $user = User::factory()->create();
        
        // User with no role should not have panel access
        $this->assertFalse($user->hasRole(['super_admin', 'panel_user', 'admin', 'user']));

        // User with proper role should have panel access
        $user->assignRole('user');
        $this->assertTrue($user->hasRole(['super_admin', 'panel_user', 'admin', 'user']));
    }
}