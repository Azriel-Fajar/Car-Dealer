<?php

namespace Tests\Feature\Dashboard;

use App\Enums\CarStatus;
use App\Models\Brand;
use App\Models\Car;
use App\Models\CarColor;
use App\Models\CarImage;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CarCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Brand $brand;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->brand = Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        $this->category = Category::factory()->create(['name' => 'SUV', 'slug' => 'suv']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'showroom_id' => null,
            'name' => 'Fortuner',
            'year' => 2024,
            'base_price' => 600_000_000,
            'fuel_type' => 'diesel',
            'transmission' => 'automatic',
            'condition' => 'new',
            'status' => 'draft',
            'badge' => null,
            'engine' => '2.8L Diesel',
            'power_hp' => 201,
            'torque_nm' => 500,
            'accel_0_100' => 10.5,
            'top_speed' => 180,
            'range_km' => null,
            'seats' => 7,
            'stock' => 3,
            'mileage_km' => 0,
            'description' => 'SUV tangguh.',
            'is_featured' => false,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function payloadFor(Car $car, array $overrides = []): array
    {
        return $this->payload(array_merge([
            'brand_id' => $car->brand_id,
            'category_id' => $car->category_id,
            'name' => $car->name,
            'year' => $car->year,
            'status' => $car->status->value,
        ], $overrides));
    }

    public function test_index_filters_and_searches(): void
    {
        $honda = Brand::factory()->create(['name' => 'Honda', 'slug' => 'honda']);
        Car::factory()->create(['brand_id' => $this->brand->id, 'name' => 'Fortuner', 'status' => CarStatus::Published]);
        Car::factory()->create(['brand_id' => $honda->id, 'name' => 'Civic', 'status' => CarStatus::Draft]);
        Car::factory()->create(['brand_id' => $honda->id, 'name' => 'Brio', 'status' => CarStatus::Published])->delete();

        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/cars/index')
                ->has('items.data', 2)
                ->where('filters.view', 'list')
                ->has('options.brands', 2)
                ->has('options.statuses', 3));

        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.index', ['brand' => $honda->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Civic')
                ->where('items.data.0.brand_name', 'Honda')
                ->where('filters.brand', (string) $honda->id));

        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.index', ['status' => 'published']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Fortuner')
                ->where('items.data.0.status_label', 'Tayang'));

        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.index', ['q' => 'civic']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Civic')
                ->where('filters.q', 'civic'));

        // Search also matches the brand name.
        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.index', ['q' => 'toyo']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Fortuner'));

        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.index', ['trashed' => 1, 'view' => 'cards']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Brio')
                ->where('items.data.0.is_trashed', true)
                ->where('filters.trashed', '1')
                ->where('filters.view', 'cards'));
    }

    public function test_create_and_edit_render_options_and_tab(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/cars/create')
                ->where('tab', 'info')
                ->has('options.brands', 1)
                ->has('options.categories', 1)
                ->has('options.showrooms')
                ->has('options.fuelTypes', 4)
                ->has('options.transmissions', 4)
                ->has('options.conditions', 2)
                ->has('options.statuses', 3)
                ->has('options.badges', 5)
                ->has('options.optionGroups', 3));

        $car = Car::factory()->create();
        CarImage::factory()->create(['car_id' => $car->id]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.edit', ['car' => $car, 'tab' => 'specs']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/cars/edit')
                ->where('tab', 'specs')
                ->where('car.id', $car->id)
                ->has('car.images', 1)
                ->has('car.colors', 0)
                ->has('car.options', 0)
                ->has('car.hotspots'));

        $this->actingAs($this->admin)
            ->get(route('dashboard.cars.edit', ['car' => $car, 'tab' => 'nope']))
            ->assertInertia(fn (Assert $page) => $page->where('tab', 'info'));
    }

    public function test_store_creates_draft_with_unique_slug(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $first = Car::query()->sole();
        $this->assertSame('toyota-fortuner-2024', $first->slug);
        $this->assertSame(CarStatus::Draft, $first->status);

        $response = $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload());

        $second = Car::query()->whereKeyNot($first->id)->sole();
        $this->assertSame('toyota-fortuner-2024-2', $second->slug);

        $response
            ->assertRedirect(route('dashboard.cars.edit', ['car' => $second, 'tab' => 'colors']))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Mobil ditambahkan. Lanjutkan isi warna & opsi.']);
    }

    public function test_store_slug_skips_soft_deleted_cars(): void
    {
        Car::factory()->create(['brand_id' => $this->brand->id, 'slug' => 'toyota-fortuner-2024'])->delete();

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame('toyota-fortuner-2024-2', Car::query()->sole()->slug);
    }

    public function test_store_rejects_published_status(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload(['status' => 'published']))
            ->assertSessionHasErrors(['status' => 'Simpan sebagai draft dulu, tambahkan foto & warna sebelum tayang.']);

        $this->assertSame(0, Car::withTrashed()->count());
    }

    public function test_store_validates_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload([
                'brand_id' => 999,
                'name' => '',
                'year' => 1989,
                'base_price' => 9_999_999,
                'fuel_type' => 'steam',
                'seats' => 10,
                'stock' => 1000,
                'accel_0_100' => 31,
            ]))
            ->assertSessionHasErrors(['brand_id', 'name', 'year', 'base_price', 'fuel_type', 'seats', 'stock', 'accel_0_100']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload(['condition' => 'new', 'mileage_km' => 101]))
            ->assertSessionHasErrors('mileage_km');

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload(['condition' => 'used', 'mileage_km' => 45_000]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Car::query()->count());
    }

    public function test_store_allows_empty_engine(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload(['engine' => null]))
            ->assertSessionHasNoErrors();

        $this->assertSame('', Car::query()->sole()->engine);
    }

    public function test_electric_requires_range(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload(['fuel_type' => 'electric', 'range_km' => null]))
            ->assertSessionHasErrors('range_km');

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.store'), $this->payload(['fuel_type' => 'electric', 'range_km' => 450]))
            ->assertSessionHasNoErrors();

        $this->assertSame(450, Car::query()->sole()->range_km);
    }

    public function test_update_keeps_slug_when_only_price_changes(): void
    {
        $car = Car::factory()->create(['brand_id' => $this->brand->id, 'name' => 'Fortuner', 'year' => 2024, 'slug' => 'toyota-fortuner-2024']);

        $this->actingAs($this->admin)
            ->from(route('dashboard.cars.edit', ['car' => $car, 'tab' => 'specs']))
            ->put(route('dashboard.cars.update', $car), $this->payloadFor($car, ['base_price' => 650_000_000]))
            ->assertRedirect(route('dashboard.cars.edit', ['car' => $car, 'tab' => 'specs']))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Mobil diperbarui.']);

        $car->refresh();
        $this->assertSame('toyota-fortuner-2024', $car->slug);
        $this->assertSame(650_000_000, $car->base_price);
    }

    public function test_update_regenerates_slug_when_name_changes(): void
    {
        $car = Car::factory()->create(['brand_id' => $this->brand->id, 'name' => 'Fortuner', 'year' => 2024, 'slug' => 'toyota-fortuner-2024']);
        Car::factory()->create(['brand_id' => $this->brand->id, 'slug' => 'toyota-fortuner-gr-sport-2024']);

        $this->actingAs($this->admin)
            ->put(route('dashboard.cars.update', $car), $this->payloadFor($car, ['name' => 'Fortuner GR Sport']))
            ->assertSessionHasNoErrors();

        $this->assertSame('toyota-fortuner-gr-sport-2024-2', $car->refresh()->slug);
    }

    public function test_cannot_publish_without_image_and_color(): void
    {
        $car = Car::factory()->create(['brand_id' => $this->brand->id]);

        $this->actingAs($this->admin)
            ->put(route('dashboard.cars.update', $car), $this->payloadFor($car, ['status' => 'published']))
            ->assertSessionHasErrors('status');

        CarImage::factory()->cover()->create(['car_id' => $car->id]);

        $this->actingAs($this->admin)
            ->put(route('dashboard.cars.update', $car), $this->payloadFor($car, ['status' => 'published']))
            ->assertSessionHasErrors('status');

        $this->assertSame(CarStatus::Draft, $car->refresh()->status);

        CarColor::factory()->create(['car_id' => $car->id]);

        $this->actingAs($this->admin)
            ->put(route('dashboard.cars.update', $car), $this->payloadFor($car, ['status' => 'published']))
            ->assertSessionHasNoErrors();

        $this->assertSame(CarStatus::Published, $car->refresh()->status);
    }

    public function test_destroy_soft_deletes_and_restore_brings_back(): void
    {
        $car = Car::factory()->create();

        $this->actingAs($this->admin)
            ->from(route('dashboard.cars.index', ['q' => 'x']))
            ->delete(route('dashboard.cars.destroy', $car))
            ->assertRedirect(route('dashboard.cars.index', ['q' => 'x']))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Mobil dihapus.']);

        $this->assertSoftDeleted($car);

        $this->actingAs($this->admin)
            ->from(route('dashboard.cars.index', ['trashed' => 1]))
            ->patch(route('dashboard.cars.restore', $car))
            ->assertRedirect(route('dashboard.cars.index', ['trashed' => 1]))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Mobil dipulihkan.']);

        $this->assertNotSoftDeleted($car);
    }
}
