<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable middleware for testing (can be enabled per test if needed)
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Reset to default queue tracking for tests unless explicitly testing Redis
        config(['shortener.analytics.click_tracking_method' => 'queue']);
    }
}
