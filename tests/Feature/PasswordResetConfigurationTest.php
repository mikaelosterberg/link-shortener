<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PasswordResetConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
    }

    public function test_password_reset_disabled_with_log_driver(): void
    {
        // Set mail driver to log
        Config::set('mail.default', 'log');

        // Clear route cache and re-register routes
        $this->reloadApplication();

        // Check that password reset routes are not registered
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.request'));
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.reset'));
    }

    public function test_password_reset_disabled_with_array_driver(): void
    {
        // Set mail driver to array
        Config::set('mail.default', 'array');

        // Clear route cache and re-register routes
        $this->reloadApplication();

        // Check that password reset routes are not registered
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.request'));
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.reset'));
    }

    public function test_password_reset_disabled_with_null_driver(): void
    {
        // Set mail driver to null
        Config::set('mail.default', 'null');

        // Clear route cache and re-register routes
        $this->reloadApplication();

        // Check that password reset routes are not registered
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.request'));
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.reset'));
    }

    public function test_password_reset_disabled_with_localhost_smtp(): void
    {
        // Set mail driver to SMTP with localhost
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'localhost');

        // Clear route cache and re-register routes
        $this->reloadApplication();

        // Check that password reset routes are not registered
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.request'));
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.reset'));
    }

    public function test_password_reset_disabled_with_127_0_0_1_smtp(): void
    {
        // Set mail driver to SMTP with 127.0.0.1
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', '127.0.0.1');

        // Clear route cache and re-register routes
        $this->reloadApplication();

        // Check that password reset routes are not registered
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.request'));
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.reset'));
    }

    public function test_password_reset_enabled_with_valid_smtp(): void
    {
        // Set mail driver to SMTP with valid host
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.gmail.com');

        // Test the isEmailConfigured method directly
        $panelProvider = new \App\Providers\Filament\AdminPanelProvider($this->app);
        $reflection = new \ReflectionClass($panelProvider);
        $method = $reflection->getMethod('isEmailConfigured');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($panelProvider));
    }

    public function test_password_reset_enabled_with_mailgun(): void
    {
        // Set mail driver to mailgun
        Config::set('mail.default', 'mailgun');

        // Test the isEmailConfigured method directly
        $panelProvider = new \App\Providers\Filament\AdminPanelProvider($this->app);
        $reflection = new \ReflectionClass($panelProvider);
        $method = $reflection->getMethod('isEmailConfigured');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($panelProvider));
    }

    public function test_password_reset_enabled_with_ses(): void
    {
        // Set mail driver to ses
        Config::set('mail.default', 'ses');

        // Test the isEmailConfigured method directly
        $panelProvider = new \App\Providers\Filament\AdminPanelProvider($this->app);
        $reflection = new \ReflectionClass($panelProvider);
        $method = $reflection->getMethod('isEmailConfigured');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($panelProvider));
    }

    public function test_email_configuration_detection_with_log_driver(): void
    {
        Config::set('mail.default', 'log');

        $panelProvider = new \App\Providers\Filament\AdminPanelProvider($this->app);
        $reflection = new \ReflectionClass($panelProvider);
        $method = $reflection->getMethod('isEmailConfigured');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($panelProvider));
    }

    public function test_email_configuration_detection_with_array_driver(): void
    {
        Config::set('mail.default', 'array');

        $panelProvider = new \App\Providers\Filament\AdminPanelProvider($this->app);
        $reflection = new \ReflectionClass($panelProvider);
        $method = $reflection->getMethod('isEmailConfigured');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($panelProvider));
    }

    public function test_email_configuration_detection_with_localhost_smtp(): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'localhost');

        $panelProvider = new \App\Providers\Filament\AdminPanelProvider($this->app);
        $reflection = new \ReflectionClass($panelProvider);
        $method = $reflection->getMethod('isEmailConfigured');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($panelProvider));
    }

    public function test_email_configuration_detection_with_valid_smtp(): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.gmail.com');

        $panelProvider = new \App\Providers\Filament\AdminPanelProvider($this->app);
        $reflection = new \ReflectionClass($panelProvider);
        $method = $reflection->getMethod('isEmailConfigured');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($panelProvider));
    }

    /**
     * Helper method to reload the application and re-register routes
     */
    protected function reloadApplication(): void
    {
        // Clear route cache
        \Illuminate\Support\Facades\Artisan::call('route:clear');

        // Re-register the service provider to pick up new config
        $this->app->register(\App\Providers\Filament\AdminPanelProvider::class, true);

        // Re-boot the application
        $this->app->boot();
    }
}
