<?php

namespace Tests\Feature\Users;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_are_seeded_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(3, User::count());
        foreach (['admin@pemecut.test' => Role::Admin, 'sales@pemecut.test' => Role::Sales,
            'customer@pemecut.test' => Role::Customer] as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->is_active);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue(Hash::check('password', $user->password));
        }
    }
}
