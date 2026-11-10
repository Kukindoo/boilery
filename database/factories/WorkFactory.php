<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Work> */
class WorkFactory extends Factory
{
    protected $model = Work::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'issue_id' => fn (array $attributes) => Issue::factory()->create(['user_id' => $attributes['user_id']])->id,
            'quote_id' => null,
            'order_id' => $this->faker->unique()->uuid(),
        ];
    }
}
