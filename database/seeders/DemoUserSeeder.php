<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Admin Pemecut', 'admin@pemecut.test', Role::Admin, '081200000001'],
            ['Sales Pemecut', 'sales@pemecut.test', Role::Sales, '081200000002'],
            ['Customer Demo', 'customer@pemecut.test', Role::Customer, '081200000003'],
        ];

        foreach ($accounts as [$name, $email, $role, $phone]) {
            User::query()->firstOrNew(['email' => $email])->forceFill([
                'name' => $name,
                'phone' => $phone,
                'role' => $role,
                'is_active' => true,
                'password' => 'password',
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
