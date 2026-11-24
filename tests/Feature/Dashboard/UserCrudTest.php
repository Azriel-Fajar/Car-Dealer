<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Sales',
            'email' => 'budi@pemecut.test',
            'phone' => '+62 812 3456 7890',
            'role' => 'sales',
            'is_active' => true,
            'password' => 'S3cret-passw0rd!',
            'password_confirmation' => 'S3cret-passw0rd!',
        ], $overrides);
    }

    public function test_index_lists_and_searches_by_name_or_email(): void
    {
        User::factory()->sales()->create(['name' => 'Wayan Putra', 'email' => 'wayan@pemecut.test']);
        User::factory()->customer()->create(['name' => 'Ketut Dewi', 'email' => 'ketut@example.test']);

        $this->actingAs($this->admin)
            ->get(route('dashboard.users.index', ['q' => 'example.test']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/users/index')
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Ketut Dewi')
                ->where('items.data.0.role', 'customer')
                ->where('items.data.0.is_active', true)
                ->where('filters.q', 'example.test'));
    }

    public function test_filter_by_role_and_active(): void
    {
        User::factory()->sales()->create(['name' => 'Sales Aktif']);
        User::factory()->sales()->inactive()->create(['name' => 'Sales Nonaktif']);
        User::factory()->customer()->create(['name' => 'Pelanggan']);

        $this->actingAs($this->admin)
            ->get(route('dashboard.users.index', ['role' => 'sales']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 2)
                ->where('filters.role', 'sales'));

        $this->actingAs($this->admin)
            ->get(route('dashboard.users.index', ['role' => 'sales', 'active' => '0']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Sales Nonaktif')
                ->where('filters.active', '0'));

        // Admin (1) + sales aktif (1) + pelanggan (1) are active.
        $this->actingAs($this->admin)
            ->get(route('dashboard.users.index', ['active' => '1']))
            ->assertInertia(fn (Assert $page) => $page->has('items.data', 3));
    }

    public function test_admin_creates_sales_account(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.users.store'), $this->payload())
            ->assertRedirect(route('dashboard.users.index'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Pengguna ditambahkan.']);

        $user = User::query()->where('email', 'budi@pemecut.test')->sole();

        $this->assertSame(Role::Sales, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);

        Auth::logout();
        $this->post(route('login.store'), ['email' => 'budi@pemecut.test', 'password' => 'S3cret-passw0rd!'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_store_ignores_nothing_but_validated_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.users.store'), $this->payload([
                'email_verified_at' => null,
                'id' => 999,
                'remember_token' => 'x',
            ]))
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'budi@pemecut.test')->sole();

        $this->assertNotNull($user->email_verified_at);
        $this->assertNotSame(999, $user->id);
        $this->assertNull($user->remember_token);
    }

    public function test_store_validates_input(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.users.store'), $this->payload([
                'name' => '',
                'email' => 'bukan-email',
                'phone' => 'abc',
                'role' => 'superuser',
                'password_confirmation' => 'beda',
            ]))
            ->assertSessionHasErrors(['name', 'email', 'phone', 'role', 'password']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'budi@pemecut.test']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.users.store'), $this->payload())
            ->assertSessionHasErrors('email');
    }

    public function test_update_changes_fields_and_password_when_filled(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($this->admin)
            ->put(route('dashboard.users.update', $user), $this->payload([
                'name' => 'Nama Baru',
                'role' => 'sales',
                'is_active' => false,
                'password' => 'An0ther-passw0rd!',
                'password_confirmation' => 'An0ther-passw0rd!',
            ]))
            ->assertRedirect(route('dashboard.users.index'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Pengguna diperbarui.']);

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame(Role::Sales, $user->role);
        $this->assertFalse($user->is_active);
        $this->assertTrue(password_verify('An0ther-passw0rd!', $user->password));
    }

    public function test_update_without_password_keeps_password(): void
    {
        $user = User::factory()->customer()->create();
        $hash = $user->password;

        $this->actingAs($this->admin)
            ->put(route('dashboard.users.update', $user), $this->payload([
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($hash, $user->refresh()->password);
    }

    public function test_update_keeping_own_email_is_allowed(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($this->admin)
            ->put(route('dashboard.users.update', $user), $this->payload([
                'email' => $user->email,
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_update_to_another_users_email_fails_on_email(): void
    {
        $other = User::factory()->create(['email' => 'taken@pemecut.test']);
        $user = User::factory()->customer()->create();

        $this->actingAs($this->admin)
            ->put(route('dashboard.users.update', $user), $this->payload([
                'email' => $other->email,
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertSessionHasErrors('email');

        $this->assertNotSame('taken@pemecut.test', $user->refresh()->email);
    }

    public function test_admin_cannot_deactivate_or_demote_self(): void
    {
        User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->put(route('dashboard.users.update', $this->admin), $this->payload([
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'sales',
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Tidak bisa mengubah peran akun sendiri.']);

        $this->actingAs($this->admin)
            ->put(route('dashboard.users.update', $this->admin), $this->payload([
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
                'is_active' => false,
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Tidak bisa menonaktifkan akun sendiri.']);

        $this->admin->refresh();
        $this->assertSame(Role::Admin, $this->admin->role);
        $this->assertTrue($this->admin->is_active);
    }

    public function test_cannot_delete_self(): void
    {
        User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->delete(route('dashboard.users.destroy', $this->admin))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Tidak bisa menghapus akun sendiri.']);

        $this->assertModelExists($this->admin);
    }

    public function test_admin_deletes_another_user(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($this->admin)
            ->delete(route('dashboard.users.destroy', $user))
            ->assertRedirect(route('dashboard.users.index'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Pengguna dihapus.']);

        $this->assertModelMissing($user);
    }

    public function test_last_active_admin_is_protected(): void
    {
        User::factory()->admin()->inactive()->create();

        $this->actingAs($this->admin)
            ->put(route('dashboard.users.update', $this->admin), $this->payload([
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'sales',
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Minimal harus ada satu admin aktif.']);

        $this->assertSame(Role::Admin, $this->admin->refresh()->role);
    }

    public function test_admin_can_demote_another_admin_when_one_active_admin_remains(): void
    {
        $other = User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->put(route('dashboard.users.update', $other), $this->payload([
                'name' => $other->name,
                'email' => $other->email,
                'role' => 'sales',
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Pengguna diperbarui.']);

        $this->assertSame(Role::Sales, $other->refresh()->role);
    }

    public function test_back_keeps_index_filters_after_store(): void
    {
        $url = route('dashboard.users.index', ['q' => 'a', 'role' => 'sales', 'active' => '1', 'page' => 1]);

        $this->actingAs($this->admin)
            ->from($url)
            ->post(route('dashboard.users.store'), $this->payload())
            ->assertRedirect($url);
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->sales()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
