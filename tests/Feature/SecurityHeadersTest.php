<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
    ];

    public function test_security_headers_are_sent_on_pages_redirects_and_errors(): void
    {
        $responses = [
            $this->get(route('home')),
            $this->get(route('dashboard')),
            $this->get('/halaman-tidak-ada'),
            $this->actingAs(User::factory()->customer()->create())->get(route('dashboard')),
        ];

        foreach ($responses as $r) {
            foreach (self::EXPECTED as $h => $v) {
                $r->assertHeader($h, $v);
            }
        }
    }
}
