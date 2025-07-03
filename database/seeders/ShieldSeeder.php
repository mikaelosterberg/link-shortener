<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ShieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['super_admin', 'admin', 'user'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $this->command->info("Created role: {$roleName}");
        }

        // Assign super admin role to first user if exists
        $firstUser = User::first();
        if ($firstUser && ! $firstUser->hasRole('super_admin')) {
            $firstUser->assignRole('super_admin');
            $this->command->info("Assigned super_admin role to: {$firstUser->email}");
        }
    }
}
