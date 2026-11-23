<?php

namespace Tests\Feature\Dashboard;

use App\Models\Brand;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BrandCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_index_lists_and_searches(): void
    {
        Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        Brand::factory()->create(['name' => 'Honda', 'slug' => 'honda']);

        $this->actingAs($this->admin)
            ->get(route('dashboard.brands.index', ['q' => 'toy']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/brands/index')
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Toyota')
                ->where('items.data.0.cars_count', 0)
                ->where('filters.q', 'toy'));
    }

    public function test_store_creates_brand_with_logo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.brands.store'), [
                'name' => 'Toyota',
                'logo' => UploadedFile::fake()->image('toyota.png', 200, 200),
            ])
            ->assertRedirect(route('dashboard.brands.index'))
            ->assertSessionHasNoErrors();

        $brand = Brand::query()->sole();

        $this->assertSame('toyota', $brand->slug);
        $this->assertNotNull($brand->logo_path);
        $this->assertStringStartsWith('brands/', $brand->logo_path);
        Storage::disk('public')->assertExists($brand->logo_path);
    }

    public function test_store_rejects_svg_and_duplicate_name(): void
    {
        Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']);

        $svgMarkup = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->actingAs($this->admin)
            ->post(route('dashboard.brands.store'), [
                'name' => 'Nissan',
                'logo' => UploadedFile::fake()->createWithContent('logo.svg', $svgMarkup),
            ])
            ->assertSessionHasErrors('logo');

        // An SVG renamed to .png: the real (content-sniffed) MIME type must still be rejected.
        $tmp = tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($tmp, $svgMarkup);

        $this->actingAs($this->admin)
            ->post(route('dashboard.brands.store'), [
                'name' => 'Nissan',
                'logo' => new UploadedFile($tmp, 'logo.png', null, null, true),
            ])
            ->assertSessionHasErrors('logo');

        $this->actingAs($this->admin)
            ->post(route('dashboard.brands.store'), ['name' => 'Toyota'])
            ->assertSessionHasErrors('name');

        // Different name, same slug: refused as a validation error instead of a unique-index 500.
        $this->actingAs($this->admin)
            ->post(route('dashboard.brands.store'), ['name' => 'TOYOTA!'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Brand::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_store_rejects_logo_over_one_megabyte(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.brands.store'), [
                'name' => 'Nissan',
                'logo' => UploadedFile::fake()->image('big.png')->size(1025),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertSame(0, Brand::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_update_replaces_logo_and_deletes_old_file(): void
    {
        $oldPath = UploadedFile::fake()->image('old.png')->store('brands', 'public');
        $brand = Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota', 'logo_path' => $oldPath]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.brands.update', $brand), [
                '_method' => 'PUT',
                'name' => 'Toyota Motor',
                'logo' => UploadedFile::fake()->image('new.png'),
            ])
            ->assertRedirect(route('dashboard.brands.index'))
            ->assertSessionHasNoErrors();

        $brand->refresh();

        $this->assertSame('Toyota Motor', $brand->name);
        $this->assertSame('toyota-motor', $brand->slug);
        $this->assertNotSame($oldPath, $brand->logo_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists((string) $brand->logo_path);
    }

    public function test_update_without_logo_keeps_existing_file_and_allows_same_name(): void
    {
        $path = UploadedFile::fake()->image('logo.png')->store('brands', 'public');
        $brand = Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota', 'logo_path' => $path]);

        $this->actingAs($this->admin)
            ->put(route('dashboard.brands.update', $brand), ['name' => 'Toyota'])
            ->assertSessionHasNoErrors();

        $this->assertSame($path, $brand->refresh()->logo_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_destroy_blocked_when_brand_has_cars(): void
    {
        $brand = Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        Car::factory()->count(2)->for($brand)->create();

        $this->actingAs($this->admin)
            ->delete(route('dashboard.brands.destroy', $brand))
            ->assertRedirect(route('dashboard.brands.index'))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Brand masih dipakai 2 mobil.']);

        $this->assertModelExists($brand);
    }

    public function test_destroy_deletes_brand_and_logo(): void
    {
        $path = UploadedFile::fake()->image('logo.png')->store('brands', 'public');
        $brand = Brand::factory()->create(['logo_path' => $path]);

        $this->actingAs($this->admin)
            ->delete(route('dashboard.brands.destroy', $brand))
            ->assertRedirect(route('dashboard.brands.index'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Brand dihapus.']);

        $this->assertModelMissing($brand);
        Storage::disk('public')->assertMissing($path);
    }
}
