<?php

namespace Database\Factories;

use App\Models\AssemblyAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssemblyAction> */
class AssemblyActionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->bothify('AA-####'),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence()
        ];
    }
}
