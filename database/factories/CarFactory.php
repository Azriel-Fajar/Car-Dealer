<?php

namespace Database\Factories;

use App\Enums\CarCondition;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Showroom;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->bothify('Model ??-###');

        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'showroom_id' => Showroom::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'year' => fake()->numberBetween(2018, 2026),
            'base_price' => fake()->numberBetween(200, 2000) * 1_000_000,
            'fuel_type' => fake()->randomElement(FuelType::cases()),
            'transmission' => fake()->randomElement(Transmission::cases()),
            'engine' => '2.0L Turbo',
            'power_hp' => fake()->numberBetween(90, 600),
            'torque_nm' => fake()->numberBetween(120, 800),
            'accel_0_100' => fake()->randomFloat(1, 3, 15),
            'top_speed' => fake()->numberBetween(160, 320),
            'range_km' => null,
            'seats' => fake()->randomElement([2, 4, 5, 7]),
            'mileage_km' => 0,
            'condition' => CarCondition::New,
            'stock' => fake()->numberBetween(1, 10),
            'badge' => null,
            'status' => CarStatus::Draft,
            'description' => fake()->paragraph(),
            'hotspots' => null,
            'is_featured' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => CarStatus::Published]);
    }

    public function draft(): static
    {
        return $this->state(['status' => CarStatus::Draft]);
    }

    public function soldOut(): static
    {
        return $this->state(['status' => CarStatus::SoldOut, 'stock' => 0]);
    }
}
