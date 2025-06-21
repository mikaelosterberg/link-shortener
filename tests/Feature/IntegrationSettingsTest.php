<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    public function test_integration_setting_can_store_and_retrieve_values(): void
    {
        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-XXXXXXXXXX');
        IntegrationSetting::set('google_analytics', 'api_secret', 'test-secret-123');
        IntegrationSetting::set('google_analytics', 'enabled', true);

        $this->assertEquals('G-XXXXXXXXXX', IntegrationSetting::get('google_analytics', 'measurement_id'));
        $this->assertEquals('test-secret-123', IntegrationSetting::get('google_analytics', 'api_secret'));
        $this->assertTrue(IntegrationSetting::get('google_analytics', 'enabled'));
    }

    public function test_integration_setting_returns_default_when_not_found(): void
    {
        $this->assertNull(IntegrationSetting::get('google_analytics', 'nonexistent'));
        $this->assertEquals('default', IntegrationSetting::get('google_analytics', 'nonexistent', 'default'));
        $this->assertFalse(IntegrationSetting::get('google_analytics', 'enabled', false));
    }

    public function test_integration_setting_can_update_existing_values(): void
    {
        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-ORIGINAL');
        $this->assertEquals('G-ORIGINAL', IntegrationSetting::get('google_analytics', 'measurement_id'));

        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-UPDATED');
        $this->assertEquals('G-UPDATED', IntegrationSetting::get('google_analytics', 'measurement_id'));
    }

    public function test_integration_setting_enforces_unique_provider_key_combination(): void
    {
        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-FIRST');
        IntegrationSetting::set('webhooks', 'measurement_id', 'webhook-value'); // Different provider, same key

        $this->assertEquals('G-FIRST', IntegrationSetting::get('google_analytics', 'measurement_id'));
        $this->assertEquals('webhook-value', IntegrationSetting::get('webhooks', 'measurement_id'));
    }

    public function test_super_admin_can_access_integrations_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/integrations-settings');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_integrations_page(): void
    {
        // First give admin the permission
        $this->admin->givePermissionTo('page_IntegrationsSettings');

        $response = $this->actingAs($this->admin)
            ->get('/admin/integrations-settings');

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_integrations_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/integrations-settings');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_integrations_page(): void
    {
        $response = $this->get('/admin/integrations-settings');

        $response->assertRedirect('/admin/login');
    }

    public function test_integration_settings_page_displays_google_analytics_form(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/integrations-settings');

        $response->assertStatus(200);
        $response->assertSee('Google Analytics Integration');
        $response->assertSee('Enable Google Analytics tracking');
        $response->assertSee('GA4 Measurement ID');
        $response->assertSee('Measurement Protocol API Secret');
    }

    public function test_can_save_google_analytics_settings(): void
    {
        $formData = [
            'google_analytics_enabled' => true,
            'google_analytics_measurement_id' => 'G-TESTTEST123',
            'google_analytics_api_secret' => 'test-api-secret-456',
            'google_analytics_notes' => 'Test configuration notes',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post('/admin/integrations-settings', $formData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify settings were saved
        $this->assertTrue(IntegrationSetting::get('google_analytics', 'enabled'));
        $this->assertEquals('G-TESTTEST123', IntegrationSetting::get('google_analytics', 'measurement_id'));
        $this->assertEquals('test-api-secret-456', IntegrationSetting::get('google_analytics', 'api_secret'));
        $this->assertEquals('Test configuration notes', IntegrationSetting::get('google_analytics', 'notes'));
    }

    public function test_can_disable_google_analytics_integration(): void
    {
        // First enable it
        IntegrationSetting::set('google_analytics', 'enabled', true);
        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-TESTTEST123');

        $formData = [
            'google_analytics_enabled' => false,
            'google_analytics_measurement_id' => 'G-TESTTEST123',
            'google_analytics_api_secret' => 'test-secret',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post('/admin/integrations-settings', $formData);

        $response->assertRedirect();
        $this->assertFalse(IntegrationSetting::get('google_analytics', 'enabled'));
    }

    public function test_form_validation_requires_measurement_id_when_enabled(): void
    {
        $formData = [
            'google_analytics_enabled' => true,
            'google_analytics_measurement_id' => '', // Empty
            'google_analytics_api_secret' => 'test-secret',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post('/admin/integrations-settings', $formData);

        $response->assertSessionHasErrors('google_analytics_measurement_id');
    }

    public function test_form_validation_requires_api_secret_when_enabled(): void
    {
        $formData = [
            'google_analytics_enabled' => true,
            'google_analytics_measurement_id' => 'G-TESTTEST123',
            'google_analytics_api_secret' => '', // Empty
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post('/admin/integrations-settings', $formData);

        $response->assertSessionHasErrors('google_analytics_api_secret');
    }

    public function test_form_validation_accepts_valid_measurement_id_formats(): void
    {
        $validIds = [
            'G-XXXXXXXXXX',
            'G-ABC123DEF4',
            'G-1234567890',
        ];

        foreach ($validIds as $id) {
            $formData = [
                'google_analytics_enabled' => true,
                'google_analytics_measurement_id' => $id,
                'google_analytics_api_secret' => 'test-secret',
            ];

            $response = $this->actingAs($this->superAdmin)
                ->post('/admin/integrations-settings', $formData);

            $response->assertRedirect();
            $response->assertSessionHasNoErrors();
        }
    }

    public function test_can_test_google_analytics_connection(): void
    {
        IntegrationSetting::set('google_analytics', 'enabled', true);
        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-TESTTEST123');
        IntegrationSetting::set('google_analytics', 'api_secret', 'test-secret');

        // This would normally make an HTTP request, but we'll mock it in the service tests
        // Here we just test that the endpoint exists and is accessible
        $response = $this->actingAs($this->superAdmin)
            ->post('/admin/integrations-settings/test-ga-connection');

        // Should return JSON response (success or failure)
        $response->assertHeader('content-type', 'application/json');
    }

    public function test_integration_settings_persist_across_requests(): void
    {
        IntegrationSetting::set('google_analytics', 'enabled', true);
        IntegrationSetting::set('google_analytics', 'measurement_id', 'G-PERSISTENT');

        // Make a new request and verify settings persist
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/integrations-settings');

        $response->assertStatus(200);

        // Settings should still be there
        $this->assertTrue(IntegrationSetting::get('google_analytics', 'enabled'));
        $this->assertEquals('G-PERSISTENT', IntegrationSetting::get('google_analytics', 'measurement_id'));
    }
}
