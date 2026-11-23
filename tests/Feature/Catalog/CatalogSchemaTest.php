<?php

namespace Tests\Feature\Catalog;

use App\Enums\FuelType;
use App\Models\Brand;
use App\Models\Car;
use App\Models\CarColor;
use App\Models\CarImage;
use App\Models\CarOption;
use App\Models\Showroom;
use App\Support\CarSlug;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_relations_and_casts(): void
    {
        $hotspots = [['x' => 12.5, 'y' => 40.0, 'title' => 'LED', 'description' => 'Lampu']];
        $car = Car::factory()->create(['hotspots' => $hotspots]);
        CarColor::factory()->count(2)->create(['car_id' => $car->id]);
        CarOption::factory()->count(2)->create(['car_id' => $car->id]);
        $first = CarImage::factory()->create(['car_id' => $car->id, 'sort_order' => 0]);
        $second = CarImage::factory()->cover()->create(['car_id' => $car->id, 'sort_order' => 1]);
        CarImage::factory()->create(['car_id' => $car->id, 'sort_order' => 2]);

        $car = Car::findOrFail($car->id);

        $this->assertSame($second->id, $car->cover->id);
        $this->assertSame($first->id, $car->images->first()->id);
        $this->assertCount(3, $car->images);
        $this->assertCount(2, $car->colors);
        $this->assertCount(2, $car->options);
        $this->assertInstanceOf(FuelType::class, $car->fuel_type);
        $this->assertEquals($hotspots, $car->hotspots);
    }

    public function test_deleting_car_soft_deletes_and_keeps_children(): void
    {
        $car = Car::factory()->create();
        CarColor::factory()->count(2)->create(['car_id' => $car->id]);

        $car->delete();

        $this->assertSoftDeleted($car);
        $this->assertSame(2, CarColor::where('car_id', $car->id)->count());
    }

    public function test_brand_with_cars_cannot_be_hard_deleted_at_db_level(): void
    {
        $car = Car::factory()->create();

        $this->expectException(QueryException::class);

        $car->brand->delete();
    }

    public function test_deleting_showroom_nulls_car_showroom(): void
    {
        $showroom = Showroom::factory()->create();
        $car = Car::factory()->create(['showroom_id' => $showroom->id]);

        $showroom->delete();

        $this->assertNull($car->fresh()->showroom_id);
    }

    public function test_car_slug_is_unique_including_trashed(): void
    {
        $brand = Brand::factory()->create(['name' => 'Toyota']);
        $car = Car::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Fortuner',
            'year' => 2024,
            'slug' => 'toyota-fortuner-2024',
        ]);
        $car->delete();

        $this->assertSame('toyota-fortuner-2024-2', CarSlug::for('Toyota', 'Fortuner', 2024));
        $this->assertSame('toyota-fortuner-2024', CarSlug::for('Toyota', 'Fortuner', 2024, $car->id));
    }

    public function test_published_scope(): void
    {
        $published = Car::factory()->published()->create();
        Car::factory()->draft()->create();
        Car::factory()->soldOut()->create();

        $this->assertSame([$published->id], Car::published()->pluck('id')->all());
    }
}
