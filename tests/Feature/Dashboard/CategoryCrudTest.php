<?php

namespace Tests\Feature\Dashboard;

use App\Models\Car;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_index_lists_and_searches(): void
    {
        Category::factory()->create(['name' => 'SUV', 'slug' => 'suv']);
        Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);

        $this->actingAs($this->admin)
            ->get(route('dashboard.categories.index', ['q' => 'su']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/categories/index')
                ->has('items.data', 1)
                ->where('items.data.0.name', 'SUV')
                ->where('items.data.0.cars_count', 0)
                ->where('filters.q', 'su'));
    }

    public function test_store_creates_category(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.categories.store'), ['name' => 'Sport Car'])
            ->assertRedirect(route('dashboard.categories.index'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Kategori ditambahkan.']);

        $this->assertSame('sport-car', Category::query()->sole()->slug);
    }

    public function test_store_rejects_duplicate_name_and_colliding_slug(): void
    {
        Category::factory()->create(['name' => 'SUV', 'slug' => 'suv']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.categories.store'), ['name' => 'SUV'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->post(route('dashboard.categories.store'), ['name' => 'SUV!'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->post(route('dashboard.categories.store'), ['name' => str_repeat('a', 41)])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Category::query()->count());
    }

    public function test_update_renames_and_allows_same_name(): void
    {
        $category = Category::factory()->create(['name' => 'SUV', 'slug' => 'suv']);

        $this->actingAs($this->admin)
            ->put(route('dashboard.categories.update', $category), ['name' => 'SUV'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->put(route('dashboard.categories.update', $category), ['name' => 'Crossover'])
            ->assertRedirect(route('dashboard.categories.index'))
            ->assertSessionHasNoErrors();

        $category->refresh();

        $this->assertSame('Crossover', $category->name);
        $this->assertSame('crossover', $category->slug);
    }

    public function test_destroy_blocked_when_category_has_cars(): void
    {
        $category = Category::factory()->create(['name' => 'SUV', 'slug' => 'suv']);
        Car::factory()->count(2)->for($category)->create();

        $this->actingAs($this->admin)
            ->delete(route('dashboard.categories.destroy', $category))
            ->assertRedirect(route('dashboard.categories.index'))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Kategori masih dipakai 2 mobil.']);

        $this->assertModelExists($category);
    }

    public function test_destroy_blocked_when_category_only_has_soft_deleted_cars(): void
    {
        $category = Category::factory()->create(['name' => 'SUV', 'slug' => 'suv']);
        Car::factory()->for($category)->create()->delete();

        $this->actingAs($this->admin)
            ->get(route('dashboard.categories.index'))
            ->assertInertia(fn (Assert $page) => $page->where('items.data.0.cars_count', 1));

        $this->actingAs($this->admin)
            ->delete(route('dashboard.categories.destroy', $category))
            ->assertRedirect(route('dashboard.categories.index'))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Kategori masih dipakai 1 mobil.']);

        $this->assertModelExists($category);
    }

    public function test_destroy_deletes_unused_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('dashboard.categories.destroy', $category))
            ->assertRedirect(route('dashboard.categories.index'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Kategori dihapus.']);

        $this->assertModelMissing($category);
    }
}
