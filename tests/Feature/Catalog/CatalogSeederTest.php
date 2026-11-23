<?php

namespace Tests\Feature\Catalog;

use App\Enums\CarStatus;
use App\Enums\OptionGroup;
use App\Models\Brand;
use App\Models\Car;
use App\Models\CarOption;
use App\Models\Category;
use App\Models\Showroom;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_expected_catalog(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(6, Category::count());
        $this->assertSame(7, Brand::count());
        $this->assertSame(3, Showroom::count());
        $this->assertSame(10, Car::count());
        $this->assertSame(9, Car::where('status', CarStatus::Published)->count());
        $this->assertGreaterThanOrEqual(3, Car::where('is_featured', true)->count());

        foreach (Car::with(['colors', 'options'])->get() as $car) {
            $this->assertGreaterThanOrEqual(2, $car->colors->count(), $car->slug);
            $this->assertLessThanOrEqual(4, $car->colors->count(), $car->slug);
            $this->assertGreaterThanOrEqual(2, count($car->hotspots), $car->slug);
            $this->assertLessThanOrEqual(3, count($car->hotspots), $car->slug);
            foreach (OptionGroup::cases() as $group) {
                $this->assertTrue($car->options->contains('group', $group), "{$car->slug} {$group->value}");
            }
        }

        $this->assertTrue(Car::where('slug', 'toyota-fortuner-2024')->exists());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(10, Car::count());
        $this->assertSame(7, Brand::count());
        $this->assertSame(6, Category::count());
        $this->assertSame(3, Showroom::count());
        $this->assertSame(10 * 6, CarOption::count());
    }
}
