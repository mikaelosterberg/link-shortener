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
    }
}
