<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_log_in(): void
    {
        $u = User::factory()->inactive()->create();
        $this->post(route('login.store'), ['email' => $u->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => EnsureUserIsActive::MESSAGE]);
        $this->assertGuest();
    }

    public function test_inactive_user_with_wrong_password_gets_generic_error(): void
    {
        $u = User::factory()->inactive()->create();
        $r = $this->post(route('login.store'), ['email' => $u->email, 'password' => 'salah']);
        $r->assertSessionHasErrors('email');
        $this->assertNotSame(EnsureUserIsActive::MESSAGE, session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_user_deactivated_mid_session_is_logged_out_on_next_request(): void
    {
        $u = User::factory()->sales()->create();
        $this->actingAs($u);
        $u->forceFill(['is_active' => false])->save();
        $this->get(route('dashboard'))->assertRedirect(route('login'))->assertSessionHasErrors(['email' => EnsureUserIsActive::MESSAGE]);
        $this->assertGuest();
    }
}
