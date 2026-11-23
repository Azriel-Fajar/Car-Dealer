<?php

namespace Tests\Feature\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_created_without_role_default_to_active_customer(): void
    {
        $user = User::create(['name' => 'Budi', 'email' => 'budi@example.com', 'password' => 'password']);
        $this->assertSame(Role::Customer, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertSame(Role::Customer, $user->fresh()->role);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_role_and_is_active_are_not_mass_assignable(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'password',
            'role' => 'admin', 'is_active' => false]);
        $this->assertSame(Role::Customer, $user->fresh()->role);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_phone_is_mass_assignable(): void
    {
        $user = User::create(['name' => 'Y', 'email' => 'y@example.com', 'password' => 'password', 'phone' => '081234567890']);
        $this->assertSame('081234567890', $user->fresh()->phone);
    }

    public function test_only_admin_and_sales_are_staff(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->isStaff());
        $this->assertTrue(User::factory()->sales()->create()->isStaff());
        $this->assertFalse(User::factory()->customer()->create()->isStaff());
        $this->assertFalse(User::factory()->inactive()->create()->is_active);
        $this->assertSame('Pelanggan', Role::Customer->label());
    }
}
