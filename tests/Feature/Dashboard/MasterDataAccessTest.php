<?php

namespace Tests\Feature\Dashboard;

use App\Models\Car;
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
        'dashboard.categories.index',
        'dashboard.cars.index',
        'dashboard.cars.create',
        'dashboard.showrooms.index',
        'dashboard.users.index',
    ];

    public function test_sales_and_customers_are_forbidden_from_master_data(): void
    {
        $sales = User::factory()->sales()->create();
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        // Guests first: actingAs() keeps the user authenticated for later requests in this test.
        foreach ($this->indexRoutes as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }

        foreach ($this->indexRoutes as $name) {
            $this->actingAs($sales)->get(route($name))->assertForbidden();
            $this->actingAs($customer)->get(route($name))->assertForbidden();
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
    }

    public function test_sales_cannot_write_or_restore_cars(): void
    {
        $sales = User::factory()->sales()->create();
        $car = Car::factory()->create();

        $this->actingAs($sales)->get(route('dashboard.cars.edit', $car))->assertForbidden();
        $this->actingAs($sales)->post(route('dashboard.cars.store'), [])->assertForbidden();
        $this->actingAs($sales)->put(route('dashboard.cars.update', $car), [])->assertForbidden();
        $this->actingAs($sales)->delete(route('dashboard.cars.destroy', $car))->assertForbidden();
        $this->assertNotSoftDeleted($car);

        $car->delete();
        $this->actingAs($sales)->patch(route('dashboard.cars.restore', $car))->assertForbidden();
        $this->assertSoftDeleted($car);
    }
}
