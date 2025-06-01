<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class ShieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin role
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        // Create other basic roles
        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => 'web',
        ]);

        // Assign super admin role to first user if exists
        $firstUser = User::first();
        if ($firstUser && !$firstUser->hasRole('super_admin')) {
            $firstUser->assignRole('super_admin');
        }
    }
}
