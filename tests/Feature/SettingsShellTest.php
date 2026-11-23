<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_profile_opens_for_every_role(): void
    {
        foreach (['admin', 'sales', 'customer'] as $state) {
            $this->actingAs(User::factory()->{$state}()->create())
                ->get(route('profile.edit'))
                ->assertOk();
        }
    }
}
