<?php

namespace Tests\Feature\Dashboard;

use App\Models\Car;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowroomCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pemecut Motors Denpasar',
            'city' => 'Denpasar',
            'address' => 'Jl. Teuku Umar No. 10',
            'phone' => '+62 361 123456',
            'lat' => -8.6705,
            'lng' => 115.2126,
        ], $overrides);
    }

    public function test_index_lists_and_searches_by_name_or_city(): void
    {
        Showroom::factory()->create(['name' => 'Pemecut Utama', 'city' => 'Denpasar']);
        Showroom::factory()->create(['name' => 'Pemecut Timur', 'city' => 'Surabaya']);

        $this->actingAs($this->admin)
            ->get(route('dashboard.showrooms.index', ['q' => 'sura']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/showrooms/index')
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Pemecut Timur')
                ->where('items.data.0.cars_count', 0)
                ->where('filters.q', 'sura'));
    }

    public function test_store_creates_showroom_with_coordinates(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.showrooms.store'), $this->payload())
            ->assertRedirect(route('dashboard.showrooms.index'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Showroom ditambahkan.']);

        $showroom = Showroom::query()->sole();

        $this->assertSame('Denpasar', $showroom->city);
        $this->assertEqualsWithDelta(-8.6705, (float) $showroom->lat, 0.00001);
        $this->assertEqualsWithDelta(115.2126, (float) $showroom->lng, 0.00001);
    }

    public function test_rejects_out_of_range_coordinates(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.showrooms.store'), $this->payload(['lat' => 91]))
            ->assertSessionHasErrors('lat');

        $this->actingAs($this->admin)
            ->post(route('dashboard.showrooms.store'), $this->payload(['lng' => 181]))
            ->assertSessionHasErrors('lng');

        $this->actingAs($this->admin)
            ->post(route('dashboard.showrooms.store'), $this->payload(['lat' => 'abc']))
            ->assertSessionHasErrors('lat');

        $this->assertSame(0, Showroom::query()->count());
    }

    public function test_rejects_invalid_phone_and_duplicate_name(): void
    {
        Showroom::factory()->create(['name' => 'Pemecut Motors Denpasar']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.showrooms.store'), $this->payload(['phone' => 'call-me']))
            ->assertSessionHasErrors('phone');

        $this->actingAs($this->admin)
            ->post(route('dashboard.showrooms.store'), $this->payload())
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Showroom::query()->count());
    }

    public function test_update_changes_showroom_and_allows_same_name(): void
    {
        $showroom = Showroom::factory()->create(['name' => 'Pemecut Motors Denpasar']);

        $this->actingAs($this->admin)
            ->put(route('dashboard.showrooms.update', $showroom), $this->payload(['city' => 'Badung']))
            ->assertRedirect(route('dashboard.showrooms.index'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Showroom diperbarui.']);

        $this->assertSame('Badung', $showroom->refresh()->city);
    }

    public function test_destroy_nulls_cars_showroom(): void
    {
        $showroom = Showroom::factory()->create();
        $car = Car::factory()->create(['showroom_id' => $showroom->id]);
        $trashed = Car::factory()->create(['showroom_id' => $showroom->id]);
        $trashed->delete();

        $this->actingAs($this->admin)
            ->delete(route('dashboard.showrooms.destroy', $showroom))
            ->assertRedirect(route('dashboard.showrooms.index'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Showroom dihapus.']);

        $this->assertModelMissing($showroom);
        $this->assertModelExists($car);
        $this->assertNull($car->refresh()->showroom_id);
        $this->assertNull(Car::withTrashed()->findOrFail($trashed->id)->showroom_id);
    }

    public function test_index_counts_soft_deleted_cars(): void
    {
        $showroom = Showroom::factory()->create();
        Car::factory()->create(['showroom_id' => $showroom->id])->delete();

        $this->actingAs($this->admin)
            ->get(route('dashboard.showrooms.index'))
            ->assertInertia(fn (Assert $page) => $page->where('items.data.0.cars_count', 1));
    }
}
