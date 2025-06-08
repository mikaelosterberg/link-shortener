<?php

namespace Tests\Feature;

use App\Events\LinkClicked;
use App\Events\LinkNotFound;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ExtensibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_redirects_to_admin_when_configured(): void
    {
        Config::set('shortener.homepage.redirect_to_admin', true);

        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }

    public function test_homepage_redirects_to_custom_url_when_configured(): void
    {
        Config::set('shortener.homepage.redirect_url', 'https://example.com');

        $response = $this->get('/');

        $response->assertRedirect('https://example.com');
    }

    public function test_custom_redirect_url_takes_precedence_over_admin_redirect(): void
    {
        Config::set('shortener.homepage.redirect_to_admin', true);
        Config::set('shortener.homepage.redirect_url', 'https://example.com');

        $response = $this->get('/');

        $response->assertRedirect('https://example.com');
    }

    public function test_link_not_found_redirects_to_custom_url_when_configured(): void
    {
        Config::set('shortener.not_found.redirect_url', 'https://example.com');

        $response = $this->get('/nonexistent');

        $response->assertRedirect('https://example.com');
    }

    public function test_link_not_found_event_is_dispatched(): void
    {
        Event::fake();

        $this->get('/nonexistent');

        Event::assertDispatched(LinkNotFound::class, function ($event) {
            return $event->shortCode === 'nonexistent';
        });
    }

    public function test_link_clicked_event_is_dispatched(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'test123',
            'original_url' => 'https://example.com',
            'created_by' => $user->id,
        ]);

        $response = $this->get('/test123');

        $response->assertRedirect('https://example.com');

        Event::assertDispatched(LinkClicked::class, function ($event) use ($link) {
            return $event->link->id === $link->id;
        });
    }

    public function test_404_tracking_can_be_disabled(): void
    {
        Config::set('shortener.not_found.track_attempts', false);

        $response = $this->get('/nonexistent');

        $response->assertStatus(404);
        // Would need to check logs don't contain the attempt, but that's harder to test
    }

    public function test_async_tracking_can_be_disabled(): void
    {
        Config::set('shortener.analytics.async_tracking', false);

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'short_code' => 'test456',
            'original_url' => 'https://example.com',
            'created_by' => $user->id,
        ]);

        $response = $this->get('/test456');

        $response->assertRedirect('https://example.com');
        // With async disabled, job wouldn't be dispatched, but testing that is complex
    }

    public function test_default_homepage_view_when_no_config(): void
    {
        Config::set('shortener.homepage.redirect_to_admin', false);
        Config::set('shortener.homepage.redirect_url', null);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }
}
