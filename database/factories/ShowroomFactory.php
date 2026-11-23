<?php

namespace Database\Factories;

use App\Models\Showroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Showroom>
 */
class ShowroomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Pemecut Motors '.fake()->city(),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'phone' => '08'.fake()->numerify('##########'),
            'lat' => fake()->latitude(-8, -6),
            'lng' => fake()->longitude(106, 112),
        ];
    }
}
