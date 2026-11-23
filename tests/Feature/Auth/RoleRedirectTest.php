<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_login_redirects_to_account(): void
    {
        $c = User::factory()->customer()->create();
        $this->post(route('login.store'), ['email' => $c->email, 'password' => 'password'])
            ->assertRedirect(route('account'));
    }

    public function test_customer_login_returns_to_intended_url(): void
    {
        $c = User::factory()->customer()->create();
        $this->withSession(['url.intended' => route('profile.edit')])
            ->post(route('login.store'), ['email' => $c->email, 'password' => 'password'])
            ->assertRedirect(route('profile.edit'));
    }

    public function test_staff_login_ignores_intended_url_and_lands_on_dashboard(): void
    {
        $a = User::factory()->admin()->create();
        $this->withSession(['url.intended' => route('account')])
            ->post(route('login.store'), ['email' => $a->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'))->assertSessionMissing('url.intended');
    }

    public function test_registration_lands_customer_on_account_and_ignores_role_payload(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Ani', 'email' => 'ani@example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'role' => 'admin', 'is_active' => '0',
        ])->assertRedirect(route('account'));
        $u = User::where('email', 'ani@example.com')->firstOrFail();
        $this->assertSame(Role::Customer, $u->role);
        $this->assertTrue($u->is_active);
    }

    public function test_two_factor_login_redirects_staff_to_dashboard(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $s = User::factory()->sales()->withTwoFactor()->create();
        $this->post(route('login.store'), ['email' => $s->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1'])->assertRedirect(route('dashboard'));
    }

    public function test_json_login_returns_redirect_target(): void
    {
        $c = User::factory()->customer()->create();
        $this->postJson(route('login.store'), ['email' => $c->email, 'password' => 'password'])
            ->assertOk()->assertJson(['two_factor' => false, 'redirect' => route('account')]);
    }

    public function test_authenticated_users_opening_guest_pages_are_sent_to_role_home(): void
    {
        $c = User::factory()->customer()->create();
        $this->actingAs($c)->get(route('login'))->assertRedirect(route('role-home'));
        $this->actingAs($c)->get(route('register'))->assertRedirect(route('role-home'));
    }

    public function test_verified_customer_visiting_verification_prompt_goes_to_role_home(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get(route('verification.notice'))->assertRedirect(route('role-home'));
    }
}
