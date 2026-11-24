<?php

namespace Tests\Feature\Dashboard;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CarHotspotTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->car = Car::factory()->create([
            'hotspots' => [['x' => 10.0, 'y' => 10.0, 'title' => 'Lama', 'description' => 'Hotspot lama.']],
        ]);
    }

    /**
     * @param  array<int, mixed>  $hotspots
     */
    private function save(array $hotspots): TestResponse
    {
        return $this->actingAs($this->admin)
            ->put(route('dashboard.cars.hotspots.update', $this->car), ['hotspots' => $hotspots]);
    }

    /**
     * @return array{x: float|int|string, y: float|int|string, title: string, description: string}
     */
    private function hotspot(float|int|string $x = 50, float|int|string $y = 50): array
    {
        return ['x' => $x, 'y' => $y, 'title' => 'Velg 20 inci', 'description' => 'Velg alloy dua warna.'];
    }

    public function test_replaces_hotspots_and_rounds(): void
    {
        $this->save([
            $this->hotspot(33.25, 33.5),
            $this->hotspot('12.349', 0),
            $this->hotspot(100, '99.96'),
        ])
            ->assertRedirect(route('dashboard.cars.edit', ['car' => $this->car, 'tab' => 'hotspots']))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Hotspot disimpan.']);

        $hotspots = $this->car->refresh()->hotspots;

        // JSON drops the zero fraction (100.0 is stored as 100), so whole
        // numbers compare loosely; the rounded ones are checked strictly.
        $this->assertSame(33.3, $hotspots[0]['x'] ?? null);
        $this->assertSame(12.3, $hotspots[1]['x'] ?? null);
        $this->assertEquals([
            ['x' => 33.3, 'y' => 33.5, 'title' => 'Velg 20 inci', 'description' => 'Velg alloy dua warna.'],
            ['x' => 12.3, 'y' => 0.0, 'title' => 'Velg 20 inci', 'description' => 'Velg alloy dua warna.'],
            ['x' => 100.0, 'y' => 100.0, 'title' => 'Velg 20 inci', 'description' => 'Velg alloy dua warna.'],
        ], $hotspots);
    }

    public function test_rejects_more_than_eight_and_out_of_range(): void
    {
        $this->save(array_fill(0, 9, $this->hotspot()))
            ->assertSessionHasErrors(['hotspots']);

        $this->save([
            $this->hotspot(-0.1, 50),
            $this->hotspot(50, 100.1),
            ['x' => 'abc', 'y' => 50, 'title' => str_repeat('a', 41), 'description' => str_repeat('b', 201)],
            ['x' => 50, 'y' => 50, 'title' => '', 'description' => ''],
        ])->assertSessionHasErrors([
            'hotspots.0.x', 'hotspots.1.y', 'hotspots.2.x', 'hotspots.2.title', 'hotspots.2.description',
            'hotspots.3.title', 'hotspots.3.description',
        ]);

        $this->assertSame('Lama', $this->car->refresh()->hotspots[0]['title'] ?? null);
    }

    public function test_empty_list_clears_hotspots(): void
    {
        $this->save([])
            ->assertRedirect(route('dashboard.cars.edit', ['car' => $this->car, 'tab' => 'hotspots']))
            ->assertSessionHasNoErrors();

        $this->assertSame([], $this->car->refresh()->hotspots);
    }

    public function test_trashed_car_returns_404(): void
    {
        $this->car->delete();

        $this->save([$this->hotspot()])->assertNotFound();
    }

    public function test_sales_cannot_save_hotspots(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)
            ->put(route('dashboard.cars.hotspots.update', $this->car), ['hotspots' => [$this->hotspot()]])
            ->assertForbidden();

        $this->assertSame('Lama', $this->car->refresh()->hotspots[0]['title'] ?? null);
    }
}
