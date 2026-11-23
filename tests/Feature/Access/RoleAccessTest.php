<?php

namespace Tests\Feature\Access;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_home(): void
    {
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('public/home'));
    }

    public function test_guests_are_redirected_to_login_from_protected_areas(): void
    {
        $this->get(route('account'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_customer_can_view_account_but_not_dashboard(): void
    {
        $c = User::factory()->customer()->create();
        $this->actingAs($c)->get(route('account'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('account/index'));
        $this->actingAs($c)->get(route('dashboard'))->assertForbidden();
    }

    public function test_staff_can_view_dashboard_but_not_account(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->sales()->create()] as $staff) {
            $this->actingAs($staff)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('dashboard/index'));
            $this->actingAs($staff)->get(route('account'))->assertForbidden();
        }
    }

    public function test_unverified_customer_can_still_reach_account(): void
    {
        $this->actingAs(User::factory()->customer()->unverified()->create())->get(route('account'))->assertOk();
    }

    public function test_role_home_dispatches_by_role(): void
    {
        $this->get(route('role-home'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('role-home'))->assertRedirect(route('account'));
        $this->actingAs(User::factory()->sales()->create())->get(route('role-home'))->assertRedirect(route('dashboard'));
    }
}
