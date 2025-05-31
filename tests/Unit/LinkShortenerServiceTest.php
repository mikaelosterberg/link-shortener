<?php

namespace Tests\Unit;

use App\Models\Link;
use App\Models\User;
use App\Services\LinkShortenerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkShortenerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LinkShortenerService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new LinkShortenerService();
        $this->user = User::factory()->create();
    }

    public function test_generates_random_code(): void
    {
        $code = $this->service->generateShortCode();

        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $code);
    }

    public function test_processes_custom_slug_correctly(): void
    {
        $customSlug = 'My Custom Slug!@#';
        $code = $this->service->generateShortCode($customSlug);

        $this->assertEquals('my-custom-slug', $code);
    }

    public function test_processes_custom_slug_with_spaces(): void
    {
        $customSlug = 'hello world test';
        $code = $this->service->generateShortCode($customSlug);

        $this->assertEquals('hello-world-test', $code);
    }

    public function test_processes_custom_slug_with_special_characters(): void
    {
        $customSlug = 'test--__--special!!characters@@';
        $code = $this->service->generateShortCode($customSlug);

        $this->assertEquals('test-__-specialcharacters', $code);
    }

    public function test_rejects_empty_custom_slug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->generateShortCode('   !!!   ');
    }

    public function test_ensures_uniqueness_check_works(): void
    {
        // Create an existing link
        Link::create([
            'short_code' => 'existing',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $this->assertFalse($this->service->ensureUnique('existing'));
        $this->assertTrue($this->service->ensureUnique('new-code'));
    }

    public function test_generates_unique_code_successfully(): void
    {
        $code = $this->service->generateUniqueCode();

        $this->assertIsString($code);
        $this->assertTrue($this->service->ensureUnique($code));
    }

    public function test_throws_exception_for_taken_custom_slug(): void
    {
        // Create an existing link
        Link::create([
            'short_code' => 'taken',
            'original_url' => 'https://example.com',
            'redirect_type' => 302,
            'is_active' => true,
            'created_by' => $this->user->id,
            'click_count' => 0,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This custom URL is already taken');
        
        $this->service->generateUniqueCode('taken');
    }

    public function test_generates_different_codes_each_time(): void
    {
        $codes = [];
        
        for ($i = 0; $i < 10; $i++) {
            $codes[] = $this->service->generateShortCode();
        }

        // All codes should be unique (very high probability)
        $uniqueCodes = array_unique($codes);
        $this->assertCount(10, $uniqueCodes);
    }

    public function test_custom_slug_preserves_valid_characters(): void
    {
        $validSlug = 'test-123_abc';
        $code = $this->service->generateShortCode($validSlug);

        $this->assertEquals('test-123_abc', $code);
    }

    public function test_custom_slug_removes_multiple_hyphens(): void
    {
        $slugWithMultipleHyphens = 'test---multiple---hyphens';
        $code = $this->service->generateShortCode($slugWithMultipleHyphens);

        $this->assertEquals('test-multiple-hyphens', $code);
    }

    public function test_custom_slug_trims_hyphens(): void
    {
        $slugWithTrailingHyphens = '-test-slug-';
        $code = $this->service->generateShortCode($slugWithTrailingHyphens);

        $this->assertEquals('test-slug', $code);
    }
}