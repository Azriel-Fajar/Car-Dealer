<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarColor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarColor>
 */
class CarColorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'name' => fake()->colorName(),
            'hex' => fake()->hexColor(),
            'price_delta' => 0,
            'image_path' => null,
        ];
    }
}
