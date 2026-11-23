<?php

namespace Database\Factories;

use App\Enums\OptionGroup;
use App\Models\Car;
use App\Models\CarOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarOption>
 */
class CarOptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'group' => fake()->randomElement(OptionGroup::cases()),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(1, 50) * 1_000_000,
            'image_path' => null,
        ];
    }
}
