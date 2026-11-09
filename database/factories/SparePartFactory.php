<?php

namespace Database\Factories;

use App\Models\SparePart;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SparePart> */
class SparePartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'price_in_cents' => fake()->numberBetween(0, 100000),
            'name' => fake()->words(3, true),
            'part_number' => fake()->bothify('SP-####'),
            'description' => fake()->sentence(),
        ];
    }
}
