<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The headers securityheaders.com grades, on the welcome page and on API answers. */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    private function assertProtected($response): void
    {
        foreach (['X-Frame-Options' => 'DENY', 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer'] as $name => $value) {
            $response->assertHeader($name, $value);
        }
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
        $this->assertStringStartsWith("default-src 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_the_welcome_page(): void
    {
        $this->assertProtected($this->get('/')->assertOk());
    }

    public function test_api_answers_and_errors(): void
    {
        $this->assertProtected($this->getJson('/api/categories')->assertOk());
        $this->assertProtected($this->postJson('/api/login', [])->assertStatus(422));
    }

    public function test_hsts_only_over_https(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
