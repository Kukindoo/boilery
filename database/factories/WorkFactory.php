<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Quote;
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
            'quote_id' => null,
            'issue_id' => fn (array $attributes) => $attributes['quote_id'] === null
                ? Issue::factory()->create()->id
                : Quote::withTrashed()->findOrFail($attributes['quote_id'])->issue_id,
            'order_id' => $this->faker->unique()->uuid(),
        ];
    }

    public function forCustomer(User|UserFactory $customer): static
    {
        return $this->for(Issue::factory()->for($customer));
    }
}
