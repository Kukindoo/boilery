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
            'issue_id' => Issue::factory(),
            'order_id' => $this->faker->unique()->uuid(),
        ];
    }

    public function forCustomer(User|UserFactory $customer): static
    {
        return $this->for(Issue::factory()->for($customer));
    }
}
