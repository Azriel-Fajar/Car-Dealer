<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $indexRoutes = [
        'dashboard.brands.index',
    ];

    public function test_sales_and_customers_are_forbidden_from_master_data(): void
    {
        $sales = User::factory()->sales()->create();
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        foreach ($this->indexRoutes as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
            $this->actingAs($sales)->get(route($name))->assertForbidden();
            $this->actingAs($customer)->get(route($name))->assertForbidden();
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
    }
}
