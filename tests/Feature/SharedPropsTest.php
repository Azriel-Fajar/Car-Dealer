<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_props_expose_role_without_secrets(): void
    {
        $u = User::factory()->customer()->create(['phone' => '081234567890']);
        $this->actingAs($u)->get(route('account'))->assertInertia(fn (Assert $p) => $p
            ->where('name', 'Pemecut Motors')
            ->where('auth.user.role', 'customer')
            ->where('auth.user.is_active', true)
            ->where('auth.user.phone', '081234567890')
            ->missing('auth.user.password')
            ->missing('auth.user.two_factor_secret')
            ->missing('auth.user.remember_token'));
    }
}
