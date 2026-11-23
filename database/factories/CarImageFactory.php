<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarImage>
 */
class CarImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'path' => 'cars/'.fake()->unique()->uuid().'.jpg',
            'sort_order' => 0,
            'is_cover' => false,
        ];
    }

    public function cover(): static
    {
        return $this->state(['is_cover' => true]);
    }
}
