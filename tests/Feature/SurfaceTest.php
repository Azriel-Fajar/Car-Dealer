<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SurfaceTest extends TestCase
{
    use RefreshDatabase;

    /** Inspect only the <html> tag (the <style> also mentions the class). */
    private function htmlTag(TestResponse $response): string
    {
        preg_match('/<html[^>]*>/', $response->getContent(), $m);

        return $m[0];
    }

    public function test_public_auth_and_customer_pages_use_public_surface(): void
    {
        $this->assertStringContainsString('surface-public', $this->htmlTag($this->get(route('home'))));
        $this->assertStringContainsString('surface-public', $this->htmlTag($this->get(route('login'))));

        $customer = User::factory()->customer()->create();
        $this->assertStringContainsString('surface-public', $this->htmlTag($this->actingAs($customer)->get(route('account'))));
        $this->assertStringContainsString('surface-public', $this->htmlTag($this->actingAs($customer)->get(route('profile.edit'))));
    }

    public function test_staff_pages_are_light_by_default(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([route('dashboard'), route('profile.edit')] as $url) {
            $tag = $this->htmlTag($this->actingAs($admin)->get($url));
            $this->assertStringNotContainsString('surface-public', $tag);
            $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bdark\b/', $tag);
        }

        // no appearance cookie -> the inline theme script receives 'light', not 'system'
        $this->actingAs($admin)->get(route('dashboard'))->assertSee("const appearance = 'light'", false);
    }
}
