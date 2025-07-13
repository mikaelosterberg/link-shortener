<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallCommand extends Command
{
    protected $signature = 'app:install';

    protected $description = 'Install the link shortener application with all required setup';

    public function handle()
    {
        $this->info('🚀 Installing Link Shortener Application...');
        $this->newLine();

        // Step 1: Check if already installed
        if ($this->isAlreadyInstalled()) {
            if (! $this->confirm('⚠️  Application appears to be already installed. Continue anyway?')) {
                $this->info('Installation cancelled.');

                return 0;
            }
        }

        // Step 2: Publish configurations first (required for migrations)
        $this->publishConfigurations();

        // Step 3: Run migrations
        $this->runMigrations();

        // Step 4: Create admin user
        $adminUser = $this->createAdminUser();

        // Step 5: Set up storage and directories
        $this->setupStorage();

        // Step 6: Set up Shield permissions
        $this->setupShield();

        // Step 7: Set up roles and permissions
        $this->setupRoles($adminUser);

        // Step 8: Fix navigation grouping
        $this->fixNavigationGrouping();

        // Step 9: Seed notification types
        $this->seedNotificationTypes();

        // Step 10: Verify Shield routes
        $this->verifyShieldRoutes();

        // Step 10: Final navigation fix (after Shield install)
        $this->finalNavigationFix();

        // Step 11: Clear caches
        $this->clearCaches();

        // Step 11: Success message
        $this->displaySuccessMessage($adminUser);

        return 0;
    }

    private function isAlreadyInstalled(): bool
    {
        try {
            return User::count() > 0 && Role::count() > 0;
        } catch (\Exception $e) {
            // Tables don't exist yet, so not installed
            return false;
        }
    }

    private function publishConfigurations(): void
    {
        $this->info('📦 Publishing required configurations...');

        $this->callSilent('vendor:publish', [
            '--provider' => 'Spatie\Permission\PermissionServiceProvider',
            '--force' => true,
        ]);
        $this->line('  ✅ Published Spatie Permission configuration');

        $this->callSilent('vendor:publish', [
            '--provider' => 'BezhanSalleh\FilamentShield\FilamentShieldServiceProvider',
            '--force' => true,
        ]);
        $this->line('  ✅ Published Filament Shield configuration');

        $this->callSilent('vendor:publish', [
            '--tag' => 'filament-shield-translations',
            '--force' => true,
        ]);
        $this->line('  ✅ Published Filament Shield translations');

        $this->newLine();
    }

    private function runMigrations(): void
    {
        $this->info('🗄️  Running database migrations...');

        $this->callSilent('migrate', ['--force' => true]);
        $this->line('  ✅ Database migrations completed');

        $this->newLine();
    }

    private function createAdminUser(): User
    {
        $this->info('👤 Creating admin user...');

        $name = $this->ask('Enter admin name', 'Admin');
        $email = $this->ask('Enter admin email', 'admin@example.com');

        // Validate email
        while (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email format');
            $email = $this->ask('Enter admin email');
        }

        $password = $this->secret('Enter admin password (min 8 characters)');

        // Validate password
        while (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters');
            $password = $this->secret('Enter admin password (min 8 characters)');
        }

        // Check if user exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if ($this->confirm("User with email {$email} already exists. Update their details?")) {
                $existingUser->update([
                    'name' => $name,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]);
                $this->line("  ✅ Updated existing user: {$email}");

                return $existingUser;
            } else {
                $this->line("  ✅ Using existing user: {$email}");

                return $existingUser;
            }
        }

        // Create new user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->line("  ✅ Created admin user: {$email}");
        $this->newLine();

        return $user;
    }

    private function setupStorage(): void
    {
        $this->info('💾 Setting up storage and directories...');

        // Create storage link
        if (! file_exists(public_path('storage'))) {
            $this->callSilent('storage:link');
            $this->line('  ✅ Created storage symbolic link');
        } else {
            $this->line('  ✅ Storage link already exists');
        }

        // Create required directories
        $directories = [
            storage_path('app/csv-imports'),
            storage_path('app/geoip'),
            storage_path('app/csv-import-results'),
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
                $this->line('  ✅ Created directory: '.basename($directory));
            } else {
                $this->line('  ✅ Directory exists: '.basename($directory));
            }
        }

        // Set proper permissions
        $this->line('  🔄 Setting storage permissions...');
        $storagePath = storage_path();

        try {
            // Make storage writable
            chmod($storagePath, 0775);

            // Make specific directories writable
            foreach ($directories as $directory) {
                if (is_dir($directory)) {
                    chmod($directory, 0775);
                }
            }

            $this->line('  ✅ Storage permissions configured');
        } catch (\Exception $e) {
            $this->warn('  ⚠️  Could not set permissions automatically: '.$e->getMessage());
            $this->line('  📋 You may need to run: chmod -R 775 storage/');
        }

        $this->newLine();
    }

    private function setupShield(): void
    {
        $this->info('🛡️  Setting up Filament Shield...');

        // Install Shield first (this registers the plugin properly)
        $this->callSilent('shield:install', [
            'panel' => 'admin',
        ]);
        $this->line('  ✅ Installed Shield plugin');

        // Generate permissions and policies
        $this->callSilent('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
        ]);

        $this->line('  ✅ Generated permissions and policies');
        $this->newLine();
    }

    private function setupRoles(User $adminUser): void
    {
        $this->info('🔐 Setting up roles and permissions...');

        // Create roles via seeder
        $this->line('  🔄 Creating roles...');
        $this->callSilent('db:seed', [
            '--class' => 'ShieldSeeder',
            '--force' => true,
        ]);
        $this->line('  ✅ Created all roles');

        // Assign super_admin role to the admin user
        $this->line('  🔄 Assigning super_admin role...');
        if (! $adminUser->hasRole('super_admin')) {
            $adminUser->assignRole('super_admin');
            $this->line("  ✅ Assigned super_admin role to {$adminUser->email}");
        } else {
            $this->line('  ✅ User already has super_admin role');
        }

        // Set up default permissions
        $this->line('  🔄 Setting up default permissions...');
        $this->callSilent('roles:setup');
        $this->line('  ✅ Configured default role permissions');

        $this->newLine();
    }

    private function fixNavigationGrouping(): void
    {
        $this->info('🔧 Fixing navigation grouping...');

        $configPath = config_path('filament-shield.php');

        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            $originalContent = $content;

            // Try multiple possible formats
            $replacements = [
                "'navigation_group' => true," => "'navigation_group' => 'Settings',",
                '"navigation_group" => true,' => '"navigation_group" => "Settings",',
                "'navigation_group' => true" => "'navigation_group' => 'Settings'",
                '"navigation_group" => true' => '"navigation_group" => "Settings"',
            ];

            foreach ($replacements as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }

            if ($content !== $originalContent) {
                file_put_contents($configPath, $content);
                $this->line('  ✅ Updated Shield navigation group to "Settings"');
            } else {
                $this->warn('  ⚠️  Could not find navigation_group setting to update');
                $this->line('  📋 You may need to manually set navigation_group to "Settings" in config/filament-shield.php');
            }
        } else {
            $this->warn('  ⚠️  Shield config file not found, skipping navigation fix');
        }

        $this->newLine();
    }

    private function verifyShieldRoutes(): void
    {
        $this->info('🔍 Verifying Shield routes...');

        try {
            // Check if Shield routes are registered
            $allRoutes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes());

            $shieldRoutes = $allRoutes->filter(function ($route) {
                return str_contains($route->uri(), 'admin/shield');
            });

            $roleRoutes = $allRoutes->filter(function ($route) {
                return str_contains($route->uri(), 'admin/shield/roles');
            });

            $this->line("  📊 Found {$shieldRoutes->count()} Shield routes total");
            $this->line("  📊 Found {$roleRoutes->count()} Role routes specifically");

            if ($roleRoutes->count() > 0) {
                $this->line('  ✅ Role routes are registered:');
                foreach ($roleRoutes->take(3) as $route) {
                    $methods = implode('|', $route->methods());
                    $this->line("    • {$methods} {$route->uri()}");
                }
            } else {
                $this->warn('  ❌ No role routes found!');

                // Show some admin routes for comparison
                $adminRoutes = $allRoutes->filter(function ($route) {
                    return str_contains($route->uri(), 'admin/') && ! str_contains($route->uri(), 'shield');
                });
                $this->line("  📋 Found {$adminRoutes->count()} other admin routes");

                // Force clear caches
                $this->callSilent('route:clear');
                $this->callSilent('config:clear');
                $this->callSilent('filament:clear-cached-components');
                $this->line('  🔄 Cleared all caches');
            }

            // Check if RoleResource is actually registered in Filament
            $panel = app(\Filament\Panel::class);
            $resources = $panel->getResources();
            $roleResource = collect($resources)->first(function ($resource) {
                return str_contains($resource, 'RoleResource');
            });

            if ($roleResource) {
                $this->line("  ✅ RoleResource found in panel: {$roleResource}");
            } else {
                $this->warn('  ❌ RoleResource not registered in Filament panel!');
                $this->line('  📋 Total panel resources: '.count($resources));

                // If no resources are found, there's likely a bigger issue
                if (count($resources) === 0) {
                    $this->warn('  🚨 No Filament resources found at all!');
                    $this->line('  🔧 Trying to rebuild Filament...');

                    // Clear everything and rebuild
                    $this->callSilent('optimize:clear');
                    $this->callSilent('config:clear');
                    $this->callSilent('route:clear');
                    $this->callSilent('view:clear');
                    $this->callSilent('filament:clear-cached-components');

                    $this->line('  ⚠️  Please restart your web server after installation');
                    $this->line('  💡 The admin panel may need a restart to detect resources');
                }
            }

        } catch (\Exception $e) {
            $this->warn('  ⚠️  Could not verify routes: '.$e->getMessage());
        }

        $this->newLine();
    }

    private function finalNavigationFix(): void
    {
        $this->info('🎯 Final navigation group fix...');

        // The shield:install command might override our config, so fix it again
        $configPath = config_path('filament-shield.php');

        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);

            // Keep Shield's navigation registration enabled (we'll use translations to control group)
            $pattern = "/'should_register_navigation'\s*=>\s*[^,]+,/";
            $replacement = "'should_register_navigation' => true,";

            $newContent = preg_replace($pattern, $replacement, $content);

            if ($newContent !== $content) {
                file_put_contents($configPath, $newContent);
                $this->line('  ✅ Configured Shield navigation settings');
            } else {
                $this->warn('  ⚠️  Navigation group pattern not found for replacement');
            }

            // Fix the vendor translation file
            $vendorLangPath = resource_path('lang/vendor/filament-shield/en/filament-shield.php');
            if (file_exists($vendorLangPath)) {
                $content = file_get_contents($vendorLangPath);

                // Replace the nav.group value in the vendor file
                $content = preg_replace(
                    "/'nav\.group'\s*=>\s*'[^']*'/",
                    "'nav.group' => 'Settings'",
                    $content
                );

                file_put_contents($vendorLangPath, $content);
                $this->line('  ✅ Fixed vendor translation to use Settings group');
            }
        }

        $this->newLine();
    }

    private function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');

        $this->callSilent('optimize:clear');
        $this->callSilent('filament:clear-cached-components');

        $this->line('  ✅ All caches cleared');
        $this->newLine();
    }

    private function displaySuccessMessage(User $adminUser): void
    {
        $this->info('🎉 Installation completed successfully!');
        $this->newLine();

        $this->line('<bg=green;fg=white> READY TO USE </> Your link shortener is now ready!');
        $this->newLine();

        $this->info('📋 What\'s been set up:');
        $this->line('  • Database tables created and migrated');
        $this->line('  • Admin user created with super_admin permissions');
        $this->line('  • All roles configured (super_admin, admin, user)');
        $this->line('  • Default permissions assigned to each role');
        $this->line('  • Storage directories created with proper permissions');
        $this->line('  • Storage symbolic link created');
        $this->line('  • Filament admin panel ready to use');
        $this->newLine();

        $this->info('🌐 Access your application:');
        $this->line('  • Homepage: '.config('app.url'));
        $this->line('  • Admin Panel: '.config('app.url').'/admin');
        $this->line("  • Login with: {$adminUser->email}");
        $this->newLine();

        $this->info('📚 Next steps:');
        $this->line('  1. Visit /admin and login with your credentials');
        $this->line('  2. Check Settings → Roles to manage permissions');
        $this->line('  3. Create your first short link!');
        $this->newLine();

        $this->info('💡 Optional enhancements:');
        $this->line('  • Set up MaxMind GeoLite2: php artisan geoip:update');
        $this->line('  • Configure queue processing for better performance');
        $this->line('  • Set up automated health checks with cron');
        $this->newLine();

        $roleCount = Role::count();
        $permissionCount = Permission::count();
        $this->line("<fg=green>✅ Installation complete: {$roleCount} roles, {$permissionCount} permissions configured</>");
    }

    private function seedNotificationTypes(): void
    {
        $this->info('📧 Setting up notification types...');

        $this->callSilent('db:seed', [
            'class' => 'NotificationTypesSeeder',
        ]);

        $this->line('  ✅ Notification types created (link_health, system_alert, maintenance)');
        $this->newLine();
    }
}
